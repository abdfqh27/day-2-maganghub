<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\KakSubmissionController;

// Redirect home directly to submissions index
Route::get('/', function () {
    return redirect()->route('submissions.index');
});

// Group routes for KAK Submissions
Route::prefix('submissions')->name('submissions.')->group(function () {
    Route::get('/', [KakSubmissionController::class, 'index'])->name('index');
    Route::get('/create', [KakSubmissionController::class, 'create'])->name('create');
    Route::post('/step', [KakSubmissionController::class, 'storeStep'])->name('storeStep');
    Route::post('/clear-draft', [KakSubmissionController::class, 'clearDraft'])->name('clearDraft');
    Route::post('/finalize', [KakSubmissionController::class, 'finalize'])->name('finalize');
    Route::get('/{submission}', [KakSubmissionController::class, 'show'])->name('show');
    Route::post('/{submission}/regenerate', [KakSubmissionController::class, 'regenerate'])->name('regenerate');
    Route::get('/{submission}/download', [KakSubmissionController::class, 'download'])->name('download');
    Route::delete('/{submission}', [KakSubmissionController::class, 'destroy'])->name('destroy');
});
