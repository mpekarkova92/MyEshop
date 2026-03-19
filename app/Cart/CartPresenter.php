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
     * Konstruktor: Nette sem automaticky předá inejctine připojení k databázi
     */
    public function __construct(
        private Explorer $database,
        private PaymentService $paymentService,
    ) {}

    /**
     * renderDefault: Připravuje data pro zobrazení obsahu košíku 
     */
    public function renderDefault(): void
    {
        // Otevřeme session sekci s názvem 'cart'
        $session = $this->getSession('cart');

        // Vytáhneme seznam ID produktů. pokud je košík prázdný, použijeme prázné hodnoty
        $rawItems = $session->items ?? [];
        $items = is_array($rawItems) ? $rawItems : [];

        // Pokud v košíku nic není, pošleme do šablony prázdné hodnoty
        if ($items === []) {
            $this->template->products = [];
            $this->template->total = 0;
            return;
        }

        // Spočítáme kolikrát je ID v košíku
        $counts = array_count_values($items);

        // Vytáhneme unikátní ID
        $productFromDb = $this->database->table('product')
            ->where('id', array_keys($counts))
            ->fetchAll();

        $finalItems = [];
        $total = 0;

        // Poskládáme si pole, které obsahuje produkt i jeho počet
        foreach ($productFromDb as $product) {
            $row = $product->toArray();
            $productId = (int) $product->id;
            $quantity = (int) ($counts[$productId] ?? 0);

            $stockRaw = $row['stock']
                ?? $row['in_stock']
                ?? $row['qty']
                ?? $row['quantity']
                ?? null;

            $stock = is_numeric($stockRaw) ? (int) $stockRaw : null;
            $isStockProblem = $stock !== null && $quantity > $stock;
            $subtotal = $product->price * $quantity;
            $total += $subtotal;
            
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

        $this->template->products = $finalItems;
        $this->template->total = $total;
    }

    /**
     * Signál pro vymazání celého košíku
     */
    public function handleClear(): void
    {
        $session = $this->getSession('cart');

        // Smažeme celou sekci 'items' v session 
        unset($session->items);

        $this->flashMessage('Košík byl úspěšně smazán', 'info');

        // Přesměrování na stránku
        $this->redirect('this');
    }

    /**
     * Signál pro přidání jednoho kusu produktu do košíku (tlačítko plus)
     */
    public function handleAdd(int $id): void
{
    $session = $this->getSession('cart');
    $items = is_array($session->items) ? $session->items : [];

    $items[] = $id;
    $session->items = $items;

    // AJAX kontrola
    if ($this->isAjax()) {
        $this->redrawControl('cartTable');
    } else {
        $this->redirect('this');
    }

}
    /**
     * Signál pro odebrání jednoho kusu produktu z košíku (tlačítko minus)
     */
    public function handleRemove(int $id): void
    {
        $session = $this->getSession('cart');
        $items = is_array($session->items) ? $session->items : [];

        $key = array_search($id, $items, true);

        if ($key !== false) {
            array_splice($items, (int) $key, 1);
            $session->items = $items;
        }

        // AJAX kontrola
        if ($this->isAjax()) {
            $this->redrawControl('cartTable');
        } else {
            $this->redirect('this');
        }
    }

    public function handleCheckout(): void
    {
        $session = $this->getSession('cart');
        $items = is_array($session->items) ? $session->items : [];
    
        if ($items === []) {
            $this->flashMessage('Košík je prázdný.', 'warning');
            $this->redirect('this');
        }
    
        $counts = array_count_values($items);
        $productsFromDb = $this->database->table('product')
            ->where('id', array_keys($counts))
            ->fetchAll();
    
        $totalAmount = 0.0;
        foreach ($productsFromDb as $product) {
            $totalAmount += ((float) $product->price) * ($counts[$product->id] ?? 0);
        }
    
        // SQL: Vytvoření záznamu v tabulce 'orders'
        $order = $this->database->table('orders')->insert([
            'customer_name' => 'Anonymní zákazník',
            'email' => 'test@test.cz',
            'total_price' => $totalAmount,
            'status' => 'new',
            'created_at' => new \DateTime(),
        ]);
    
        $orderId = $order->id; // Skutečné ID z databáze
    
        // SQL: Uložení jednotlivých položek do 'order_items'
        foreach ($productsFromDb as $product) {
            $qty = (int) $counts[$product->id];
            $this->database->table('order_items')->insert([
                'order_id' => $orderId,
                'product_id' => $product->id,
                'quantity' => $qty,
                'price' => $product->price,
            ]);
            
            // SQL: Odečtení ze skladu
            $product->update(['quantity' => $product->quantity - $qty]);
        }
    
        unset($session->items);
        
        // Přesměrování na novou akci 'done' (vytvoříme v dalším kroku)
        $this->redirect('done', ['id' => $orderId]);
    }

    public function renderDone(int $id): void
    {
        // SQL: Vyhledá objednávku v DB podle ID z adresy
        $order = $this->database->table('orders')->get($id);

        // Kontrola: pokud objednávka neexistuje, vyhodí chybu
        if (!$order) {
            $this->error('Objednávka nebyla nalezena.');
        }

        // Latte: Předá nalezenou objednávku do šablony
        $this->template->order = $order;
    }

    // Přidání počtu produktů v košíku
    public function handleUpdateQuantity(int $id, int $quantity = 1): void
    {
        $session = $this->getSession('cart');
        $items = is_array($session->items) ? $session->items : [];

        // Vymažeme všechny staré výskyty tohoto ID (počet)
        $items = array_filter($items, fn($itemId) => $itemId !== $id);

        // Přidáme ho tam tolikrát, kolik uživatel napsal
        for ($i = 0; $i < $quantity; $i++) {
            $items[] = $id;
        }

        $session->items = $items;
        
        // Pokud je to AJAX, překleslíme jen košík
        if ($this->isAjax()) {
            $this->redrawControl('cartTable'); // Zrychlení (Pošle jen kousek HTML ne celou stránku)
        } else {
            $this->redirect('this');
        }
    }
}
