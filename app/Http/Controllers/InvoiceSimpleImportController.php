<?php

namespace App\Http\Controllers;

use App\Http\Requests\InvoiceSimpleImportConfirmRequest;
use App\Http\Requests\InvoiceSimpleImportPreviewRequest;
use App\Models\SimpleInvoice;
use App\Services\InvoiceSimpleImportService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use InvalidArgumentException;

class InvoiceSimpleImportController extends Controller
{
    private const CACHE_TTL_MINUTES = 30;

    public function form()
    {
        return view('invoices.import-simple');
    }

    public function index()
    {
        $invoices = SimpleInvoice::orderByDesc('id')->get();

        return view('invoices.simple-index', compact('invoices'));
    }

    public function preview(
        InvoiceSimpleImportPreviewRequest $request,
        InvoiceSimpleImportService $service
    ) {
        try {
            $preview = $service->preview(
                $request->file('file'),
                (string) $request->validated('fecha_limite')
            );
        } catch (InvalidArgumentException $exception) {
            return back()
                ->withInput()
                ->with('error', $exception->getMessage());
        } catch (\Throwable $exception) {
            report($exception);

            return back()
                ->withInput()
                ->with('error', 'No fue posible leer el archivo. Verifica su formato y contenido.');
        }

        $token = (string) Str::uuid();
        Cache::put(
            $this->cacheKey((int) $request->user()->id, $token),
            [
                'valid_rows' => $preview['valid_rows'],
                'summary' => $preview['summary'],
            ],
            now()->addMinutes(self::CACHE_TTL_MINUTES)
        );

        return view('invoices.import-simple-preview', [
            'rows' => $preview['rows'],
            'summary' => $preview['summary'],
            'token' => $token,
            'expiresInMinutes' => self::CACHE_TTL_MINUTES,
        ]);
    }

    public function confirm(
        InvoiceSimpleImportConfirmRequest $request,
        InvoiceSimpleImportService $service
    ) {
        $token = (string) $request->validated('token');
        $batch = Cache::pull($this->cacheKey((int) $request->user()->id, $token));

        if (! is_array($batch)) {
            return redirect()
                ->route('invoices.simple-import.form')
                ->with('error', 'La previsualizacion expiro o ya fue confirmada. Carga el archivo nuevamente.');
        }

        $result = $service->confirm($batch);

        return view('invoices.import-simple-result', compact('result'));
    }

    private function cacheKey(int $userId, string $token): string
    {
        return "invoice-simple-import:{$userId}:{$token}";
    }
}
