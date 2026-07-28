<?php

use App\Http\Controllers\InvoiceCheckoutTestController;
use App\Http\Controllers\InvoicePaymentController;
use App\Models\Invoice;

test('checkout creates a different reference for every payment attempt', function () {
    $invoice = new Invoice(['refpago' => '6761061']);
    $method = new ReflectionMethod(InvoiceCheckoutTestController::class, 'buildPaymentReference');
    $controller = new InvoiceCheckoutTestController();

    $first = $method->invoke($controller, $invoice);
    $second = $method->invoke($controller, $invoice);

    expect($first)
        ->toMatch('/^FACTURA-6761061-[A-Z0-9]{10}$/')
        ->not->toBe($second);
});

test('webhook can recover refpago from both wompi reference formats', function (string $reference) {
    $method = new ReflectionMethod(InvoicePaymentController::class, 'extractRefpagoFromReference');

    expect($method->invoke(new InvoicePaymentController(), $reference))->toBe('6761061');
})->with([
    'payment link' => 'INV-6761061-A1B2C3',
    'checkout' => 'FACTURA-6761061-Z9Y8X7W6V5',
]);
