<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PublicApiController;
use App\Http\Controllers\WompiWebhookController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| Estas rutas se sirven bajo el prefijo /api y el middleware 'api'
| (sin sesión ni CSRF). Perfectas para DataTables server-side.
*/

Route::get('/procesos', [PublicApiController::class, 'procesos'])

    ->name('api.public.procesos');
Route::post('/wompi/webhook', [WompiWebhookController::class, 'receive']);


// (Opcional) Ping para pruebas rápidas de disponibilidad de API
Route::get('/ping', function () {
    return response()->json(['ok' => true, 'ts' => now()->toISOString()]);
});

