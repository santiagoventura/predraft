<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DraftController;
use App\Http\Controllers\LeagueController;
use App\Http\Controllers\LeagueScoringController;
use App\Http\Controllers\PlayerDataController;
use App\Http\Controllers\RankingsController;
use Illuminate\Support\Facades\Route;

// Authentication routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Protected routes
Route::middleware('auth.check')->group(function () {
    Route::get('/', function () {
        return redirect()->route('leagues.index');
    });

    // League routes
    Route::resource('leagues', LeagueController::class);

    // League Scoring routes
    Route::get('/leagues/{league}/scoring', [LeagueScoringController::class, 'index'])->name('leagues.scoring.index');
    Route::get('/leagues/{league}/scoring/edit', [LeagueScoringController::class, 'edit'])->name('leagues.scoring.edit');
    Route::put('/leagues/{league}/scoring', [LeagueScoringController::class, 'update'])->name('leagues.scoring.update');
    Route::post('/leagues/{league}/scoring/preset', [LeagueScoringController::class, 'applyPreset'])->name('leagues.scoring.preset');
    Route::post('/leagues/{league}/scoring/calculate', [LeagueScoringController::class, 'calculateScores'])->name('leagues.scoring.calculate');

    // Player Rankings routes
    Route::get('/leagues/{league}/rankings', [RankingsController::class, 'index'])->name('rankings.index');

    // Draft routes
    Route::get('/drafts', [DraftController::class, 'index'])->name('drafts.index');
    Route::get('/leagues/{league}/drafts/create', [DraftController::class, 'create'])->name('drafts.create');
    Route::post('/leagues/{league}/drafts', [DraftController::class, 'store'])->name('drafts.store');
    Route::get('/drafts/{draft}', [DraftController::class, 'show'])->name('drafts.show');
    Route::post('/drafts/{draft}/start', [DraftController::class, 'start'])->name('drafts.start');
    Route::get('/drafts/{draft}/recommendations', [DraftController::class, 'recommendations'])->name('drafts.recommendations');
    Route::post('/drafts/{draft}/pick', [DraftController::class, 'makePick'])->name('drafts.pick');
    Route::post('/drafts/{draft}/revert', [DraftController::class, 'revertPick'])->name('drafts.revert');
    Route::post('/drafts/{draft}/simulate', [DraftController::class, 'simulate'])->name('drafts.simulate');
    Route::delete('/drafts/{draft}', [DraftController::class, 'destroy'])->name('drafts.destroy');

    // Player Data Management routes
    Route::get('/admin/player-data', [PlayerDataController::class, 'index'])->name('admin.player-data.index');
    Route::post('/admin/player-data/start-update', [PlayerDataController::class, 'startUpdate'])->name('admin.player-data.start-update');
    Route::get('/admin/player-data/progress', [PlayerDataController::class, 'getProgress'])->name('admin.player-data.progress');
});

