<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\GoodsController;
use App\Http\Controllers\MoneyController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\FilterController;
use App\Http\Controllers\KursController;

/*
 |--------------------------------------------------------------------------
 | Legacy module → Laravel route mapping
 |--------------------------------------------------------------------------
 | autorith.php              → AuthController
 | document/index.php        → DocumentController
 | library/doc-run.php       → DocumentController (save/provodka/delete)
 | client/index.php + run.php → ClientController
 | comp/index.php + run-comp.php → GoodsController
 | money/index.php           → MoneyController
 | admin/                    → AdminController
 | library/filter.php        → FilterController
 | kurs/                     → KursController
 */

// ── Auth (public) ─────────────────────────────────────────────────────────────
Route::get('/', [AuthController::class , 'showLogin'])->name('login');
Route::post('/login', [AuthController::class , 'login'])->name('login.post');
Route::post('/web3/challenge', [AuthController::class , 'web3LoginChallenge'])->name('web3.challenge');
Route::post('/web3/login', [AuthController::class , 'web3Login'])->name('web3.login');
Route::post('/forgot-password', [AuthController::class , 'forgotPassword'])->name('password.forgot');
Route::post('/logout', [AuthController::class , 'logout'])->name('logout');
Route::get('/register', [AuthController::class , 'showRegister'])->name('register');
Route::post('/register', [AuthController::class , 'register'])->name('register.post');

