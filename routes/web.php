<?php

use App\Http\Controllers\AdminExpedientesController;
use App\Http\Controllers\AdminPostulacionesController;
use App\Http\Controllers\AdminPostulacionesDocsController;
use App\Http\Controllers\EmbedController;
use App\Http\Controllers\InvoiceImportController;
use App\Http\Controllers\InvoicePaymentController;
use App\Http\Controllers\InvoicePaymenttController;
use App\Http\Controllers\NoticiaController;
use App\Http\Controllers\ProponentesCertificadosController;
use App\Http\Controllers\ObservacionController;
use App\Http\Controllers\ParametrosContratoController;
use App\Http\Controllers\ProcesoController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProponenteController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PostulacionController;
use App\Http\Controllers\VentanasObservacionesController;
use Illuminate\Http\Request;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use App\Http\Controllers\ProponentesOldController;


Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});
Route::middleware(['auth', 'can:isProponente'])->group(function () {

    Route::get('/proponente/crear', [ProponenteController::class, 'create'])->name('proponente.create');
    Route::post('/proponente', [ProponenteController::class, 'store'])->name('proponente.store');
    Route::put('/proponente/{proponente}', [ProponenteController::class, 'update'])->name('proponente.update');

    Route::get('/mis-postulaciones', [PostulacionController::class, 'index'])->name('postulaciones.index');

    Route::post('/postulaciones/{codigo}', [PostulacionController::class, 'store'])
        ->name('postulaciones.store');



    Route::delete('/procesos/{codigo}/postulaciones/{proponente}', [PostulacionController::class, 'destroy'])->name('postulaciones.destroy');

    Route::get('/postulaciones/{codigo}/archivos', [PostulacionController::class, 'archivosForm'])
        ->name('postulaciones.archivos.form');

    Route::delete('/postulaciones/{codigo}/{proponente}', [PostulacionController::class, 'destroy'])
        ->name('postulaciones.destroy');

    Route::post('/postulaciones/{codigo}/archivos', [PostulacionController::class, 'archivosStore'])
        ->name('postulaciones.archivos.store');


    Route::get('/postulaciones/{codigo}/archivos/{key}', [PostulacionController::class, 'archivoShow'])
        ->name('postulaciones.archivos.show');
});

Route::middleware(['auth', 'can:isAdmin'])->group(function () {

    Route::get('/procesos/crear', [ProcesoController::class, 'create'])->name('procesos.create');
    Route::post('/procesos', [ProcesoController::class, 'store'])->name('procesos.store');
    Route::get('/procesos/{codigo}/edit', [ProcesoController::class, 'edit'])->name('procesos.edit');
    Route::put('/procesos/{codigo}', [ProcesoController::class, 'update'])->name('procesos.update');

    Route::delete('/procesos/{proceso}', [ProcesoController::class, 'destroy'])
        ->name('procesos.destroy');
    Route::post('/procesos/{codigo}/asignar-proponente', [ProcesoController::class, 'asignarProponente'])->name('procesos.asignarProponente');
    Route::post('/procesos/{codigo}/postulaciones/{proponente}/estado', [PostulacionController::class, 'cambiarEstado'])->name('postulaciones.cambiarEstado');

    Route::get('/admin/postulaciones', [AdminPostulacionesController::class, 'index'])
        ->name('admin.postulaciones.index');

    Route::post('/procesos/{codigo}/postulaciones/{proponente}/estado', [AdminPostulacionesController::class, 'cambiarEstado'])
        ->name('postulaciones.cambiarEstado');

    Route::get('/admin/proponentes/{proponente}/documentos', [AdminPostulacionesController::class, 'documentos'])
        ->name('proponentes.documentos');

    Route::get('/proponentes/{proponente}/archivo/{path}', [AdminPostulacionesController::class, 'verArchivo'])
        ->where('path', '.*')
        ->name('proponentes.archivo')
        ->middleware('signed');

    Route::get('/admin/proponentes/{proponente}', [AdminPostulacionesController::class, 'show'])
        ->name('proponentes.show');


    Route::get('/procesos/{proceso:codigo}/observaciones', [ObservacionController::class, 'index'])
        ->name('procesos.observaciones.index');

    Route::get('/admin/observaciones', [ObservacionController::class, 'adminIndex'])
        ->name('admin.observaciones.index');

    Route::get('/observaciones/ventanas', [VentanasObservacionesController::class, 'index'])
        ->name('observaciones.ventanas.index');


    Route::put('/procesos/{proceso:codigo}/observaciones/ventana', [VentanasObservacionesController::class, 'update'])
        ->name('observaciones.ventanas.update');

    Route::patch('/observaciones/{observacion}/estado', [ObservacionController::class, 'actualizarEstado'])
        ->name('observaciones.actualizarEstado');


    Route::prefix('admin/parametros')->name('parametros.')->group(function () {
        Route::get('/', [ParametrosContratoController::class, 'index'])->name('index');
        Route::post('/store', [ParametrosContratoController::class, 'store'])->name('store');
        Route::put('/{entidad}/{id}', [ParametrosContratoController::class, 'update'])->name('update');
        Route::delete('/{entidad}/{id}', [ParametrosContratoController::class, 'destroy'])->name('destroy');
    });
});


