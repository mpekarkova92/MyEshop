<?php declare(strict_types=1);

namespace App\Presentation\Accessory;

use Latte\Extension;


final class LatteExtension extends Extension
{
	/** @return array<string, callable> */
	public function getFilters(): array
	{
		return [
			// filtry...
		];
	}

	/** @return array<string, callable> */
	public function getFunctions(): array
	{
		return [
			// funkce...
		];
	}
}
