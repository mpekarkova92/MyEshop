<?php declare(strict_types=1);

/* Říkáme v jaké "krabici" je tento soubor */   
namespace App\Model;


/* Model produktu - reprezentuje jeden produkt v eshopu */
class Product {
    public int $id;
    public string $name;
    public float $price;
    public ?string $color;
    public ?string $description;

    /* Konstruktor pro inicializaci produktu */
    public function __construct(int $id, string $name, float $price, ?string $color = null, ?string $description = null) {
        $this->id = $id;
        $this->name = $name;
        $this->price = $price;
        $this->color = $color;
        $this->description = $description;
    }


}