# Můj e-shop

Internetový obchod postavený na frameworku [Nette](https://nette.org) – PHP framework pro vývoj webových aplikací.

## O projektu

Jednoduchý e-shop s katalogem produktů, košíkem a správou zboží. Aplikace využívá Nette 3.2, Latte šablony, Nette Database a Bootstrap pro responzivní vzhled.

## Funkce

- **Katalog produktů** – přehled všech produktů na úvodní stránce
- **Detail produktu** – zobrazení produktu s možností přidat do košíku
- **Košík** – správa položek, změna množství (tlačítka +/−), vymazání košíku
- **Přidání produktu** – formulář pro vložení nového produktu
- **Session košík** – položky se ukládají v session

## Technologie

- **PHP** 8.2+
- **Nette** 3.2 (Application, Database, Forms, DI)
- **Latte** 3.1 – šablonovací systém
- **Tracy** – ladění a výpis chyb
- **Bootstrap** – CSS framework
- **PHPStan** – statická analýza kódu
- **Composer** – správa závislostí

## Požadavky

- PHP 8.2 nebo vyšší
- MySQL/MariaDB databáze
- Composer

## Instalace

1. Naklonujte repozitář a nainstalujte závislosti:

   ```bash
   composer install
   ```

2. Vytvořte soubor `config/local.neon` a nastavte připojení k databázi (formát viz [dokumentace Nette Database](https://doc.nette.org/cs/database)):

   ```neon
   database:
       dsn: 'mysql:host=127.0.0.1;dbname=eshop;charset=utf8mb4'
       user: uzivatel
       password: heslo
   ```

3. Importujte databázové schéma (tabulka `product` s poli `id`, `name`, `price`, `color`, `description`).

4. Zajistěte oprávnění k zápisu pro adresáře `temp/` a `log/`.

## Spuštění

Vestavěný PHP server:

```bash
php -S localhost:8000 -t www
```

Poté otevřete v prohlížeči: **http://localhost:8000**

Pro Apache nebo Nginx nastavte virtual host směřující na složku `www/`. Ujistěte se, že adresáře `app/`, `config/`, `log/` a `temp/` nejsou přímo přístupné z webu.

## Struktura projektu

```
app/
├── Cart/              # Košík – CartPresenter, default.latte
├── Core/              # Router
├── Model/             # Modely (Product)
├── Presentation/
│   ├── Home/          # Úvodní stránka, katalog, formuláře
│   ├── Error/         # Chybové stránky (404, 500)
│   └── Accessory/     # LatteExtension
config/                # Konfigurace (common.neon, services.neon, local.neon)
www/                   # Veřejný adresář (index.php, .htaccess)
assets/                # Statické soubory (CSS, JS)
```

## PHPStan

Statická analýza:

```bash
vendor/bin/phpstan analyse
```

## Licence

MIT, BSD-3-Clause, GPL-2.0-only nebo GPL-3.0-only
