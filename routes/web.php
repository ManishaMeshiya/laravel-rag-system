<?php

use App\Http\Controllers\Document\DocumentController;
use App\Http\Controllers\Question\QuestionController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DocumentController::class, 'index'])->name('documents.index');
Route::post('documents/store', [DocumentController::class, 'store'])->name('documents.store');
Route::post('questions/store', [QuestionController::class, 'store'])->name('questions.store');
