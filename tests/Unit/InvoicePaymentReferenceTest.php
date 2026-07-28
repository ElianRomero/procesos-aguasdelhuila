<?php

use App\Http\Controllers\InvoiceCheckoutTestController;
use App\Http\Controllers\InvoicePaymentController;
use App\Models\Invoice;
use App\Models\SimpleInvoice;

test('checkout creates a different reference for every payment attempt', function () {
    $invoice = new Invoice(['refpago' => '6761061']);
    $method = new ReflectionMethod(InvoiceCheckoutTestController::class, 'buildPaymentReference');
    $controller = new InvoiceCheckoutTestController;

    $first = $method->invoke($controller, $invoice);
    $second = $method->invoke($controller, $invoice);

    expect($first)
        ->toMatch('/^FACTURA-6761061-[A-Z0-9]{10}$/')
        ->not->toBe($second);
});

test('public payment creates a recognizable and unique reference for every attempt', function () {
    $invoice = new Invoice(['refpago' => '4833']);
    $method = new ReflectionMethod(InvoicePaymentController::class, 'buildPaymentReference');
    $controller = new InvoicePaymentController;

    $first = $method->invoke($controller, $invoice);
    $second = $method->invoke($controller, $invoice);

    expect($first)
        ->toMatch('/^FACTURA-4833-[A-Z0-9]{10}$/')
        ->not->toBe($second);
});

test('simplified payment sends only refpago as the Wompi reference', function () {
    $invoice = new SimpleInvoice(['refpago' => '111122']);
    $method = new ReflectionMethod(InvoicePaymentController::class, 'buildPaymentReference');

    expect($method->invoke(new InvoicePaymentController, $invoice))->toBe('111122');
});

test('public checkout only activates with matching Wompi environments', function () {
    $method = new ReflectionMethod(InvoicePaymentController::class, 'hasValidCheckoutConfig');
    $controller = new InvoicePaymentController;

    expect($method->invoke($controller, 'pub_prod_123', 'prod_integrity_123', 'COP'))->toBeTrue()
        ->and($method->invoke($controller, 'pub_test_123', 'test_integrity_123', 'COP'))->toBeTrue()
        ->and($method->invoke($controller, 'pub_prod_123', 'test_integrity_123', 'COP'))->toBeFalse()
        ->and($method->invoke($controller, 'pub_prod_123', '', 'COP'))->toBeFalse();
});

test('webhook can recover refpago from both wompi reference formats', function (string $reference) {
    $method = new ReflectionMethod(InvoicePaymentController::class, 'extractRefpagoFromReference');

    expect($method->invoke(new InvoicePaymentController, $reference))->toBe('6761061');
})->with([
    'payment link' => 'INV-6761061-A1B2C3',
    'checkout' => 'FACTURA-6761061-Z9Y8X7W6V5',
]);
