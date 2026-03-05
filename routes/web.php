<?php

use App\Http\Controllers\PostController;
use App\Http\Controllers\BoostController;
use App\Http\Controllers\ValidatorController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SyncRunController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CampaignController;

Route::get('/', fn() => redirect()->route('login'));

Auth::routes();

// ─── Webhooks N8N (sans authentification, sans CSRF) ─────────
Route::prefix('webhook/n8n')->name('webhook.n8n.')->group(function () {
    Route::post('/boost-created',    [WebhookController::class, 'boostCreated'])->name('boost-created');
    Route::post('/boost-activated',  [WebhookController::class, 'boostActivated'])->name('boost-activated');
    Route::post('/campaign-done',    [CampaignController::class, 'n8nCallback'])->name('campaign-done');
});

// ─── Routes authentifiées ────────────────────────────────────
Route::middleware(['auth'])->group(function () {

    Route::post('/notifications/{id}/read',  [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all',   [NotificationController::class, 'markAllRead'])->name('notifications.read-all');

    Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

    // ─── Profil utilisateur ──────────────────────────────────
    Route::get('/profile',                [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile/info',           [ProfileController::class, 'updateInfo'])->name('profile.update-info');
    Route::put('/profile/password',       [ProfileController::class, 'updatePassword'])->name('profile.update-password');
    Route::post('/profile/avatar',        [ProfileController::class, 'updateAvatar'])->name('profile.update-avatar');

    // Posts
    Route::get('/posts', [PostController::class, 'index'])->name('posts.index');

    // ─── Campaigns Media Buyer ────────────────────────────────
    Route::get('/campaigns',         [CampaignController::class, 'index'])->name('campaigns.index');
    Route::get('/campaigns/create',  [CampaignController::class, 'create'])->name('campaigns.create');

    // IMPORTANT : routes statiques AVANT la route dynamique /{campaign}
    Route::middleware(['role:validator_n1,validator_n2,validator,admin'])->group(function () {
        Route::get('/campaigns/pending', [CampaignController::class, 'pendingList'])->name('campaigns.pending');
    });

    Route::get('/campaigns/{campaign}', [CampaignController::class, 'show'])->name('campaigns.show');

    // Opérateur : créer, soumettre, lancer
    Route::middleware(['role:operator,admin'])->group(function () {
        Route::post('/campaigns',                    [CampaignController::class, 'store'])->name('campaigns.store');
        Route::post('/campaigns/{campaign}/submit',  [CampaignController::class, 'submit'])->name('campaigns.submit');
        Route::post('/campaigns/{campaign}/launch',  [CampaignController::class, 'launch'])->name('campaigns.launch');
    });

    // Validateurs N1 + N2 : approuver/rejeter (vérification fine dans le contrôleur)
    Route::middleware(['role:validator_n1,validator_n2,validator,admin'])->group(function () {
        Route::post('/campaigns/{campaign}/approve', [CampaignController::class, 'approve'])->name('campaigns.approve');
        Route::post('/campaigns/{campaign}/reject',  [CampaignController::class, 'reject'])->name('campaigns.reject');
    });

    // ─── Boost — Opérateur (operator + admin uniquement) ─────
    Route::middleware(['role:operator,admin'])->group(function () {
        Route::get('/boost/my-requests',     [BoostController::class, 'myRequests'])->name('boost.my-requests');
        Route::get('/boost/create',          [BoostController::class, 'create'])->name('boost.create');
        Route::post('/boost',                [BoostController::class, 'store'])->name('boost.store');
        Route::post('/boost/{boost}/submit', [BoostController::class, 'submit'])->name('boost.submit');
    });

    // ─── Users (admin only) ──────────────────────────────────
    Route::middleware(['role:admin'])->prefix('users')->name('users.')->group(function () {
        Route::get('/',                [UserController::class, 'index'])->name('index');
        Route::get('/create',          [UserController::class, 'create'])->name('create');
        Route::post('/',               [UserController::class, 'store'])->name('store');
        Route::get('/{user}/edit',     [UserController::class, 'edit'])->name('edit');
        Route::put('/{user}',          [UserController::class, 'update'])->name('update');
        Route::delete('/{user}',       [UserController::class, 'destroy'])->name('destroy');
        Route::post('/{user}/toggle',  [UserController::class, 'toggleActive'])->name('toggle');
    });

    // ─── Sync Runs (admin + validators) — monitoring ─────────
    Route::middleware(['role:validator_n1,validator_n2,validator,admin'])->prefix('sync-runs')->name('sync-runs.')->group(function () {
        Route::get('/',        [SyncRunController::class, 'index'])->name('index');
        Route::get('/{syncRun}', [SyncRunController::class, 'show'])->name('show');
    });

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
        Route::get('/boost/pending-n1',                    [ValidatorController::class, 'pendingN1'])->name('boost.pending-n1');
        Route::post('/boost/{boost}/approve-n1',           [ValidatorController::class, 'approveN1'])->name('boost.approve-n1');
        Route::post('/boost/{boost}/reject-n1',            [ValidatorController::class, 'rejectN1'])->name('boost.reject-n1');
        Route::post('/boost/{boost}/request-changes-n1',   [ValidatorController::class, 'requestChangesN1'])->name('boost.request-changes-n1');
    });

    // ─── Boost — Validation N+2 ─────────────────────────────
    Route::middleware(['role:validator_n2,admin'])->group(function () {
        Route::get('/boost/pending-n2',                    [ValidatorController::class, 'pendingN2'])->name('boost.pending-n2');
        Route::post('/boost/{boost}/approve-n2',           [ValidatorController::class, 'approveN2'])->name('boost.approve-n2');
        Route::post('/boost/{boost}/reject-n2',            [ValidatorController::class, 'rejectN2'])->name('boost.reject-n2');
        Route::post('/boost/{boost}/request-changes-n2',   [ValidatorController::class, 'requestChangesN2'])->name('boost.request-changes-n2');
    });

    // ─── Boost — Actions post-N8N (tous validateurs + admin) ─
    Route::middleware(['role:validator_n1,validator_n2,validator,admin'])->group(function () {
        Route::get('/boost/all',                  [ValidatorController::class, 'all'])->name('boost.all');
        Route::post('/boost/{boost}/activate',    [ValidatorController::class, 'activate'])->name('boost.activate');
        Route::post('/boost/{boost}/pause',       [ValidatorController::class, 'pause'])->name('boost.pause');
        Route::post('/boost/{boost}/cancel',      [ValidatorController::class, 'cancel'])->name('boost.cancel');
        Route::post('/boost/{boost}/retry-n8n',   [ValidatorController::class, 'retryN8n'])->name('boost.retry-n8n');
    });

    // Route dynamique en dernier
    Route::get('/boost/{boost}', [BoostController::class, 'show'])->name('boost.show');

});