// ── Protected area ────────────────────────────────────────────────────────────
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [AuthController::class , 'dashboard'])->name('dashboard');
    Route::post('/wallet/challenge', [AuthController::class, 'web3LinkChallenge'])->name('wallet.challenge');
    Route::post('/wallet/link', [AuthController::class, 'linkWallet'])->name('wallet.link');
    Route::post('/wallet/unlink', [AuthController::class, 'unlinkWallet'])->name('wallet.unlink');

    // ── Documents ─────────────────────────────────────────────────────────────
    Route::prefix('document')->name('document.')->group(function () {
            Route::get('/', [DocumentController::class , 'index'])->name('index');
            Route::get('/show', [DocumentController::class , 'show'])->name('show');
            Route::get('/print', [DocumentController::class , 'print'])->name('print');
            Route::post('/save', [DocumentController::class , 'save'])->name('save');
            Route::post('/delete', [DocumentController::class , 'destroy'])->name('destroy');
            Route::post('/provodka', [DocumentController::class , 'provodka'])->name('provodka');
            Route::post('/status', [DocumentController::class , 'bulkStatus'])->name('bulkStatus');
            Route::post('/set-client', [DocumentController::class , 'setClient'])->name('setClient');
            // z_body line items
            Route::post('/body/add', [DocumentController::class , 'bodyAdd'])->name('body.add');
            Route::post('/body/delete', [DocumentController::class , 'bodyDelete'])->name('body.delete');
            Route::post('/body/update', [DocumentController::class , 'bodyUpdate'])->name('body.update');
        }
        );

        // ── Clients ───────────────────────────────────────────────────────────────
        Route::prefix('client')->name('client.')->group(function () {
            Route::get('/', [ClientController::class , 'index'])->name('index');
            Route::get('/show', [ClientController::class , 'show'])->name('show');
            Route::get('/search', [ClientController::class , 'search'])->name('search');
            Route::get('/{id}/orders', [ClientController::class , 'orders'])->name('orders');
            Route::post('/save', [ClientController::class , 'save'])->name('save');
            Route::post('/quick-store', [ClientController::class , 'storeQuick'])->name('quickStore');
            Route::post('/delete', [ClientController::class , 'destroy'])->name('destroy');
            Route::get('/saldo', [ClientController::class , 'saldo'])->name('saldo');
            Route::post('/firm', [ClientController::class , 'saveFirm'])->name('firm.save');
        }
        );

        // ── Products ──────────────────────────────────────────────────────────────
        Route::prefix('goods')->name('goods.')->group(function () {
            Route::get('/', [GoodsController::class , 'index'])->name('index');
            Route::get('/show', [GoodsController::class , 'show'])->name('show');
            Route::get('/search', [GoodsController::class , 'search'])->name('search');
            Route::post('/save', [GoodsController::class , 'save'])->name('save');
            Route::post('/delete', [GoodsController::class , 'destroy'])->name('destroy');
            Route::post('/toggle-sklad', [GoodsController::class , 'toggleSklad'])->name('toggleSklad');
        }
        );

        // ── Money ─────────────────────────────────────────────────────────────────
        Route::prefix('money')->name('money.')->group(function () {
            Route::get('/', [MoneyController::class , 'index'])->name('index');
            Route::get('/show', [MoneyController::class , 'show'])->name('show');
            Route::post('/save', [MoneyController::class , 'save'])->name('save');
            Route::post('/provodka', [MoneyController::class , 'provodka'])->name('provodka');
            Route::post('/delete', [MoneyController::class , 'destroy'])->name('destroy');
        }
        );

        Route::prefix('news')->name('news.')->group(function () {
            Route::get('/', [NewsController::class, 'index'])->name('index');
            Route::get('/show', [NewsController::class, 'show'])->name('show');
            Route::get('/edit', [NewsController::class, 'edit'])->name('edit');
            Route::post('/save', [NewsController::class, 'save'])->name('save');
            Route::post('/delete', [NewsController::class, 'destroy'])->name('destroy');
        });

        // ── Settings ─────────────────────────────────────────────────────────────────
        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/', [SettingsController::class , 'index'])->name('index');
            Route::get('/show', [SettingsController::class , 'show'])->name('show');
            Route::post('/save', [SettingsController::class , 'save'])->name('save');
            Route::post('/profile-update', [SettingsController::class , 'profileUpdate'])->name('profileUpdate');
            Route::post('/password-change', [SettingsController::class , 'passwordChange'])->name('passwordChange');
            Route::get('/firms', [SettingsController::class , 'firmsIndex'])->name('firms.index');
            Route::get('/firms/{id}', [SettingsController::class , 'firmsShow'])->name('firms.show');
            Route::post('/firms', [SettingsController::class , 'firmsStore'])->name('firms.store');
            Route::put('/firms/{id}', [SettingsController::class , 'firmsUpdate'])->name('firms.update');
            Route::delete('/firms/{id}', [SettingsController::class , 'firmsDestroy'])->name('firms.destroy');
            Route::get('/catalog', [SettingsController::class , 'catalogIndex'])->name('catalog.index');
            Route::get('/catalog/{id}', [SettingsController::class , 'catalogShow'])->name('catalog.show');
            Route::post('/catalog', [SettingsController::class , 'catalogStore'])->name('catalog.store');
            Route::put('/catalog/{id}', [SettingsController::class , 'catalogUpdate'])->name('catalog.update');
            Route::delete('/catalog/{id}', [SettingsController::class , 'catalogDestroy'])->name('catalog.destroy');

            // Async API
            Route::get('/api/{type}', [SettingsController::class , 'apiIndex'])->name('api.index');
            Route::get('/api/{type}/{id}', [SettingsController::class , 'apiShow'])->name('api.show');
            Route::post('/api', [SettingsController::class , 'apiStore'])->name('api.store');
            Route::put('/api/{id}', [SettingsController::class , 'apiUpdate'])->name('api.update');
            Route::delete('/api/{id}', [SettingsController::class , 'apiDestroy'])->name('api.destroy');
        }
        );

        // ── Currency rates (kurs/) ────────────────────────────────────────────────
        Route::prefix('kurs')->name('kurs.')->group(function () {
            Route::get('/', [KursController::class , 'index'])->name('index');
            Route::post('/save', [KursController::class , 'save'])->name('save');
        }
        );

        // ── Filter ────────────────────────────────────────────────────────────────
        Route::post('/filter', [FilterController::class , 'apply'])->name('filter.apply');
        Route::post('/filter/clear', [FilterController::class , 'clear'])->name('filter.clear');
    });
