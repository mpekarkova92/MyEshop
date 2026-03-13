<?php declare(strict_types=1);

namespace App\Presentation\Home;
use App\Model\Product;
use Nette\Database\Explorer;
use Nette;


/**
 * Hlavní řídící jednotka úvodní stránky
 * 
 * Dědí od Presenteru, což mu dává schopnost ovládat šablony a databázi
 */

final class HomePresenter extends Nette\Application\UI\Presenter
{

	/**
	 * Konstruktor - Nette automaticky dá připojení k databázi DI
	 * Explorer $database je nakonfigurovaná v config/local.neon
	 */
	public function __construct(private Explorer $database) {}

	/**
	 * renderDefault se spusté před vykreslením default.latte
	 * Slouží k přípravě dat pro katalog
	 */
	public function renderDefault(): void
	{
		// SQL: SELECT * FROM product
		$rows = $this->database->table('product')->fetchAll();

		$products = [];
		foreach ($rows as $row) {
			// Transformace databázového řádku na čistý PHP objekt našeho Modelu 
			$products[] = new Product(
				$row->id,
				$row->name,
				(float) $row->price,
				$row->color,
				$row->description
			);
		}

		// Předání pole objektů do Latte šablony. V Latte k nim přistoupíme jako k $products.
		$this->template->products = $products;
	}
 

	public function calculateDiscount(float $price): float
	{
		return $price * 0.9;
	}


	/**
	 * Továrna na formulář. Nette zavolá tuhle metodu, když v šabloně uvidí {form addProductForm}
	 * /** @return Nette\Application\UI\Multiplier<Nette\Application\UI\Form> 
	 */
	protected function createComponentAddProductForm(): \Nette\Application\UI\Form
	{
		$form = new \Nette\Application\UI\Form;

		// Definujeme pole formuláře: Nette hlídá automaticky bezpečnost a datové typy
		$form->addText('name', 'Název produktu:')
			->setRequired('Název produktu je povinný');

		$form->addText('price', 'Cena produktu:')
			->setRequired('number')
			->setRequired('Cena je povinná');

		$form->addText('color', 'Barva produktu:');

		$form->addTextArea('description', 'Popis produktu');

		// Tlačítko pro odeslání formuláře
		$form->addSubmit('send', 'Uložit produkt');

		/**
		 * Callback: co se stane, když uživatel klikne na "Uložit" a vše je správně
		 */
		$form->onSuccess[] = function (array $values): void {
			// INSERT INTO product ... (hodnoty se berou z názvů polí ve formuláři)
			$this->database->table('product')->insert($values);
			$this->flashMessage('Produkt byl úspěšně přidán', 'success');
			$this->redirect('this'); // Přesměrování zabrání pětovnému odeslání pči refresh
		};

		return $form;
	}

	/**
	 * Multiplier umožňuje mít na jedné stránce více stejných formulářů
	 */
	protected function createComponentDeleteForm(): \Nette\Application\UI\Multiplier
	{
		return new \Nette\Application\UI\Multiplier(function ($productId) {
			$form = new \Nette\Application\UI\Form;
			$form->addSubmit('submit', 'Smazat');
	
			$form->onSuccess[] = function () use ($productId): void {
				// DELETE FROM product WHERE id = ...
				$this->database->table('product')->get($productId)->delete();
				$this->flashMessage('Produkt byl odstraněn.', 'info');
				$this->redirect('this');
			};
	
			return $form;
		});
	}

	/**
	 * renderDetail se spustí při zobrazení detailu (např. (home/detail/5))
	 */

	public function renderDetail(int $id): void
	{

		// SELECT * FROM product WHERE id = $id
		$row = $this->database->table('product')->get($id);

		// Pokud produkt neexistuje, vyhodíme chybu
		if (!$row) {
			$this->error('Produkt nebyl nalezen');
		}

		// Pošle data do šablony
		$this->template->product = $row;
	}

	/**
	 * HANDLE (Signál) - Reaguje na kliknutí na n:href="addToCart!"
	 * Nejde o novou stránku, ale o akci nad aktuální stránkou 
	 */

	public function handleAddToCart(int $id): void
	{
		// Session: Krátkodobá paměť serveru spojená s prohlížečem uživatele
		$session = $this->getSession('cart');

		// Pokud v session nic není, vytvoří prázdné  pole
		if (!isset($session->items)) {
			 $session->items = [];
		}

		// Přidáme ID produktu do pole nakoupených věcí
		$session->items[] = $id;

		$this->flashMessage('Produkt byl přidán do košíku', 'success');
		$this->redirect('this');
	}

}
