<?php declare(strict_types=1);

namespace App\Presentation\Home;
use App\Model\Product;
use Nette\Database\Explorer;
use Nette;

final class HomePresenter extends Nette\Application\UI\Presenter
{
    public function __construct(private Explorer $database) {}

    public function renderDefault(): void
    {
        $rows = $this->database->table('product')->fetchAll();
        $products = [];
        foreach ($rows as $row) {
            $products[] = new Product(
                $row->id,
                $row->name,
                (float) $row->price,
                $row->color,
                $row->description
            );
        }
        $this->template->products = $products;
    }

    protected function createComponentAddProductForm(): \Nette\Application\UI\Form
    {
        $form = new \Nette\Application\UI\Form;
        $form->addHidden('id'); // Přidáno pro podporu editace
        $form->addText('name', 'Název produktu:')->setRequired();
        $form->addText('price', 'Cena produktu:')->setRequired();
        $form->addText('color', 'Barva produktu:');
        $form->addTextArea('description', 'Popis produktu');
        $form->addSubmit('send', 'Uložit produkt');

        $form->onSuccess[] = function (array $values): void {
            $id = $values['id'];
            unset($values['id']);
            if ($id) {
                $this->database->table('product')->get($id)->update($values);
                $this->flashMessage('Produkt upraven');
            } else {
                $this->database->table('product')->insert($values);
                $this->flashMessage('Produkt přidán');
            }
            $this->redirect('this');
        };
        return $form;
    }

    protected function createComponentDeleteForm(): \Nette\Application\UI\Multiplier
    {
        return new \Nette\Application\UI\Multiplier(function ($productId) {
            $form = new \Nette\Application\UI\Form;
            $form->addSubmit('submit', 'Smazat');
            $form->onSuccess[] = function () use ($productId): void {
                $this->database->table('product')->get($productId)->delete();
                $this->flashMessage('Smazáno');
                $this->redirect('this');
            };
            return $form;
        });
    }

    public function handleAddToCart(int $id): void
    {
        $session = $this->getSession('cart');
        if (!isset($session->items)) { $session->items = []; }
        $session->items[] = $id;
        $this->flashMessage('V košíku!', 'success');
        $this->redirect('this');
    }

    public function handleEdit(int $id): void
    {
        $product = $this->database->table('product')->get($id);
        if ($product) {
            $this['addProductForm']->setDefaults($product);
            $this->flashMessage('Upravujete ' . $product->name);
        }
    }

    protected function beforeRender(): void
    {
        parent::beforeRender();
        $session = $this->getSession('cart');
        $this->template->cartCount = isset($session->items) ? count($session->items) : 0;

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