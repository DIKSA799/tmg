<?php

use App\Http\Controllers\GeographyController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\VoterRecordController;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingController::class)->name('home');

Route::prefix('geography')->name('geography.')->group(function () {
    Route::get('states', [GeographyController::class, 'states'])->name('states');
    Route::get('states/{state}/lgas', [GeographyController::class, 'lgas'])->name('lgas');
    Route::get('lgas/{lga}/wards', [GeographyController::class, 'wards'])->name('wards');
    Route::get('wards/{ward}/polling-units', [GeographyController::class, 'pollingUnits'])->name('polling-units');

    Route::post('locate', [GeographyController::class, 'locate'])
        ->middleware('throttle:30,1')
        ->name('locate');
});

Route::post('submissions', [VoterRecordController::class, 'store'])
    ->middleware('throttle:20,1')
    ->name('submissions.store');
