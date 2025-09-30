<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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
            return redirect()->route('pago.search.form')->with('error', 'Ingresa un REFPAGO');
        }
        return redirect()->route('pago.show', ['refpago' => $ref]);
    }

    public function show(string $refpago)
    {
        $invoice = Invoice::where('refpago', $refpago)->first();

        if (!$invoice) {
            return view('payments.not-found', compact('refpago'));
        }

        return view('payments.show', compact('invoice'));
    }
    // 🔧 Pon este helper dentro del mismo controlador (fuera del método):
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
        if ($invoice->valfactura <= 0) {
            return back()->with('error', 'Esta factura tiene saldo cero/negativo. No es cobrable.');
        }

        $wompiBase = rtrim(config('services.wompi.base_url', 'https://sandbox.wompi.co'), '/');
        $privateKey = config('services.wompi.private_key');
        $currency = 'COP';

        // Dedupe: si hace < 15s ya generaste uno, reenvía a ese y evita lluvia de links
        if ($invoice->wompi_link_id && $invoice->updated_at && now()->diffInSeconds($invoice->updated_at) < 15) {
            return redirect()->away('https://checkout.wompi.co/l/' . $invoice->wompi_link_id);
        }

        $name = $this->cleanStr("Pago factura " . $invoice->refpago, 64);
        $description = $this->cleanStr("Factura de {$invoice->nombre} - {$invoice->direccion}", 180);
        $expiresAtUtc = now()->utc()->addMinutes(30)->toIso8601String();

        $reference = 'INV-' . $invoice->refpago . '-' . Str::upper(Str::random(6));

        $payload = [
            'name' => $name,
            'description' => $description,
            'single_use' => true,
            'collect_shipping' => false,
            'currency' => $currency,
            'amount_in_cents' => (int) $invoice->valfactura,
            'reference' => $reference,
            'expires_at' => $expiresAtUtc,
            'redirect_url' => route('pago.show', ['refpago' => $invoice->refpago]),
        ];

        try {
            $resp = Http::withToken($privateKey)->acceptJson()->asJson()->timeout(15)->post($wompiBase . '/v1/payment_links', $payload);
            if (!$resp->successful()) {
                // 409/422: conflicto de reference u otros
                if (in_array($resp->status(), [409, 422])) {
                    $body = $resp->json();
                    $msgs = data_get($body, 'error.messages', []);
                    $flat = is_array($msgs) ? implode(' | ', collect($msgs)->flatten()->all()) : ($msgs ?: '');
                    return back()->with('error', $flat ?: 'Error de validación con Wompi.');
                }
                Log::error('Wompi payment_link error', ['status' => $resp->status(), 'body' => $resp->json()]);
                return back()->with('error', 'No fue posible generar el enlace de pago.');
            }

            $data = $resp->json();
            $id = data_get($data, 'data.id');                    // <-- ID del link
            if (!$id) {
                Log::error('Wompi: respuesta sin id', ['data' => $data]);
                return back()->with('error', 'No fue posible generar el enlace de pago.');
            }

            // (Opcional) health check: GET /v1/payment_links/:id devuelve active + merchant_public_key
            $health = Http::timeout(10)->get($wompiBase . '/v1/payment_links/' . $id);
            if (!$health->successful() || !data_get($health->json(), 'data.active')) {
                Log::error('Wompi: link recién creado inactivo', ['id' => $id, 'body' => $health->json()]);
                return back()->with('error', 'El enlace no quedó activo. Intenta de nuevo.');
            }

            $invoice->update([
                'payment_link_url' => 'https://checkout.wompi.co/l/' . $id,
                'wompi_link_id' => $id,
                'expires_at' => \Illuminate\Support\Carbon::parse($expiresAtUtc)->setTimezone(config('app.timezone')),
                'wompi_reference' => $reference,
                'status' => 'pendiente',
            ]);

            return redirect()->away('https://checkout.wompi.co/l/' . $id);

        } catch (\Throwable $e) {
            Log::error('Excepción Wompi', ['e' => $e->getMessage()]);
            return back()->with('error', 'Error interno al generar el enlace de pago.');
        }
    }






    // Webhook (notificación Wompi)
    public function webhook(Request $request)
    {
        $raw = $request->getContent();
        $payload = json_decode($raw, true) ?? [];

        Log::info('Wompi webhook hit', [
            'event' => data_get($payload, 'event'),
            'id' => data_get($payload, 'data.transaction.id'),
            'ref' => data_get($payload, 'data.transaction.reference'),
            'status' => data_get($payload, 'data.transaction.status'),
            'env' => data_get($payload, 'environment'),
        ]);

        // 1) Firma (obligatoria)
        if (!$this->verifyWompiSignature($request, $payload)) {
            Log::warning('Wompi webhook: invalid signature');
            return response('invalid signature', 400);
        }

        // 2) Solo transacciones
        if (data_get($payload, 'event') !== 'transaction.updated') {
            return response('ignored', 200);
        }

        $tx = data_get($payload, 'data.transaction', []);
        $txRef = data_get($tx, 'reference');
        $txStatus = data_get($tx, 'status');              // APPROVED|DECLINED|VOIDED|ERROR|PENDING
        $plinkId = data_get($tx, 'payment_link_id') ?: data_get($payload, 'data.payment_link.id');

        // 3) Encontrar invoice por todas las pistas
        $q = \App\Models\Invoice::query();
        if ($txRef) {
            $q->orWhere('wompi_reference', $txRef);
            if ($refpago = $this->extractRefpagoFromReference($txRef)) {
                $q->orWhere('refpago', $refpago);
            }
        }
        if ($plinkId) {
            $q->orWhere('wompi_link_id', $plinkId);
        }
        $invoice = $q->first();

        if (!$invoice) {
            Log::error('Wompi webhook: invoice not found', ['ref' => $txRef, 'plinkId' => $plinkId]);
            return response('ok', 200);
        }

        // 4) Idempotencia
        if ($invoice->status === 'pagada' && $txStatus === 'APPROVED') {
            return response('ok', 200);
        }

        // 5) Mapeo compatible con tu ENUM actual
        $newStatus = match ($txStatus) {
            'APPROVED' => 'pagada',
            'PENDING' => 'pendiente',
            'DECLINED', 'VOIDED', 'ERROR' => 'cancelada',
            default => 'pendiente',
        };

        $invoice->status = $newStatus;         // si tienes paid_at, puedes setearlo aquí
        $invoice->save();

        return response('ok', 200);
    }



    /**
     * Verifica la firma del evento Wompi (X-Event-Checksum / signature.checksum)
     * Doc: concatenar en orden los valores de signature.properties + timestamp + secret y hacer SHA256. :contentReference[oaicite:2]{index=2}
     */
    private function verifyWompiSignature(Request $request, array $payload): bool
    {
        $secret = config('services.wompi.events_secret');
        if (!$secret) {
            Log::error('Wompi: events_secret missing');
            return false;
        }

        $props = data_get($payload, 'signature.properties', []);
        // timestamp entero (preferido por Wompi)
        $timestamp = data_get($payload, 'timestamp');

        // fallback: si no vino timestamp, intenta convertir sent_at a epoch
        if ($timestamp === null) {
            if ($sentAt = data_get($payload, 'sent_at')) {
                try {
                    $timestamp = (string) \Carbon\Carbon::parse($sentAt)->timestamp;
                } catch (\Throwable $e) {
                    $timestamp = null;
                }
            }
        }

        if (!is_array($props) || $timestamp === null || $timestamp === '') {
            Log::warning('Wompi webhook: missing properties/timestamp');
            return false;
        }

        // concatena en orden los campos pedidos dentro de data
        $concat = '';
        foreach ($props as $path) {
            $val = data_get($payload, 'data.' . $path);
            if ($val === null) {
                Log::warning('Wompi webhook: property not found', ['path' => $path]);
                return false;
            }
            $concat .= (string) $val;
        }
        $concat .= (string) $timestamp . $secret;

        $computed = strtoupper(hash('sha256', $concat));
        $fromHdr = strtoupper((string) $request->header('X-Event-Checksum', ''));
        $fromBody = strtoupper((string) data_get($payload, 'signature.checksum', ''));

        if ($computed !== $fromHdr && $computed !== $fromBody) {
            Log::warning('Wompi webhook: checksum mismatch', [
                'computed' => $computed,
                'hdr' => $fromHdr,
                'body' => $fromBody,
            ]);
            return false;
        }
        return true;
    }


    /** Extrae REFPAGO de referencias tipo "INV-<refpago>-XYZ" */
    private function extractRefpagoFromReference(string $ref): ?string
    {
        if (preg_match('/^INV-([A-Za-z0-9]+)-[A-Z0-9]+$/', $ref, $m)) {
            return $m[1];
        }
        return null;
    }
}
