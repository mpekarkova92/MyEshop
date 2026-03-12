<?php declare(strict_types=1);

namespace App\Presentation\Home;
use App\Model\Product;
use Nette\Database\Explorer;
use Nette;


final class HomePresenter extends Nette\Application\UI\Presenter
{
	public function renderDefault(): void
	{
		// Vytáhneme řádek z tabulky product
		$rows = $this->database->table('product')->fetchAll();

		$products = [];
		foreach ($rows as $row) {
			// Každý řádek převedeme na objekt Product
			$products[] = new Product(
				$row->name,
				(float) $row->price,
				$row->color,
			);
		}

		// Pošleme do šablony
		$this->template->products = $products;
	}

	public function calculateDiscount(float $price): float
	{
		return $price * 0.9;
	}

	public function __construct(
		private Explorer $database,
	) {

	}


}
