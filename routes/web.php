<?php

use App\Http\Controllers\AiAssistantController;
use App\Http\Controllers\AiQueryController;
use App\Http\Controllers\KakSubmissionController;
use Illuminate\Support\Facades\Route;

// Redirect home directly to submissions index
Route::get('/', function () {
    return redirect()->route('submissions.index');
});

// AI Assistant & Natural Language Search Routes (Throttle 20 requests per minute)
Route::prefix('kak')->name('kak.')->group(function () {
    Route::post('/assistant/ask', [AiAssistantController::class, 'ask'])
        ->middleware('throttle:20,1')
        ->name('assistant.ask');

    Route::post('/history/ai-search', [AiQueryController::class, 'search'])
        ->middleware('throttle:20,1')
        ->name('history.aiSearch');
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
