<?php 

namespace App\Presentation\Sign;

use Nette;
use Nette\Application\UI\Form;

class SignPresenter extends Nette\Application\UI\Presenter
{
    public function __construct(
        private Nette\Database\Explorer $database,
        private Nette\Security\Passwords $password,

    ) {
        parent::__construct();
    }

    protected function createComponentSignInForm(): Form
    {
        $form = new Form;

        $form->addText('email', 'Email:')
        ->setRequired('Zadejte prosím email.');

        $form->addPassword('password', 'Heslo:')
        ->setRequired('Zadejte prosím heslo.');

        $form->addSubmit('send', 'Přihlásit se');

        $form->onSuccess[] = [$this, 'signFormSucceeded'];
        return $form;
    }

    public function signFormSucceeded(Form $form, \stdClass $values): void
    {
        try {
            //Nette automaticky použije MyAuthenticator
            $this->getUser()->login($values->email, $values->password);
            $this->redirect('Admin:default'); // Po úspěchu do administrace

        } catch (Nette\Security\AuthenticationException $e) {
            $form->addError('Nesprávné přihlašovací údaje');
        }
    }

    public function actionOut(): void
    {
        $this->getUser()->logout();
        $this->flashMessage('Byli jste odhlášeni');
        $this->redirect('sign:in');
    }

    protected function createComponentRegisterForm(): Form
    {
        $form = new Form;
        
        $form->addText('full_name', 'Jméno:')
        ->setRequired('Zadejte prosím Vaše jméno');

        $form->addEmail('email', 'E-mail:')
        ->setRequired('Zadejete prosím Váš email.');

        $form->addPassword('password', 'Heslo:')
        ->setRequired('Zadejte prosím heslo.')
        ->addRule(Form::MIN_LENGTH, 'Heslo musí obsahovat 6 znaků.', 6);

        $form->addSubmit('send', 'Zaregistrovat se');

        $form->onSuccess[] = [$this, 'registerFormSucceeded'];
        return $form;
    }

    public function registerFormSucceeded(Form $form, \stdClass $values): void
    {
        try {
            // Použití Exploreru přímo v presenteru (pro zjednodušení) - jinak v Modelu 
            $this->database->table('users')->insert([
                'full_name' => $values->full_name,
                'email' => $values->email,
                'password' => $this->password->hash($values->password), // Zašifrované heslo 
                'role' => 'customer', // Výchozí role
            ]);
            
            $this->flashMessage('Registrace byla úspěšně provedena, nyní se můžete přihlásit.', 'success');
            $this->redirect('Sign:in');

        } catch (Nette\Database\UniqueConstraintViolationException $e) {
            $form->addError('Tento e-mail je již zaregistrovaný');
        }
    }
}