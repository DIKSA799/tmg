<?php

use App\Http\Controllers\Admin\CommunityLinkController as AdminCommunityLinkController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\GeographyController as AdminGeographyController;
use App\Http\Controllers\Admin\LeaderboardController as AdminLeaderboardController;
use App\Http\Controllers\Admin\LoginController as AdminLoginController;
use App\Http\Controllers\Admin\RecordController as AdminRecordController;
use App\Http\Controllers\Admin\SenatorialController as AdminSenatorialController;
use App\Http\Controllers\Admin\SettingsController as AdminSettingsController;
use App\Http\Controllers\Ambassador\AcknowledgementController as AmbassadorAcknowledgementController;
use App\Http\Controllers\Ambassador\AuthController as AmbassadorAuthController;
use App\Http\Controllers\Ambassador\DashboardController as AmbassadorDashboardController;
use App\Http\Controllers\Ambassador\LeaderboardController as AmbassadorLeaderboardController;
use App\Http\Controllers\Ambassador\NominationController as AmbassadorNominationController;
use App\Http\Controllers\Ambassador\NomineeController as AmbassadorNomineeController;
use App\Http\Controllers\Ambassador\PasswordController as AmbassadorPasswordController;
use App\Http\Controllers\Ambassador\PasswordResetController as AmbassadorPasswordResetController;
use App\Http\Controllers\Ambassador\ProfileController as AmbassadorProfileController;
use App\Http\Controllers\Ambassador\VoteController as AmbassadorVoteController;
use App\Http\Controllers\GeographyController;
use App\Http\Controllers\LandingController;
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
| is currently open to the public as well.
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

/*
|--------------------------------------------------------------------------
| Ambassador registration (open)
|--------------------------------------------------------------------------
|
| The registration flow is open to the public for now. To require a signed-in
| operator again, wrap `register`, the `geography` group and `submissions`
| below in Route::middleware('auth')->group(fn () => ...).
|
*/
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

/*
|--------------------------------------------------------------------------
| TMG Ambassadors Space
|--------------------------------------------------------------------------
|
| Ambassadors sign in with their phone number or email and their TMG number
| as the password. Everything below is scoped to the ambassador's own
| senatorial district.
|
*/
Route::prefix('tmgamb')->name('tmgamb.')->group(function () {
    Route::get('/', [AmbassadorAuthController::class, 'create'])->name('login');
    Route::post('/', [AmbassadorAuthController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('login.store');

    Route::get('forgot-password', [AmbassadorPasswordResetController::class, 'create'])
        ->name('password.request');
    Route::post('forgot-password', [AmbassadorPasswordResetController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('password.email');

    Route::post('logout', [AmbassadorAuthController::class, 'destroy'])
        ->middleware('auth:ambassador')
        ->name('logout');

    Route::middleware('auth:ambassador')->group(function () {
        Route::get('dashboard', AmbassadorDashboardController::class)->name('dashboard');
        Route::get('leaderboard', AmbassadorLeaderboardController::class)->name('leaderboard');
        Route::get('profile', [AmbassadorProfileController::class, 'show'])->name('profile');
        Route::post('profile/photo', [AmbassadorProfileController::class, 'updatePhoto'])
            ->middleware('throttle:10,1')
            ->name('profile.photo');
        Route::post('password', [AmbassadorPasswordController::class, 'update'])
            ->middleware('throttle:10,1')
            ->name('password.update');

        Route::get('nominee/{nomination:public_id}', AmbassadorNomineeController::class)->name('nominee');

        Route::post('nominate', [AmbassadorNominationController::class, 'store'])
            ->middleware('throttle:10,1')
            ->name('nominate');
        Route::post('invite/{ambassador}', [AmbassadorNominationController::class, 'invite'])
            ->middleware('throttle:30,1')
            ->name('invite');
        Route::post('invitations/{invitation}/accept', [AmbassadorNominationController::class, 'accept'])
            ->name('invitations.accept');
        Route::post('invitations/{invitation}/reject', [AmbassadorNominationController::class, 'reject'])
            ->name('invitations.reject');

        Route::post('vote/{nomination}', [AmbassadorVoteController::class, 'store'])
            ->middleware('throttle:30,1')
            ->name('vote');

        Route::post('terms/accept', [AmbassadorAcknowledgementController::class, 'acceptTerms'])->name('terms.accept');
        Route::post('notice/seen', [AmbassadorAcknowledgementController::class, 'seenNotice'])->name('notice.seen');
    });
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
            Route::get('leaderboard', [AdminLeaderboardController::class, 'index'])->name('leaderboard');
            Route::get('leaderboard/states', [AdminLeaderboardController::class, 'states'])->name('leaderboard.states');
            Route::get('leaderboard/{nomination}', [AdminLeaderboardController::class, 'show'])->name('leaderboard.show');
            Route::get('records', [AdminRecordController::class, 'index'])->name('records');
            Route::get('records/export', [AdminRecordController::class, 'export'])->name('records.export');
            Route::get('geography', [AdminGeographyController::class, 'index'])->name('geography');
            Route::get('senatorial', [AdminSenatorialController::class, 'index'])->name('senatorial');
            Route::get('settings', [AdminSettingsController::class, 'index'])->name('settings');
            Route::post('settings', [AdminSettingsController::class, 'update'])->name('settings.update');
            Route::post('community-links', [AdminCommunityLinkController::class, 'store'])->name('community-links.store');
            Route::delete('community-links/{communityLink}', [AdminCommunityLinkController::class, 'destroy'])->name('community-links.destroy');
            Route::post('logout', [AdminLoginController::class, 'destroy'])->name('logout');
        });
    });
