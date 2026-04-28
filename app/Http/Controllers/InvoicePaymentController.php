<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class InvoicePaymentController extends Controller
{
    public function searchForm()
    {
        return view('payments.search');
    }

    public function search(Request $request)
    {
        $ref = trim((string) $request->query('refpago'));

        if (!$ref) {
            return redirect()->route('pago.search.form')
                ->with('error', 'Ingresa un REFPAGO');
        }

        return redirect()->route('pago.show', ['refpago' => $ref]);
    }

    public function show(string $refpago)
    {
        $invoice = Invoice::where('refpago', $refpago)->first();

        if (!$invoice) {
            return view('payments.not-found', compact('refpago'));
        }

        $vencida = $this->invoiceExpired($invoice);

        return view('payments.show', compact('invoice', 'vencida'));
    }

    private function invoiceExpired(Invoice $invoice): bool
    {
        if (!$invoice->fecha) {
            return false;
        }

        return now()->startOfDay()->gt(Carbon::parse($invoice->fecha)->endOfDay());
    }

    private function cleanStr(?string $s, int $max = 140): string
    {
        $s = $s ?? '';
        $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $s);

        if (!mb_check_encoding($s, 'UTF-8')) {
            $s = @mb_convert_encoding($s, 'UTF-8', 'Windows-1252, ISO-8859-1, UTF-8');
        }

        $s = @iconv('UTF-8', 'UTF-8//IGNORE', $s) ?: '';
        $s = trim(preg_replace('/\s{2,}/', ' ', $s));

        return mb_strimwidth($s, 0, $max, '', 'UTF-8');
    }

    public function createOrReuseLink(Request $request, string $refpago)
    {
        $invoice = Invoice::where('refpago', $refpago)->firstOrFail();

        if ($invoice->status === 'pagada') {
            return back()->with('ok', 'Esta factura ya fue pagada. ¡Gracias!');
        }

        if ($this->invoiceExpired($invoice)) {
            return back()->with('error', 'Esta factura está vencida y ya no se puede pagar.');
        }

        if ($invoice->valfactura <= 0) {
            return back()->with('error', 'Esta factura tiene saldo cero o negativo. No es cobrable.');
        }

        // Reutilizar enlace activo si ya existe
        if (
            $invoice->payment_link_url &&
            $invoice->expires_at &&
            now()->lt($invoice->expires_at) &&
            $invoice->status === 'pendiente'
        ) {
            return redirect()->away($invoice->payment_link_url);
        }

        $wompiBase = rtrim(config('services.wompi.base_url', 'https://sandbox.wompi.co'), '/');
        $privateKey = config('services.wompi.private_key');
        $currency = 'COP';

        $name = $this->cleanStr("Pago factura " . $invoice->refpago, 64);
        $description = $this->cleanStr("Factura " . $invoice->refpago, 180);
        $expiresAtUtc = now()->utc()->addMinutes(30)->toIso8601String();
        $reference = 'INV-' . $invoice->refpago . '-' . Str::upper(Str::random(6));

        $payload = [
            'name' => $name,
            'description' => $description,
            'single_use' => true,
            'collect_shipping' => false,
            'currency' => $currency,
            'amount_in_cents' => (int) $invoice->valfactura * 100,
            'reference' => $reference,
            'expires_at' => $expiresAtUtc,
            'redirect_url' => route('pago.show', ['refpago' => $invoice->refpago]),
        ];

        try {
            $resp = Http::withToken($privateKey)
                ->acceptJson()
                ->asJson()
                ->timeout(15)
                ->post($wompiBase . '/v1/payment_links', $payload);

            if (!$resp->successful()) {
                if (in_array($resp->status(), [409, 422])) {
                    $msgs = data_get($resp->json(), 'error.messages', []);
                    $flat = is_array($msgs)
                        ? implode(' | ', collect($msgs)->flatten()->all())
                        : ($msgs ?: '');

                    return back()->with('error', $flat ?: 'Error de validación con Wompi.');
                }

                Log::error('Wompi payment_link error', [
                    'status' => $resp->status(),
                    'body' => $resp->json(),
                ]);

                return back()->with('error', 'No fue posible generar el enlace de pago.');
            }

            $data = $resp->json();
            $id = data_get($data, 'data.id');

            if (!$id) {
                Log::error('Wompi: respuesta sin id', ['data' => $data]);
                return back()->with('error', 'No fue posible generar el enlace de pago.');
            }

            Log::info('Wompi link creado', [
                'refpago' => $invoice->refpago,
                'wompi_link_id' => $id,
                'wompi_reference' => $reference,
                'amount_in_cents' => (int) $invoice->valfactura,
            ]);

            $health = Http::timeout(10)->get($wompiBase . '/v1/payment_links/' . $id);

            if (!$health->successful() || !data_get($health->json(), 'data.active')) {
                Log::error('Wompi: link recién creado inactivo', [
                    'id' => $id,
                    'body' => $health->json(),
                ]);

                return back()->with('error', 'El enlace no quedó activo. Intenta de nuevo.');
            }

            $invoice->update([
                'payment_link_url' => 'https://checkout.wompi.co/l/' . $id,
                'wompi_link_id' => $id,
                'expires_at' => Carbon::parse($expiresAtUtc)->setTimezone(config('app.timezone')),
                'wompi_reference' => $reference,
                'status' => 'pendiente',
            ]);

            return redirect()->away('https://checkout.wompi.co/l/' . $id);

        } catch (\Throwable $e) {
            Log::error('Excepción Wompi', ['e' => $e->getMessage()]);
            return back()->with('error', 'Error interno al generar el enlace de pago.');
        }
    }

    public function webhook(Request $request)
    {
        Log::info('Webhook ARRIVED', ['raw' => $request->getContent()]);

        $payload = json_decode($request->getContent(), true) ?? [];

        $sigOk = $this->verifyWompiSignature($request, $payload);

        if (!$sigOk) {
            if (app()->environment(['local', 'development', 'testing'])) {
                Log::info('SIGDEBUG: firma inválida pero omitida en local/dev/testing');
            } else {
                Log::info('SIGDEBUG: firma inválida en producción, abortando');
                return response('invalid signature', 400);
            }
        }

        if (data_get($payload, 'event') !== 'transaction.updated') {
            Log::info('SIGDEBUG: evento ignorado', ['event' => data_get($payload, 'event')]);
            return response('ignored', 200);
        }

        $tx = (array) data_get($payload, 'data.transaction', []);
        $txId = (string) data_get($tx, 'id', '');
        $txStatus = strtoupper((string) data_get($tx, 'status', ''));
        $txAmount = (int) data_get($tx, 'amount_in_cents', 0);
        $txRef = (string) data_get($tx, 'reference', '');
        $plinkId = (string) data_get($tx, 'payment_link_id', '');
        $approved = $this->wompiIsApproved($tx);

        Log::info('Webhook TX parsed', [
            'tx_id' => $txId,
            'status' => $txStatus,
            'approved' => $approved,
            'reference' => $txRef,
            'payment_link_id' => $plinkId,
        ]);

        $invoice = $this->findInvoiceFromTx($tx);

        if (!$invoice) {
            Log::info('Webhook: invoice not found', [
                'payment_link_id' => $plinkId,
                'reference' => $txRef,
            ]);
            return response('ok', 200);
        }

        if ($invoice->wompi_transaction_id === $txId && strtoupper((string) $invoice->wompi_status) === $txStatus) {
            return response('ok', 200);
        }

        if ($approved && $invoice->status === 'pagada') {
            return response('ok', 200);
        }

        $newStatus = $approved ? 'pagada' : match ($txStatus) {
            'DECLINED', 'VOIDED', 'ERROR' => 'cancelada',
            'PENDING', '' => 'pendiente',
            default => 'pendiente',
        };

        $invoice->wompi_transaction_id = $txId ?: $invoice->wompi_transaction_id;
        $invoice->wompi_status = $txStatus ?: $invoice->wompi_status;
        $invoice->wompi_amount_in_cents = $txAmount ?: $invoice->wompi_amount_in_cents;
        $invoice->status = $newStatus;

        if ($newStatus === 'pagada' && empty($invoice->paid_at)) {
            $invoice->paid_at = now();
        }

        if (empty($invoice->wompi_link_id) && $plinkId) {
            $invoice->wompi_link_id = $plinkId;
        }

        $invoice->save();

        Log::info('Invoice updated from webhook', [
            'invoice_id' => $invoice->id,
            'status' => $invoice->status,
            'tx_id' => $txId,
            'tx_status' => $txStatus,
            'approved' => $approved,
        ]);

        return response('ok', 200);
    }

    private function verifyWompiSignature(Request $request, array $payload): bool
    {
        $secret = (string) config('services.wompi.events_secret', '');
        $hdr = (string) $request->header('X-Event-Checksum', '');
        $bodySig = (string) data_get($payload, 'signature.checksum', '');
        $props = data_get($payload, 'signature.properties', []);
        $timestamp = data_get($payload, 'timestamp');

        if ($timestamp === null && ($sentAt = data_get($payload, 'sent_at'))) {
            try {
                $timestamp = (string) Carbon::parse($sentAt)->timestamp;
            } catch (\Throwable $e) {
                $timestamp = null;
            }
        }

        if (empty($secret) || !is_array($props) || $timestamp === null || $timestamp === '') {
            return false;
        }

        $concat = '';
        foreach ($props as $path) {
            $val = data_get($payload, 'data.' . $path);
            if ($val === null) {
                return false;
            }
            $concat .= (string) $val;
        }

        $concat .= (string) $timestamp . $secret;

        $computed = strtoupper(hash('sha256', $concat));
        $hdrUp = strtoupper($hdr);
        $bodyUp = strtoupper($bodySig);

        return ($computed === $hdrUp) || ($computed === $bodyUp);
    }

    private function wompiIsApproved(array $tx): bool
    {
        $status = strtoupper((string) data_get($tx, 'status', ''));
        $sandboxStatus = strtoupper((string) data_get($tx, 'payment_method.sandbox_status', ''));
        $finalizedAt = data_get($tx, 'finalized_at');

        return $status === 'APPROVED'
            || ($sandboxStatus === 'APPROVED' && !empty($finalizedAt));
    }

    private function findInvoiceFromTx(array $tx): ?Invoice
    {
        $plinkId = data_get($tx, 'payment_link_id');
        $txRef = (string) data_get($tx, 'reference', '');

        if ($plinkId) {
            if ($inv = Invoice::where('wompi_link_id', $plinkId)->first()) {
                return $inv;
            }
        }

        if ($txRef) {
            if ($inv = Invoice::where('wompi_reference', $txRef)->first()) {
                return $inv;
            }
        }

        if ($refpago = $this->extractRefpagoFromReference($txRef)) {
            if ($inv = Invoice::where('refpago', $refpago)->first()) {
                return $inv;
            }
        }

        return null;
    }

    private function extractRefpagoFromReference(string $ref): ?string
    {
        if (preg_match('/^INV-(.+)-[A-Z0-9]+$/', $ref, $m)) {
            return $m[1];
        }

        return null;
    }
}