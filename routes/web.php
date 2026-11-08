<?php

use App\Http\Controllers\Document\DocumentController;
use App\Http\Controllers\Question\QuestionController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DocumentController::class, 'index'])->name('documents.index');
Route::post('documents/store', [DocumentController::class, 'store'])
    ->middleware('throttle:document-uploads')
    ->name('documents.store');
Route::delete('documents/{document}', [DocumentController::class, 'destroy'])
    ->name('documents.destroy');
Route::post('questions/store', [QuestionController::class, 'store'])
    ->middleware('throttle:ai-questions')
    ->name('questions.store');