Route::middleware(['auth'])->group(function () {
    Route::get('/procesos/{proceso:codigo}/observaciones/nueva', [ObservacionController::class, 'create'])
        ->name('procesos.observaciones.create');

    Route::post('/procesos/{proceso:codigo}/observaciones', [ObservacionController::class, 'store'])
        ->name('procesos.observaciones.store');

    Route::get('/observaciones/{observacion}/archivos/{archivo}', [ObservacionController::class, 'downloadArchivo'])
        ->name('observaciones.archivos.download');

    Route::get('/mis-observaciones', [ObservacionController::class, 'myIndex'])
        ->name('mis.observaciones.index');


    Route::get('/observaciones/{observacion}/editar', [ObservacionController::class, 'edit'])
        ->name('observaciones.edit');

    Route::patch('/observaciones/{observacion}', [ObservacionController::class, 'update'])
        ->name('observaciones.update');

    Route::delete('/observaciones/{observacion}/archivos/{archivo}', [ObservacionController::class, 'destroyArchivo'])
        ->name('observaciones.archivos.destroy');
});




/*
 |— VerNoticias-----------------------------------------------------------------------------------------------------------------------------
*/

Route::middleware('auth')->post(
    '/procesos/{proceso}/noticias/{noticia}/leer',
    [NoticiaController::class, 'marcarLeida']
)->name('procesos.noticias.leer');

/* ——— Público (sin login): solo noticias públicas del proceso ——— */
Route::get('/public/procesos/{proceso}/noticias', [NoticiaController::class, 'index'])
    ->name('public.procesos.noticias.index');

/* ——— Autenticado (proponente/admin): ven lo visible para el usuario ——— */
Route::middleware('auth')->group(function () {
    Route::get('/procesos/{proceso}/noticias', [NoticiaController::class, 'index'])
        ->name('procesos.noticias.index');

    Route::get('/procesos/{proceso}/noticias/{noticia}', [NoticiaController::class, 'show'])
        ->name('procesos.noticias.show');
});

/* ——— Solo ADMIN ——— */
// Global (listado DataTables)
Route::middleware(['auth', 'can:isAdmin'])->group(function () {
    Route::get('/admin/noticias', [NoticiaController::class, 'adminNoticiasIndex'])
        ->name('admin.noticias.index');

    Route::get('/admin/noticias/data', [NoticiaController::class, 'adminNoticiasData'])
        ->name('admin.noticias.data');

    // Crear noticia global (eliges el proceso en el formulario)
    Route::get('/admin/noticias/create', [NoticiaController::class, 'adminCreate'])
        ->name('admin.noticias.create');

    Route::post('/admin/noticias', [NoticiaController::class, 'adminStore'])
        ->name('admin.noticias.store');

    // Auxiliares AJAX
    Route::get('/admin/procesos/buscar', [NoticiaController::class, 'adminProcesosSearch'])
        ->name('admin.procesos.buscar');

    Route::get('/admin/procesos/{proceso}/proponentes', [NoticiaController::class, 'adminProponentesByProceso'])
        ->name('admin.procesos.proponentes');

    Route::get('/admin/facturas/importar', [InvoiceImportController::class, 'form'])->name('invoices.import.form');
    Route::post('/admin/facturas/importar', [InvoiceImportController::class, 'import'])->name('invoices.import');

    Route::get('/proponentes/old/crear', [ProponentesOldController::class, 'create'])
        ->name('proponentes.old.create');

    Route::post('/proponentes/old', [ProponentesOldController::class, 'store'])
        ->name('proponentes.old.store');

});

