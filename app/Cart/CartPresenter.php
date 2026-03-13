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
        $items = $session->items ?? [];

        // Pokud v košíki nic není, pošleme do šablony prázdné hodnoty
        if(empty($items)) {
            $this->template->products = [];
            $this->template->total = 0;
            return;
        }

        // Dotaz do DB: Dej mi produkty, které mají ID v tomto seznamu 
        // SQL SELECT * FROM product WHERE id IN (1, 5, 8)
        $products = $this->database->table('product')
            ->where('id', $items)
            ->fetchAll();

        // Výpočet celkové ceny nákupu 
        $total = 0;
        foreach($products as $product){
            $total += $product->price;
        }

        // Předání dat do Latte šablony
        $this->template->products = $products; // Seznam produktů 
        $this->template->total = $total; // Výsledná suma
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
}
