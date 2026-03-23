<?php declare(strict_types=1);

namespace App\Presentation\Admin;

use Nette;
use Nette\Database\Explorer;

final class AdminPresenter extends Nette\Application\UI\Presenter
{
    public function __construct(private Explorer $database) {}

    public function renderDefault(): void
    {
        $this->template->orders = $this->database->table('orders')->order('created_at DESC');
    }

    public function formatTemplateFiles(): array
    {
        return [__DIR__ . '/default.latte'];
    }

    protected function beforeRender(): void
    {
        parent::beforeRender();
        
        // Košík
        $session = $this->getSession('cart');
        $this->template->cartCount = isset($session->items) ? count($session->items) : 0;

        // Statistiky
        if ($this->getUser()->isInRole('admin')) {
            $orders = $this->database->table('orders');
            $this->template->stats = [
                'today' => (clone $orders)->where('created_at >= ?', new \DateTime('today'))->sum('total_price') ?: 0,
                'week'  => (clone $orders)->where('created_at >= ?', new \DateTime('-7 days'))->sum('total_price') ?: 0,
                'month' => (clone $orders)->where('created_at >= ?', new \DateTime('-1 month'))->sum('total_price') ?: 0,
                'year'  => (clone $orders)->where('created_at >= ?', new \DateTime('-1 year'))->sum('total_price') ?: 0,
            ];
            $this->template->newOrdersCount = (clone $orders)->where('status', 'new')->count();
            $this->template->lowStockCount = $this->database->table('product')->where('quantity < ?', 5)->count();
        }
    }
}