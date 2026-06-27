<?php

use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Admin\UsageController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportUploadController;
use Illuminate\Support\Facades\Route;

// ─── Authenticated chat ───────────────────────────────────────────────────────

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::get('/', [ChatController::class, 'index'])->name('chat');
    Route::post('/chat/send', [ChatController::class, 'send'])
        ->name('chat.send')
        ->middleware('throttle:chat');

    Route::post('/chat/stream', [ChatController::class, 'stream'])
        ->name('chat.stream')
        ->middleware('throttle:chat');

    // Conversation history
    Route::get('/conversations', [ConversationController::class, 'index'])->name('conversations.index');
    Route::get('/conversations/{conversation}', [ConversationController::class, 'show'])->name('conversations.show');
    Route::delete('/conversations/{conversation}', [ConversationController::class, 'destroy'])->name('conversations.destroy');

    // Report uploads (client users)
    Route::get('/reports', [ReportUploadController::class, 'index'])->name('reports.index');
    Route::post('/reports', [ReportUploadController::class, 'store'])->name('reports.store');
    Route::delete('/reports/{report}', [ReportUploadController::class, 'destroy'])->name('reports.destroy');

    // Profile (Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ─── Admin area ───────────────────────────────────────────────────────────────

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', fn () => redirect()->route('admin.clients.index'))->name('dashboard');
    Route::resource('clients', ClientController::class);
    Route::resource('clients.users', AdminUserController::class)->shallow();
    Route::resource('clients.reports', AdminReportController::class)->shallow()->only(['index', 'destroy']);
    Route::get('usage', [UsageController::class, 'index'])->name('usage');
});

require __DIR__.'/auth.php';
