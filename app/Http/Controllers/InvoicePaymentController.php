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
    // 👇 Pon este helper dentro del mismo controlador (fuera del método)
    private function appendPublicKeyToCheckoutUrl(string $url): string
    {
        $pub = config('services.wompi.public_key');
        if (!$pub)
            return $url;

        if (str_starts_with($url, 'https://checkout.wompi.co/')) {
            $query = parse_url($url, PHP_URL_QUERY);
            if (!$query || !str_contains($query, 'public-key=')) {
                $sep = $query ? '&' : '?';
                $url .= $sep . 'public-key=' . $pub;
            }
        }
        return $url;
    }

    private function normalizeCheckoutUrl(array $responseData): string
    {
        $id = data_get($responseData, 'data.id');  // <- preferimos SIEMPRE el id
        $url = data_get($responseData, 'data.url')
            ?: data_get($responseData, 'data.payment_link_url')
            ?: '';

        if ($id) {
            $url = 'https://checkout.wompi.co/l/' . $id;
        } else {
            if (preg_match('#/l/([A-Za-z0-9\-_]+)#', $url, $m)) {
                $id = $m[1];
                $url = 'https://checkout.wompi.co/l/' . $id;
            }
        }

        if (!$url || !str_contains($url, '/l/')) {
            Log::error('Wompi no retornó id de link de pago', ['data' => $responseData]);
            throw new \RuntimeException('No fue posible generar el enlace de pago (sin id).');
        }

        return $this->appendPublicKeyToCheckoutUrl($url);
    }


    public function createOrReuseLink(Request $request, string $refpago)
    {
        $invoice = Invoice::where('refpago', $refpago)->firstOrFail();

        // ⚠️ No cobrar montos <= 0
        if ($invoice->valfactura <= 0) {
            return back()->with('error', 'Esta factura tiene saldo cero/negativo. No es cobrable.');
        }

        $wompiBase = rtrim(config('services.wompi.base_url', 'https://sandbox.wompi.co'), '/'); // Sandbox/Prod según .env
        $privateKey = config('services.wompi.private_key');
        $currency = 'COP';

        // Reusar link activo, pero primero validar remotamente que siga "active"
        if ($invoice->payment_link_url && $invoice->expires_at && now()->lt($invoice->expires_at)) {
            // Extrae el {id} de .../l/{id}
            if (preg_match('#/l/([A-Za-z0-9\-_]+)#', $invoice->payment_link_url, $m)) {
                $id = $m[1];
                $health = Http::retry(1, 200)->timeout(10)->get($wompiBase . '/v1/payment_links/' . $id);
                if ($health->successful()) {
                    $info = $health->json();
                    $active = (bool) data_get($info, 'data.active');
                    $mpk = data_get($info, 'data.merchant_public_key'); // debería venir
                    if ($active && $mpk) {
                        // ✅ Link aún sirve, reusar
                        return redirect()->away('https://checkout.wompi.co/l/' . $id);
                    }
                }
            }
            // Si no está activo o no se pudo leer, seguimos y generamos uno nuevo
        }

        $name = $this->cleanStr("Pago factura " . $invoice->refpago, 64);
        $description = $this->cleanStr("Factura de {$invoice->nombre} - {$invoice->direccion}", 180);

        // Wompi pide expiración en **UTC** (ISO-8601 está OK). :contentReference[oaicite:2]{index=2}
        $expiresAtUtc = now()->utc()->addMinutes(30)->toIso8601String();

        $http = Http::withToken($privateKey)
            ->acceptJson()->asJson()
            ->retry(2, 300)->timeout(15);

        // Hasta 2 intents por posible choque de reference
        for ($attempt = 1; $attempt <= 2; $attempt++) {
            $reference = 'INV-' . $invoice->refpago . '-' . Str::upper(Str::random(6));

            $payload = [
                'name' => $name,
                'description' => $description,
                'single_use' => true,
                'collect_shipping' => false,           // requerido por API de Payment Links :contentReference[oaicite:3]{index=3}
                'currency' => $currency,
                'amount_in_cents' => (int) $invoice->valfactura,
                'reference' => $reference,
                'expires_at' => $expiresAtUtc,
                'redirect_url' => route('pago.show', ['refpago' => $invoice->refpago]),
            ];

            try {
                $resp = $http->post($wompiBase . '/v1/payment_links', $payload);
                if ($resp->successful()) {
                    $data = $resp->json();

                    // 🔑 Toma SIEMPRE el id de la respuesta y construye /l/{id}
                    $id = data_get($data, 'data.id');
                    if (!$id) {
                        Log::error('Wompi: respuesta sin id', ['data' => $data]);
                        return back()->with('error', 'No fue posible generar el enlace de pago (sin id).');
                    }

                    // ✅ Verifica salud del link recién creado (debe traer merchant_public_key y active=true)
                    $health = Http::retry(1, 200)->timeout(10)->get($wompiBase . '/v1/payment_links/' . $id);
                    if (!$health->successful()) {
                        Log::error('Wompi: GET payment_links/:id falló', ['id' => $id, 'status' => $health->status(), 'body' => $health->json()]);
                        return back()->with('error', 'No fue posible validar el enlace de pago.');
                    }
                    $info = $health->json();
                    $mpk = data_get($info, 'data.merchant_public_key');
                    $active = (bool) data_get($info, 'data.active');

                    if (!$mpk || !$active) {
                        Log::error('Wompi: link sin merchant_public_key o inactivo', ['info' => $info]);
                        return back()->with('error', 'El enlace de pago no quedó activado. Intenta de nuevo.');
                    }

                    $paymentLinkUrl = 'https://checkout.wompi.co/l/' . $id;

                    // Guarda en tu zona horaria
                    $invoice->update([
                        'payment_link_url' => $paymentLinkUrl,
                        'expires_at' => \Illuminate\Support\Carbon::parse($expiresAtUtc)->setTimezone(config('app.timezone')),
                        'wompi_reference' => $reference,
                        'status' => 'pendiente',
                    ]);

                    return redirect()->away($paymentLinkUrl);
                }

                // 409/422 → valida mensajes (422 = INPUT_VALIDATION_ERROR) :contentReference[oaicite:4]{index=4}
                if (in_array($resp->status(), [409, 422])) {
                    $body = $resp->json();
                    $messages = data_get($body, 'error.messages', []);
                    $flatMsg = is_array($messages) ? implode(' | ', collect($messages)->flatten()->all()) : ($messages ?: '');
                    if (stripos($flatMsg, 'reference') !== false) {
                        Log::warning('Wompi: reference conflict, retrying', ['messages' => $messages]);
                        continue; // intenta con nueva reference
                    }
                    $firstField = is_array($messages) ? array_key_first($messages) : null;
                    $firstErr = $firstField && isset($messages[$firstField][0]) ? $messages[$firstField][0] : null;
                    return back()->with('error', $firstErr ?: ($flatMsg ?: 'Error de validación con Wompi.'));
                }

                Log::error('Wompi payment_link error', ['status' => $resp->status(), 'body' => $resp->json()]);
                return back()->with('error', 'No fue posible generar el enlace de pago en este momento. Intenta de nuevo.');

            } catch (\Throwable $e) {
                Log::error('Excepción Wompi', ['e' => $e->getMessage(), 'attempt' => $attempt]);
                if ($attempt >= 2) {
                    return back()->with('error', 'Error interno al generar el enlace de pago.');
                }
            }
        }

        return back()->with('error', 'No fue posible generar el enlace de pago. Intenta más tarde.');
    }




    // Webhook (notificación Wompi)
    public function webhook(Request $request)
    {
        // 1) Lee body RAW tal como llegó (se usa para la firma)
        $raw = $request->getContent();
        $payload = json_decode($raw, true) ?? [];

        // 2) Log mínimo para depurar (¡no loguees datos sensibles en prod!)
        Log::info('Wompi webhook hit', [
            'event' => data_get($payload, 'event'),
            'id' => data_get($payload, 'data.transaction.id'),
            'ref' => data_get($payload, 'data.transaction.reference'),
            'status' => data_get($payload, 'data.transaction.status'),
            'env' => data_get($payload, 'environment'),
        ]);

        // 3) Valida firma (recomendado por Wompi)
        if (!$this->verifyWompiSignature($request, $payload)) {
            Log::warning('Wompi webhook: invalid signature');
            return response('invalid signature', 400);
        }

        // 4) Acepta sólo evento de transacción
        if (data_get($payload, 'event') !== 'transaction.updated') {
            return response('ignored', 200);
        }

        $tx = data_get($payload, 'data.transaction', []);
        $txId = data_get($tx, 'id');
        $txRef = data_get($tx, 'reference');
        $txStatus = data_get($tx, 'status'); // APPROVED | DECLINED | VOIDED | ERROR | PENDING
        $amount = (int) data_get($tx, 'amount_in_cents');

        // 5) Busca la factura
        $invoice = Invoice::where('wompi_reference', $txRef)->first();

        if (!$invoice && $txRef) {
            // Si tu reference es tipo INV-<refpago>-XXXX, extraemos REFPAGO
            if ($refpago = $this->extractRefpagoFromReference($txRef)) {
                $invoice = Invoice::where('refpago', $refpago)->first();
            }
        }

        if (!$invoice) {
            Log::error('Wompi webhook: invoice not found for reference', ['reference' => $txRef]);
            return response('ok', 200); // responde 200 igual para que Wompi no reintente infinitamente
        }

        // 6) Idempotencia: si ya está pagada, no hagas nada
        if ($invoice->status === 'pagada' && $txStatus === 'APPROVED') {
            return response('ok', 200);
        }

        // 7) Aplica el mapeo de estado
        $newStatus = match ($txStatus) {
            'APPROVED' => 'pagada',
            'DECLINED' => 'rechazada',
            'VOIDED' => 'anulada',
            'ERROR' => 'error',
            default => 'pendiente', // PENDING u otros
        };

        // 8) Guarda campos útiles de la transacción
        $invoice->wompi_transaction_id = $txId;
        $invoice->wompi_status = $txStatus;
        $invoice->wompi_amount_in_cents = $amount;

        if ($newStatus === 'pagada') {
            $invoice->status = 'pagada';
            $invoice->paid_at = now();
        } else {
            $invoice->status = $newStatus;
        }

        $invoice->save();

        // 9) SIEMPRE responde 200 rápido (Wompi reintenta si no ve 200). :contentReference[oaicite:1]{index=1}
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
        $timestamp = (string) data_get($payload, 'timestamp'); // UNIX
        if (!is_array($props) || $timestamp === '') {
            return false;
        }

        // Extrae los valores en el orden indicado por properties (siempre del objeto "data")
        $concat = '';
        foreach ($props as $path) {
            // properties vienen como "transaction.id", "transaction.status", etc. dentro de data
            $value = data_get($payload, 'data.' . $path);
            if ($value === null) {
                return false;
            }
            $concat .= (string) $value;
        }
        $concat .= $timestamp . $secret;

        $computed = strtoupper(hash('sha256', $concat));
        $fromHdr = strtoupper((string) $request->header('X-Event-Checksum', ''));
        $fromBody = strtoupper((string) data_get($payload, 'signature.checksum', ''));

        // Acepta si coincide con header o con signature.checksum del body
        return $computed && ($computed === $fromHdr || $computed === $fromBody);
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
