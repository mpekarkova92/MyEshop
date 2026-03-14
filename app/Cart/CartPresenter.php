<?php declare(strict_types=1);

namespace App\Presentation\Cart;

use Nette;
use Nette\Database\Explorer;

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
            $productId = (int) $product->id;
            $quantity = $counts[$productId];
            $subtotal = $product->price * $quantity;
            $total += $subtotal;
            
            $finalItems[] = (object) [
                'id' => $productId,
                'name' => $product->name,
                'price' => $product->price,
                'color' => $product->color,
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
        $items = $session->items;

        if (!is_array($items)) {
            $items = [];
        }

        $items[] = $id;
        $session->items = $items;

        $this->redirect('this');
    }

    /**
     * Signál pro odebrání jednoho kusu produktu z košíku (tlačítko minus)
     */
    public function handleRemove(int $id): void
    {
        $session = $this->getSession('cart');
        $items = $session->items;

        // Zajistíme, že máme pole
        if (!is_array($items)) {
            $items = [];
        }

        // Najdeme pozici (klíč)
        $key = array_search($id, $items, true);

        // Pokud jsme ho našli, "vystřihneme" jeden prvek na dané pozici
        if ($key !== false) {
            array_splice($items, (int) $key, 1);
            $session->items = $items;
        }

        $this->redirect('this');
    }
}
