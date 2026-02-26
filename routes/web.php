<?php

use App\Http\Controllers\PostController;
use App\Http\Controllers\BoostController;
use App\Http\Controllers\ValidatorController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NotificationController;

Route::get('/', fn() => redirect()->route('login'));

Auth::routes();

// ─── Webhooks N8N (sans authentification, sans CSRF) ─────────
Route::prefix('webhook/n8n')->name('webhook.n8n.')->group(function () {
    Route::post('/boost-created',   [WebhookController::class, 'boostCreated'])->name('boost-created');
    Route::post('/boost-activated', [WebhookController::class, 'boostActivated'])->name('boost-activated');
});

// ─── Routes authentifiées ────────────────────────────────────
Route::middleware(['auth'])->group(function () {

    Route::post('/notifications/{id}/read',  [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all',   [NotificationController::class, 'markAllRead'])->name('notifications.read-all');

    Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

    // Posts
    Route::get('/posts', [PostController::class, 'index'])->name('posts.index');

    // ─── Boost — Opérateur ───────────────────────────────────
    Route::get('/boost/my-requests',     [BoostController::class, 'myRequests'])->name('boost.my-requests');
    Route::get('/boost/create',          [BoostController::class, 'create'])->name('boost.create');
    Route::post('/boost',                [BoostController::class, 'store'])->name('boost.store');
    Route::post('/boost/{boost}/submit', [BoostController::class, 'submit'])->name('boost.submit');

    // ─── Settings (admin only) ───────────────────────────────
    Route::middleware(['role:admin'])->prefix('settings')->name('settings.')->group(function () {
        Route::get('/',           [App\Http\Controllers\SettingsController::class, 'index'])->name('index');
        Route::post('/n8n',       [App\Http\Controllers\SettingsController::class, 'updateN8n'])->name('update-n8n');
        Route::post('/meta',      [App\Http\Controllers\SettingsController::class, 'updateMeta'])->name('update-meta');
        Route::post('/test/n8n',  [App\Http\Controllers\SettingsController::class, 'testN8n'])->name('test-n8n');
        Route::post('/test/meta', [App\Http\Controllers\SettingsController::class, 'testMeta'])->name('test-meta');
    });

    // ─── Boost — Validation N+1 ─────────────────────────────
    // IMPORTANT : routes statiques AVANT la route dynamique /{boost}
    Route::middleware(['role:validator_n1,validator,admin'])->group(function () {
        Route::get('/boost/pending-n1',              [ValidatorController::class, 'pendingN1'])->name('boost.pending-n1');
        Route::post('/boost/{boost}/approve-n1',     [ValidatorController::class, 'approveN1'])->name('boost.approve-n1');
        Route::post('/boost/{boost}/reject-n1',      [ValidatorController::class, 'rejectN1'])->name('boost.reject-n1');
    });

    // ─── Boost — Validation N+2 ─────────────────────────────
    Route::middleware(['role:validator_n2,admin'])->group(function () {
        Route::get('/boost/pending-n2',              [ValidatorController::class, 'pendingN2'])->name('boost.pending-n2');
        Route::post('/boost/{boost}/approve-n2',     [ValidatorController::class, 'approveN2'])->name('boost.approve-n2');
        Route::post('/boost/{boost}/reject-n2',      [ValidatorController::class, 'rejectN2'])->name('boost.reject-n2');
    });

    // ─── Boost — Actions post-N8N (tous validateurs + admin) ─
    Route::middleware(['role:validator_n1,validator_n2,validator,admin'])->group(function () {
        Route::get('/boost/all',                  [ValidatorController::class, 'all'])->name('boost.all');
        Route::post('/boost/{boost}/activate',    [ValidatorController::class, 'activate'])->name('boost.activate');
        Route::post('/boost/{boost}/pause',       [ValidatorController::class, 'pause'])->name('boost.pause');
        Route::post('/boost/{boost}/retry-n8n',   [ValidatorController::class, 'retryN8n'])->name('boost.retry-n8n');
    });

    // Route dynamique en dernier
    Route::get('/boost/{boost}', [BoostController::class, 'show'])->name('boost.show');

});
