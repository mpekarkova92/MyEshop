<?php declare(strict_types=1);

namespace App\Presentation\Admin;

use Nette;
use Nette\Database\Explorer;

final class AdminPresenter extends Nette\Application\UI\Presenter
{
    public function __construct(
        private Explorer $database,
    ) {}

    public function renderDefault(): void
    {
        $this->template->orders = $this->database->table('orders')
            ->order('created_at DESC');
    }

    /**
     * Tímto natvrdo řekneme Nette: "Šablonu hledej v té samé složce, kde jsem já"
     */
    public function formatTemplateFiles(): array
    {
        return [__DIR__ . '/default.latte'];
    }
}