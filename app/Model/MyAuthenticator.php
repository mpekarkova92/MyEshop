<?php 

namespace App\Model;

use Nette;
use Nette\Security\Passwords;
use Nette\Database\Explorer;

class MyAuthenticator implements Nette\Security\Authenticator
{
    // 1. Tady musíme Nette říct: "Potřebuji databázi a nástroj na hesla"
    public function __construct(
        private Explorer $database,
        private Passwords $passwords,
    ) {
    }

    public function authenticate(string $username, string $password): Nette\Security\SimpleIdentity
    {
        // 2. Tady musíme výsledek uložit do proměnné $row a použít ->fetch()
        $row = $this->database->table('users')
            ->where('email', $username)
            ->fetch(); // fetch() vrátí jeden řádek, fetchAll() by vrátil pole všech

        // Pokud uživatel neexistuje
        if (!$row) {
            throw new Nette\Security\AuthenticationException('Uživatel nenalezen.');
        }

        // 3. Pozor na název: $this->passwords (máš tam množné číslo v konstruktoru)
        if (!$this->passwords->verify($password, $row->password)) {
            throw new Nette\Security\AuthenticationException('Špatné heslo.');
        }

        // Vše ok, vrátíme identitu
        return new Nette\Security\SimpleIdentity(
            $row->id,
            $row->role,
            ['email' => $row->email, 'name' => $row->full_name]
        );
    }
}