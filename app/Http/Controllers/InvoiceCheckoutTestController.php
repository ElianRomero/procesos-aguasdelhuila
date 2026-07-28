<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class InvoiceCheckoutTestController extends Controller
{
    public function searchForm()
    {
        return view('payments_checkout.search');
    }

    public function search(Request $request)
    {
        $ref = trim((string) $request->query('refpago'));

        if ($ref === '') {
            return redirect()->route('pago.checkout.search.form')
                ->with('error', 'Ingresa un REFPAGO');
        }

        return redirect()->route('pago.checkout.show', ['refpago' => $ref]);
    }

    public function show(string $refpago)
    {
        $invoice = Invoice::where('refpago', $refpago)->first();

        if (!$invoice) {
            return view('payments_checkout.not-found', compact('refpago'));
        }

        $vencida = $this->invoiceExpired($invoice);

        return view('payments_checkout.show', compact('invoice', 'vencida'));
    }

    public function createCheckoutUrl(Request $request, string $refpago)
    {
        $invoice = Invoice::where('refpago', $refpago)->firstOrFail();

        if ($invoice->status === 'pagada') {
            return back()->with('ok', 'Esta factura ya fue pagada. Gracias.');
        }

        if ($this->invoiceExpired($invoice)) {
            return back()->with('error', 'Esta factura esta vencida y ya no se puede pagar.');
        }

        if ($invoice->valfactura <= 0) {
            return back()->with('error', 'Esta factura tiene saldo cero o negativo. No es cobrable.');
        }

        $publicKey = (string) config('services.wompi.public_key', '');
        $integritySecret = (string) config('services.wompi.integrity_secret', '');
        $currency = (string) config('services.wompi.currency', 'COP');
        $redirectUrl = (string) config(
            'services.wompi.redirect_url',
            route('pago.checkout.show', ['refpago' => $invoice->refpago])
        );
        $checkoutBase = rtrim((string) config('services.wompi.checkout_url', 'https://checkout.wompi.co/p/'), '/');

        if (!$this->hasValidCheckoutConfig($publicKey, $integritySecret, $currency)) {
            return back()->with('error', 'La configuracion de Wompi Checkout no esta completa o mezcla ambientes distintos.');
        }

        $amountInCents = (int) $invoice->valfactura * 100;
        $reference = $this->buildPaymentReference($invoice);
        $expiresAtUtc = now()->utc()->addMinutes(30)->toIso8601String();
        $signature = hash('sha256', $reference . $amountInCents . $currency . $expiresAtUtc . $integritySecret);

        $params = array_filter([
            'public-key' => $publicKey,
            'currency' => $currency,
            'amount-in-cents' => $amountInCents,
            'reference' => $reference,
            'signature:integrity' => $signature,
            'redirect-url' => $redirectUrl,
            'expiration-time' => $expiresAtUtc,
            'customer-data:email' => $this->resolveCustomerEmail($invoice),
            'customer-data:full-name' => $this->cleanStr($invoice->nombre ?: ('Pagador factura ' . $invoice->refpago), 80),
            'customer-data:phone-number' => $this->resolveCustomerPhone($invoice),
            'customer-data:legal-id' => $invoice->codigo ?: null,
            'customer-data:legal-id-type' => $invoice->codigo ? 'CC' : null,
        ], static fn ($value) => $value !== null && $value !== '');

        $checkoutUrl = $checkoutBase . '/?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);

        $invoice->update([
            'payment_link_url' => $checkoutUrl,
            'wompi_link_id' => null,
            'wompi_reference' => $reference,
            'wompi_amount_in_cents' => $amountInCents,
            'wompi_status' => null,
            'wompi_transaction_id' => null,
            'expires_at' => Carbon::parse($expiresAtUtc)->setTimezone(config('app.timezone')),
            'status' => 'pendiente',
        ]);

        Log::info('Wompi checkout test url creada', [
            'invoice_id' => $invoice->id,
            'refpago' => $invoice->refpago,
            'wompi_reference' => $reference,
            'amount_in_cents' => $amountInCents,
            'expires_at' => $expiresAtUtc,
        ]);

        return redirect()->away($checkoutUrl);
    }

    private function invoiceExpired(Invoice $invoice): bool
    {
        if (!$invoice->fecha) {
            return false;
        }

        return now()->startOfDay()->gt(Carbon::parse($invoice->fecha)->endOfDay());
    }

    private function cleanStr(?string $value, int $max = 140): string
    {
        $value = $value ?? '';
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $value);

        if (!mb_check_encoding($value, 'UTF-8')) {
            $value = @mb_convert_encoding($value, 'UTF-8', 'Windows-1252, ISO-8859-1, UTF-8');
        }

        $value = @iconv('UTF-8', 'UTF-8//IGNORE', $value) ?: '';
        $value = trim(preg_replace('/\s{2,}/', ' ', $value));

        return mb_strimwidth($value, 0, $max, '', 'UTF-8');
    }

    private function hasValidCheckoutConfig(string $publicKey, string $integritySecret, string $currency): bool
    {
        if ($publicKey === '' || $integritySecret === '' || $currency === '') {
            return false;
        }

        $isTestPublic = str_starts_with($publicKey, 'pub_test_');
        $isProdPublic = str_starts_with($publicKey, 'pub_prod_');
        $isTestIntegrity = str_starts_with($integritySecret, 'test_integrity_');
        $isProdIntegrity = str_starts_with($integritySecret, 'prod_integrity_');

        return !(
            ($isTestPublic && !$isTestIntegrity) ||
            ($isProdPublic && !$isProdIntegrity)
        );
    }

    private function buildPaymentReference(Invoice $invoice): string
    {
        return 'FACTURA-' . $invoice->refpago . '-' . Str::upper(Str::random(10));
    }

    private function resolveCustomerEmail(Invoice $invoice): ?string
    {
        return filter_var($invoice->direccion, FILTER_VALIDATE_EMAIL) ? $invoice->direccion : null;
    }

    private function resolveCustomerPhone(Invoice $invoice): ?string
    {
        if (!$invoice->direccion) {
            return null;
        }

        preg_match('/(\+?\d[\d\s]{6,})/', $invoice->direccion, $matches);

        return $matches[1] ?? null;
    }
}
