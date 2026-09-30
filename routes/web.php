<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\GeographyController as AdminGeographyController;
use App\Http\Controllers\Admin\LoginController as AdminLoginController;
use App\Http\Controllers\Admin\RecordController as AdminRecordController;
use App\Http\Controllers\GeographyController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\VoterRecordController;
use App\Http\Middleware\NoIndex;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public landing
|--------------------------------------------------------------------------
|
| The root serves the public TMG Ambassadors Space microsite. Registration
| itself remains restricted to signed-in operators.
|
*/
Route::get('/', LandingController::class)->name('home');

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])->name('login.store');
});

Route::post('logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('register', RegisterController::class)->name('register');

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
});

/*
|--------------------------------------------------------------------------
| Operations console
|--------------------------------------------------------------------------
|
| Mounted on an unguessable path (config('admin.path'), rotate with
| ADMIN_PATH) behind its own `admin` guard. It is intentionally never
| linked from the public site and never listed in robots.txt.
|
*/
Route::prefix(config('admin.path'))
    ->middleware(NoIndex::class)
    ->name('admin.')
    ->group(function () {
        Route::middleware('guest:admin')->group(function () {
            Route::get('login', [AdminLoginController::class, 'create'])->name('login');
            Route::post('login', [AdminLoginController::class, 'store'])
                ->middleware('throttle:'.config('admin.throttle.login'))
                ->name('login.store');
        });

        Route::middleware(['auth:admin', 'throttle:'.config('admin.throttle.pages')])->group(function () {
            Route::get('/', DashboardController::class)->name('dashboard');
            Route::get('data', [DashboardController::class, 'data'])->name('data');
            Route::get('records', [AdminRecordController::class, 'index'])->name('records');
            Route::get('geography', [AdminGeographyController::class, 'index'])->name('geography');
            Route::post('logout', [AdminLoginController::class, 'destroy'])->name('logout');
        });
    });
