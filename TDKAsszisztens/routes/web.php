<?php

use App\Http\Controllers\ChatController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\MessageController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return auth()->check() ? redirect()->route('chat.index') : Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', fn () => redirect()->route('chat.index'))
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('chat', [ChatController::class, 'index'])->name('chat.index');
    Route::post('chat', [MessageController::class, 'store'])->name('conversations.store');
    Route::get('chat/{conversation}', [ChatController::class, 'show'])->name('chat.show');
    Route::post('chat/{conversation}/messages', [MessageController::class, 'store'])->name('messages.store');
    Route::delete('chat/{conversation}', [ConversationController::class, 'destroy'])->name('conversations.destroy');
    Route::post('documents', [DocumentController::class, 'store'])->name('documents.store');
    Route::delete('documents/{filename}', [DocumentController::class, 'destroy'])->name('documents.destroy');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
