<?php declare(strict_types=1);

/* Říkáme v jaké "krabici" je tento soubor */   
namespace App\Model;


/* Model produktu - reprezentuje jeden produkt v eshopu */
class Product {
    public string $name;
    public float $price;
    public ?string $color;

    /* Konstruktor pro inicializaci produktu */
    public function __construct(string $name, float $price, ?string $color = null) {
        $this->name = $name;
        $this->price = $price;
        $this->color = $color;
    }


}