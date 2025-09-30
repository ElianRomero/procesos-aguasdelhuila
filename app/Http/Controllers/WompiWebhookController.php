<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Invoice;
use Illuminate\Support\Str;
class WompiWebhookController extends Controller
{
    public function receive(Request $request)
    {
        // A) Decodifica el payload (en tu log venía dentro de "raw")
        $payload = json_decode($request->getContent(), true);
        if (is_array($payload) && isset($payload['raw'])) {
            $payload = json_decode($payload['raw'], true);
        }
        if (!is_array($payload)) {
            Log::warning('Wompi webhook: payload inválido');
            return response()->json(['ok' => true]);
        }

        $tx = data_get($payload, 'data.transaction', []);
        $txId = data_get($tx, 'id');
        $ref = data_get($tx, 'reference'); // p.ej. "test_lA5m25_1759..._xxxxx"
        $desc = data_get($tx, 'payment_method.payment_description')
            ?: data_get($tx, 'payment_description');
        $amount = (int) data_get($tx, 'amount_in_cents');

        // B) Aprobado (sandbox usa payment_method.sandbox_status)
        $status = data_get($tx, 'status'); // en prod
        $sandboxApproved = data_get($tx, 'payment_method.sandbox_status') === 'APPROVED';
        $approved = ($status === 'APPROVED') || $sandboxApproved;

        if (!$approved) {
            Log::info('Wompi webhook: no aprobado', ['tx' => $txId, 'status' => $status]);
            return response()->json(['ok' => true]);
        }

        // C) Idempotencia básica (si ya está esa transacción, salimos)
        $already = Invoice::where('wompi_transaction_id', $txId)->exists();
        if ($already) {
            return response()->json(['ok' => true]);
        }

        // D) Ubicar la factura:
        // D1) por descripción: "Pago factura 48314"
        $invoice = null;
        if ($desc && preg_match('/factura\s+(\d+)/i', $desc, $m)) {
            $invoice = Invoice::where('numero', $m[1])->first();
        }

        // D2) por link_id de la reference (primer tramo antes del "_")
        if (!$invoice && $ref) {
            $linkId = Str::before($ref, '_'); // "test_lA5m25"
            $invoice = Invoice::where('wompi_link_id', $linkId)->first();
        }

        // D3) (opcional) por wompi_reference si lo usas para algo más
        if (!$invoice && $ref) {
            $invoice = Invoice::where('wompi_reference', $ref)->first();
        }

        if (!$invoice) {
            Log::warning('Wompi webhook: no encontré la factura', compact('txId', 'ref', 'desc'));
            return response()->json(['ok' => true]);
        }

        // E) Si ya está pagada, no la toquemos
        if ($invoice->status === 'pagada') {
            return response()->json(['ok' => true]);
        }

        // F) Actualizar la factura con los campos que ya tienes en tu esquema
        $invoice->update([
            'status' => 'pagada',
            'wompi_status' => $status ?: 'APPROVED',
            'wompi_transaction_id' => $txId,
            'wompi_amount_in_cents' => $amount,
            'paid_at' => now(),
        ]);

        Log::info('Wompi webhook: pago aplicado OK', [
            'tx' => $txId,
            'numero' => $invoice->numero,
            'amount' => $amount,
        ]);

        return response()->json(['ok' => true]);
    }
}
