<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\GoodsController;
use App\Http\Controllers\MoneyController;
use App\Http\Controllers\DepositController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\ReportController;
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

        Route::prefix('deposit')->name('deposit.')->group(function () {
            Route::get('/', [DepositController::class, 'index'])->name('index');
            Route::get('/show', [DepositController::class, 'show'])->name('show');
            Route::post('/save', [DepositController::class, 'save'])->name('save');
            Route::post('/provodka', [DepositController::class, 'provodka'])->name('provodka');
            Route::post('/delete', [DepositController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/', [ReportController::class, 'index'])->name('index');
            Route::get('/sales', [ReportController::class, 'sales'])->name('sales');
            Route::get('/abc-xyz', [ReportController::class, 'abcXyz'])->name('abcxyz');
            Route::get('/inventory', [ReportController::class, 'inventory'])->name('inventory');
            Route::get('/turnover', [ReportController::class, 'turnover'])->name('turnover');
            Route::get('/purchases', [ReportController::class, 'purchases'])->name('purchases');
            Route::get('/stocks', [ReportController::class, 'stocks'])->name('stocks');
            Route::get('/stocks/export', [ReportController::class, 'stocksExport'])->name('stocks.export');
            Route::get('/pnl-segments', [ReportController::class, 'pnlSegments'])->name('pnlsegments');
            Route::get('/unit-economics', [ReportController::class, 'unitEconomics'])->name('uniteconomics');
            Route::get('/gross-profit', [ReportController::class, 'grossProfit'])->name('grossprofit');
            Route::get('/financial-pnl', [ReportController::class, 'financialPnl'])->name('financialpnl');
            Route::get('/balance-sheet', [ReportController::class, 'balanceSheet'])->name('balancesheet');
            Route::get('/cash-flow-statement', [ReportController::class, 'cashFlowStatement'])->name('cashflowstmt');
            Route::get('/sales-forecast', [ReportController::class, 'salesForecast'])->name('salesforecast');
            Route::get('/purchase-plan', [ReportController::class, 'purchasePlan'])->name('purchaseplan');
            Route::get('/profit-plan', [ReportController::class, 'profitPlan'])->name('profitplan');
            Route::get('/demand-trends', [ReportController::class, 'demandTrends'])->name('demandtrends');
            Route::get('/strategic-export/{report}/{format}', [ReportController::class, 'strategicExport'])->name('strategic.export');
            Route::get('/finance', [ReportController::class, 'finance'])->name('finance');
        });

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
            Route::post('/switch-project', [SettingsController::class , 'switchProject'])->name('switchProject');
            Route::post('/save', [SettingsController::class , 'save'])->name('save');
            Route::post('/profile-update', [SettingsController::class , 'profileUpdate'])->name('profileUpdate');
            Route::post('/password-change', [SettingsController::class , 'passwordChange'])->name('passwordChange');
            Route::get('/projects', [SettingsController::class , 'projectsIndex'])->name('projects.index');
            Route::get('/projects/{id}', [SettingsController::class , 'projectsShow'])->name('projects.show');
            Route::post('/projects', [SettingsController::class , 'projectsStore'])->name('projects.store');
            Route::put('/projects/{id}', [SettingsController::class , 'projectsUpdate'])->name('projects.update');
            Route::delete('/projects/{id}', [SettingsController::class , 'projectsDestroy'])->name('projects.destroy');
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
            Route::get('/banners', [SettingsController::class , 'bannersIndex'])->name('banners.index');
            Route::get('/banners/{id}', [SettingsController::class , 'bannersShow'])->name('banners.show');
            Route::post('/banners', [SettingsController::class , 'bannersStore'])->name('banners.store');
            Route::post('/banners/{id}', [SettingsController::class , 'bannersUpdate'])->name('banners.update');
            Route::delete('/banners/{id}', [SettingsController::class , 'bannersDestroy'])->name('banners.destroy');

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
