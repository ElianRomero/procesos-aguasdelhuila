<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProponenteController;
use Illuminate\Support\Facades\Route;

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
Route::middleware(['auth'])->group(function () {
Route::post('/proponente', [ProponenteController::class, 'store'])->name('proponente.store');
Route::put('/proponente/{proponente}', [ProponenteController::class, 'update'])->name('proponente.update');
// Para mostrar el formulario (crear o editar según usuario)
Route::get('/proponente/crear', [ProponenteController::class, 'create'])->name('proponente.create');

});

require __DIR__.'/auth.php';
