<?php

use Illuminate\Support\Facades\Route;
use Modules\JangKeyte\src\Http\Controllers\Job\JobController;
use Modules\JangKeyte\src\Http\Controllers\Job\UpdateJobController;

Route::group(['middleware' => 'locale'], function() {

    /* Admin Middleware */
    Route::middleware(['auth'])->group(function() {

        // routes for job
        Route::prefix('job')->group(function(){
            Route::get('/', [JobController::class, 'index'])->name('job_index');
            Route::get('/edit/{id?}', [UpdateJobController::class, 'create'])->name('job_edit');
            Route::post('update', [UpdateJobController::class, 'store'])->name('job_update');
            Route::get('delete/{id}', [JobController::class, 'destroy'])->name('job_delete');
        });
    });
});