Route::middleware(['auth', 'can:isProponente'])->group(function () {
    Route::get('/mi/noticias', [NoticiaController::class, 'misNoticias'])
        ->name('proponente.noticias.index');
});

// Responder (crear comentario)
Route::middleware('auth')->post(
    '/procesos/{proceso}/noticias/{noticia}/comentarios',
    [NoticiaController::class, 'comentariosStore']
)->name('procesos.noticias.comentarios.store');

// Borrar comentario (dueño o admin)
Route::middleware('auth')->delete(
    '/procesos/{proceso}/noticias/{noticia}/comentarios/{comentario}',
    [NoticiaController::class, 'comentariosDestroy']
)->name('procesos.noticias.comentarios.destroy');
// Rutas protegidas para ver/descargar adjuntos
Route::middleware('auth')->group(function () {
    // Adjuntos de la NOTICIA
    Route::get(
        '/procesos/{proceso}/noticias/{noticia}/archivos/{archivo}',
        [NoticiaController::class, 'verArchivoNoticia']
    )->name('procesos.noticias.archivos.ver');

    // Adjuntos de COMENTARIOS
    Route::get(
        '/procesos/{proceso}/noticias/{noticia}/comentarios/{comentario}/archivos/{archivo}',
        [NoticiaController::class, 'verArchivoComentario']
    )->name('procesos.noticias.comentarios.archivos.ver');
});

/*
 |— Ver procesos------------------------------------------------------------------------------------------------------------------------------
*/

Route::middleware(['auth', 'can:isAdmin'])->prefix('backoffice')->group(function () {
    // Vista única con DataTables
    Route::get('/expedientes', [AdminExpedientesController::class, 'index'])
        ->name('bo.expedientes.grid');

    // JSON para DataTables (pares proponente-proceso con ≥1 doc)
    Route::get('/expedientes/data', [AdminExpedientesController::class, 'data'])
        ->name('bo.expedientes.data');

    // JSON de documentos por proponente + ?proceso=CODIGO (mismo controlador)
    Route::get('/proponentes/{proponente}/docs', [AdminExpedientesController::class, 'docs'])
        ->name('bo.expedientes.docs');

    Route::get('/proponentes/{proponente}/registros', [AdminExpedientesController::class, 'docs'])
        ->name('bo.expedientes.archivos');
    // Stream/inline del archivo privado firmado
    Route::get('/proponentes/{proponente}/stream/{path}', [AdminExpedientesController::class, 'stream'])
        ->where('path', '.*')
        ->middleware('signed')
        ->name('bo.expedientes.stream');



});


/*
 |—-----------------------------------------------------------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {
    Route::get('/proponentes/certificados', [ProponentesCertificadosController::class, 'index'])
        ->name('proponentes.certificados.index');

    Route::get('/proponentes/certificados/data', [ProponentesCertificadosController::class, 'data'])
        ->name('proponentes.certificados.data'); // ponla ANTES de la dinámica por claridad

    Route::get('/proponentes/{id}/certificado', [ProponentesCertificadosController::class, 'download'])
        ->name('proponentes.certificados.download');
});




Route::get('/embed/procesos', [EmbedController::class, 'index'])->name('embed.procesos');



/*
 |—Pasarela de pagos WOMPI-----------------------------------------------------------------------------------------------------------------------------
*/


// Público / cliente:
// web.php o api.php
Route::get('/pago/buscar', [InvoicePaymentController::class, 'searchForm'])->name('pago.search.form');
Route::get('/pago', [InvoicePaymentController::class, 'search'])->name('pago.search');
Route::get('/pago/{refpago}', [InvoicePaymentController::class, 'show'])->name('pago.show');
Route::post('/wompi/webhook', [InvoicePaymentController::class, 'webhook'])->name('wompi.webhook');



require __DIR__ . '/auth.php';
