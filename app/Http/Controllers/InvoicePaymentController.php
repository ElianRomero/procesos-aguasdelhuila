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

    public function createOrReuseLink(Request $request, string $refpago)
    {
        $invoice = Invoice::where('refpago', $refpago)->firstOrFail();

        // Si ya existe un link activo (no expirado), reutilizar
        if ($invoice->isPaymentLinkActive()) {
            return redirect()->away($invoice->payment_link_url);
        }

        // Generar NUEVO link con expiración de 30 minutos
        // Usa tus credenciales del .env:
        // WOMPI_PRIVATE_KEY, WOMPI_PUBLIC_KEY, WOMPI_ENV=https://production.wompi.co o sandbox
        $wompiBase = config('services.wompi.base_url', 'https://production.wompi.co'); // o https://sandbox.wompi.co
        $privateKey = config('services.wompi.private_key'); // Bearer
        $currency = 'COP';

        // Genera una referencia única por intento
        $reference = 'INV-' . $invoice->refpago . '-' . Str::upper(Str::random(6));

        // Expira en 30 minutos
        $expiresAt = now()->addMinutes(30);

        // Construye el payload según tu endpoint de Payment Links.
        // Si usas el endpoint de "Payment Links API", ajusta campos a tu implementación actual.
        $payload = [
            'name' => "Pago factura " . $invoice->refpago,
            'description' => "Factura de {$invoice->nombre} - {$invoice->direccion}",
            'amount_in_cents' => (int) $invoice->valfactura, // ya guardado en centavos
            'currency' => $currency,
            'reference' => $reference,
            'single_use' => true,
            'expires_at' => $expiresAt->toIso8601String(),
            // Opcional: customer data
            'customer_data' => [
                'full_name' => $invoice->nombre,
            ],
            // Opcional: redirect/back URLs si tu flujo lo usa
            // 'redirect_url' => route('pago.show', $invoice->refpago),
        ];

        try {
            $resp = Http::withToken($privateKey)
                ->acceptJson()
                ->post($wompiBase . '/v1/payment_links', $payload);

            if (!$resp->successful()) {
                Log::error('Wompi payment_link error', ['status' => $resp->status(), 'body' => $resp->json()]);
                return back()->with('error', 'No fue posible generar el enlace de pago. Intenta más tarde.');
            }

            $data = $resp->json();
            // Ajusta según respuesta real de tu integración actual
            $paymentLinkUrl = data_get($data, 'data.payment_link_url') ?? data_get($data, 'data.url');

            if (!$paymentLinkUrl) {
                Log::error('Wompi respuesta sin URL', ['data' => $data]);
                return back()->with('error', 'No fue posible generar el enlace de pago.');
            }

            // Guardar en DB
            $invoice->update([
                'payment_link_url' => $paymentLinkUrl,
                'expires_at' => $expiresAt,
                'wompi_reference' => $reference,
                'status' => 'pendiente',
            ]);

            return redirect()->away($paymentLinkUrl);

        } catch (\Throwable $e) {
            Log::error('Excepción al crear payment_link Wompi: ' . $e->getMessage());
            return back()->with('error', 'Error interno al generar el enlace de pago.');
        }
    }

    // Webhook (notificación Wompi)
    public function webhook(Request $request)
    {
        // Verifica la firma si tu integración lo requiere
        $event = $request->all();
        Log::info('Wompi webhook recibido', $event);

        $reference = data_get($event, 'data.transaction.reference');
        $status = data_get($event, 'data.transaction.status'); // APPROVED, DECLINED, VOIDED, ERROR

        if (!$reference) {
            return response()->json(['ok' => true]);
        }

        $inv = Invoice::where('wompi_reference', $reference)->first();
        if (!$inv) {
            return response()->json(['ok' => true]);
        }

        if ($status === 'APPROVED') {
            $inv->update(['status' => 'pagada']);
        } elseif (in_array($status, ['DECLINED', 'VOIDED', 'ERROR'])) {
            // Si quieres marcar expirada o cancelada aquí, depende de tu lógica
            $inv->update(['status' => 'cancelada']);
        }

        return response()->json(['ok' => true]);
    }
}
