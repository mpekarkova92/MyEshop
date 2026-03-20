<?php declare(strict_types=1);

    namespace App\Presentation\Cart;

    use Nette;
    use Nette\Database\Explorer;
    use App\Model\PaymentService;

    /**
    * Presenter pro obsluhu nákupního košíku
    */
    final class CartPresenter extends Nette\Application\UI\Presenter
    {
        /**
        * Konstruktor: Připojení databáze a platební služby
        */
        public function __construct(
            private Explorer $database,
            private PaymentService $paymentService,
        ) {}

        /**
        * renderDefault: Připravuje data pro zobrazení košíku
        */
        public function renderDefault(): void
        {
            // Otevření session sekce košíku
            $session = $this->getSession('cart');

                    // Načtení ID produktů ze session (pokud nic není, prázdné pole)
                    $rawItems = $session->items ?? [];
                    $items = is_array($rawItems) ? $rawItems : [];

                    // Pokud je košík prázdný, pošleme do šablony nuly
                    if ($items === []) {
                        $this->template->products = [];
                        $this->template->total = 0;
                        return;
                    }

                    // Spočítá výskyt každého ID (např. ID 5 je tam 3x)
                    $counts = array_count_values($items);

                    // SQL: Vytáhne z DB jen ty produkty, co jsou v košíku
                    $productFromDb = $this->database->table('product')
                    ->where('id', array_keys($counts))
                    ->fetchAll();

                    $finalItems = [];
                    $total = 0;

                    // Procházíme produkty z DB a doplňujeme k nim počty a výpočty
                    foreach ($productFromDb as $product) {
                        $row = $product->toArray();
                        $productId = (int) $product->id;
                        $quantity = (int) ($counts[$productId] ?? 0);

            // Detekce názvu sloupce pro sklad (podpora různých verzí DB)
            $stockRaw = $row['stock'] ?? $row['in_stock'] ?? $row['qty'] ?? $row['quantity'] ?? null;
            $stock = is_numeric($stockRaw) ? (int) $stockRaw : null;
            
            // Kontrola, zda zákazník nechce víc, než máme na skladě
            $isStockProblem = $stock !== null && $quantity > $stock;
            
            // Výpočet ceny za položku a přičtení do celkové sumy
            $subtotal = $product->price * $quantity;
            $total += $subtotal;
            
            // Vytvoření objektu pro šablonu (hezčí práce v .latte)
            $finalItems[] = (object) [
                'id' => $productId,
                'name' => $product->name,
                'price' => $product->price,
                'color' => $product->color,
                'stock' => $stock,
                'isStockProblem' => $isStockProblem,
                'quantity' => $quantity,
                'subtotal' => $subtotal,
            ];
        }

        // Předání dat do Latte šablony
        $this->template->products = $finalItems;
        $this->template->total = $total;
    }

    /**
     * handleClear: Vymaže celý košík (vysype session)
     */
    public function handleClear(): void
    {
        $session = $this->getSession('cart');
        unset($session->items); // Smazání dat ze session
        $this->flashMessage('Košík byl úspěšně smazán', 'info');
        $this->redirect('this');
    }

    /**
     * handleAdd: Přidá jeden kus produktu (tlačítko PLUS)
     */
    public function handleAdd(int $id): void
    {
        $session = $this->getSession('cart');
        $items = is_array($session->items) ? $session->items : [];

        $items[] = $id; // Přidání ID do pole
        $session->items = $items;

        // Pokud voláno AJAXem, překreslí jen snippet (tabulku)
        if ($this->isAjax()) {
            $this->redrawControl('cartTable');
        } else {
            $this->redirect('this');
        }
    }

    /**
     * handleRemove: Odebere jeden kus produktu (tlačítko MINUS)
     */
    public function handleRemove(int $id): void
    {
        $session = $this->getSession('cart');
        $items = is_array($session->items) ? $session->items : [];

        // Najde pozici ID v poli a smaže ji
        $key = array_search($id, $items, true);
        if ($key !== false) {
            array_splice($items, (int) $key, 1);
            $session->items = $items;
        }

        // AJAX podpora pro bleskovou změnu
        if ($this->isAjax()) {
            $this->redrawControl('cartTable');
        } else {
            $this->redirect('this');
        }
    }

    /**
     * handleCheckout: Zpracování objednávky (uložení do DB a odečet skladu)
     */
    public function handleCheckout(): void
    {
        $session = $this->getSession('cart');
        $items = is_array($session->items) ? $session->items : [];
    
        if ($items === []) {
            $this->flashMessage('Košík je prázdný.', 'warning');
            $this->redirect('this');
        }
    
        // Spočítání kusů a načtení cen z DB
        $counts = array_count_values($items);
        $productsFromDb = $this->database->table('product')
            ->where('id', array_keys($counts))
            ->fetchAll();
    
        $totalAmount = 0.0;
        foreach ($productsFromDb as $product) {
            $totalAmount += ((float) $product->price) * ($counts[$product->id] ?? 0);
        }
    
        // SQL: Vytvoření hlavní objednávky
        $order = $this->database->table('orders')->insert([
            'customer_name' => 'Anonymní zákazník',
            'email' => 'test@test.cz',
            'total_price' => $totalAmount,
            'status' => 'new',
            'created_at' => new \DateTime(),
        ]);
    
        $orderId = $order->id;
    
        // SQL: Uložení položek objednávky + Odečet ze skladu v tabulce produktů
        foreach ($productsFromDb as $product) {
            $qty = (int) $counts[$product->id];
            $this->database->table('order_items')->insert([
                'order_id' => $orderId,
                'product_id' => $product->id,
                'quantity' => $qty,
                'price' => $product->price,
            ]);
            
            // SQL: Aktualizace počtu kusů na skladě
            $product->update(['quantity' => $product->quantity - $qty]);
        }
    
        unset($session->items); // Vyprázdnění košíku po nákupu
        
        // Přesměrování na děkovací stránku
        $this->redirect('done', ['id' => $orderId]);
    }

    /**
     * renderDone: Zobrazení potvrzení o objednávce
     */
    public function renderDone(int $id): void
    {
        // SQL: Načte detaily objednávky z DB pro zobrazení zákazníkovi
        $order = $this->database->table('orders')->get($id);

        if (!$order) {
            $this->error('Objednávka nebyla nalezena.');
        }

        $this->template->order = $order;
    }

    /**
     * handleUpdateQuantity: Ruční přepis čísla v košíku (AJAX)
     */
    public function handleUpdateQuantity(int $id, int $quantity = 1): void
    {
        $session = $this->getSession('cart');
        $items = is_array($session->items) ? $session->items : [];

        // Vymaže všechny staré výskyty produktu a nahradí je novým počtem
        $items = array_filter($items, fn($itemId) => $itemId !== $id);

        for ($i = 0; $i < $quantity; $i++) {
            $items[] = $id;
        }

        $session->items = $items;
        
        // Překreslení snippetu (bez blikání stránky)
        if ($this->isAjax()) {
            $this->redrawControl('cartTable');
        } else {
            $this->redirect('this');
        }
    }
}