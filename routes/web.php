<?php

use App\Http\Controllers\AdminPostulacionesController;
use App\Http\Controllers\ProcesoController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProponenteController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PostulacionController;

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

    Route::post('/procesos/{codigo}/asignar-proponente', [ProcesoController::class, 'asignarProponente'])->name('procesos.asignarProponente');
    Route::post('/procesos/{codigo}/postulaciones/{proponente}/estado', [PostulacionController::class, 'cambiarEstado'])->name('postulaciones.cambiarEstado');

    Route::get('/admin/postulaciones', [AdminPostulacionesController::class, 'index'])
        ->name('admin.postulaciones.index');

    Route::post('/procesos/{codigo}/postulaciones/{proponente}/estado', [AdminPostulacionesController::class, 'cambiarEstado'])
        ->name('postulaciones.cambiarEstado');

    Route::get('/admin/proponentes/{proponente}/documentos', [AdminPostulacionesController::class, 'documentos'])
        ->name('proponentes.documentos');


    Route::get('/admin/proponentes/{proponente}', [AdminPostulacionesController::class, 'show'])
        ->name('proponentes.show');
});




require __DIR__ . '/auth.php';
