<?php

use Illuminate\Support\Facades\Route;
use Modules\JangKeyte\src\Http\Controllers\JangKeyteController;

Route::get('/switch-language/{language}', [JangKeyteController::class, 'switchLanguage'])->name('switch_language');