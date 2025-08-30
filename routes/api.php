
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PublicApiController;

Route::get('/public/procesos', [PublicApiController::class, 'procesos']);
