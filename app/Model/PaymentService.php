<?php declare(strict_types=1);

namespace App\Model;

final class PaymentService
{

    // Tady budou klíče od Gopay
    private string $merchantId = '123456789';

    /**
     * Metoda vygeneruje URL, na kterou přesměruje zákazníka
     */
    public function createPaymentUrl(int $orderID, float $amount): string
    {
        // Simulace vygenerování odkazu na bránu (API)
        // V reálu by tu byla URL platební brány, my jen přesměrujeme zpět do aplikace
        return  "http://localhost/muj-eshop/www/cart/payment-success?id={$orderID}";
    }
}