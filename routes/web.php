<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BankController;
use App\Http\Controllers\BusinessAssetController;
use App\Http\Controllers\BlockchainMonitorController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardAgentChatController;
use App\Http\Controllers\DepositController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\EducationController;
use App\Http\Controllers\EmployeeRoleController;
use App\Http\Controllers\FilterController;
use App\Http\Controllers\FinancingController;
use App\Http\Controllers\GoodsController;
use App\Http\Controllers\KursController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\MoneyController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\PriceController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\WalletController;

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
Route::get('/start', [AuthController::class , 'showLogin'])->name('login');
Route::post('/login', [AuthController::class , 'login'])->name('login.post');
Route::post('/login/google', [AuthController::class , 'googleLogin'])->name('login.google');
Route::post('/auth/phone/send-code', [AuthController::class, 'apiSendPhoneCode'])->name('auth.phone.send-code');
Route::post('/auth/phone/verify', [AuthController::class, 'apiVerifyPhoneCode'])->name('auth.phone.verify');
Route::post('/web3/challenge', [AuthController::class , 'web3LoginChallenge'])->name('web3.challenge');
Route::post('/web3/login', [AuthController::class , 'web3Login'])->name('web3.login');
Route::post('/forgot-password', [AuthController::class , 'forgotPassword'])->name('password.forgot');
Route::post('/logout', [AuthController::class , 'logout'])->name('logout');
Route::get('/register', [AuthController::class , 'showRegister'])->name('register');
Route::post('/register', [AuthController::class , 'register'])->name('register.post');

// ── Language switching ────────────────────────────────────────────────────────
Route::post('/language/switch', [LanguageController::class, 'switch'])->name('language.switch');
Route::get('/language/current', [LanguageController::class, 'current'])->name('language.current');

// ── Sitemap ───────────────────────────────────────────────────────────────────
Route::get('/sitemap', [SitemapController::class, 'short'])->name('sitemap.short');
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap.public');

// ── Public Pages ──────────────────────────────────────────────────────────────
Route::view('/', 'pages.micro_business')->name('micro-business');
Route::view('/education', 'pages.education')->name('education.public');
Route::get('/price', [PriceController::class, 'index'])->name('price');
Route::get('/about', function () {
    $projectTypeLabels = [
        'trade' => 'Торговля',
        'bank' => 'Банк',
        'insurance' => 'Страхование',
        'education' => 'Образование',
        '' => 'Другие проекты',
    ];

    $projects = \Illuminate\Support\Facades\Schema::hasTable('project') 
        ? \App\Models\Project::query()
            ->when(
                \Illuminate\Support\Facades\Schema::hasColumn('project', 'web'),
                fn ($query) => $query->where('web', 1)
            )
            ->whereNotNull('url')
            ->where('url', '<>', '')
            ->orderBy('project_type')
            ->orderBy('num')
            ->orderBy('name')
            ->get(['id', 'name', 'project_type', 'url', 'web'])
            ->map(function ($project) use ($projectTypeLabels) {
                $type = strtolower(trim((string) ($project->project_type ?? '')));
                $project->segment_label = $projectTypeLabels[$type] ?? 'Другие проекты';
                return $project;
            })
            ->groupBy('segment_label')
        : collect();
    return view('pages.about', compact('projects'));
})->name('about');
Route::get('/team', [TeamController::class, 'index'])->name('team');
Route::get('/wallet/swap-window', [WalletController::class, 'swapWindow'])->name('wallet.swap-window');
Route::post('/price/order', [PriceController::class, 'order'])->name('price.order');

// ── Protected area ────────────────────────────────────────────────────────────
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [AuthController::class , 'dashboard'])->name('dashboard');
    Route::name('education.')->group(function () {
        Route::get('/course', [EducationController::class, 'course'])->name('course');
        Route::get('/course/{topic}', [EducationController::class, 'courseShow'])->name('course.show');
        Route::post('/course/topics', [EducationController::class, 'storeTopic'])->name('topics.store');
        Route::put('/course/topics/{topic}', [EducationController::class, 'updateTopic'])
            ->whereNumber('topic')->name('topics.update');
        Route::delete('/course/topics/{topic}', [EducationController::class, 'destroyTopic'])
            ->whereNumber('topic')->name('topics.destroy');
        Route::post('/course/materials', [EducationController::class, 'storeMaterial'])->name('materials.store');
        Route::put('/course/materials/{material}', [EducationController::class, 'updateMaterial'])
            ->whereNumber('material')->name('materials.update');
        Route::delete('/course/materials/{material}', [EducationController::class, 'destroyMaterial'])
            ->whereNumber('material')->name('materials.destroy');
        Route::get('/materials', [EducationController::class, 'materials'])->name('material-files.index');
        Route::post('/materials', [EducationController::class, 'storeMaterialImage'])->name('material-files.store');
        Route::delete('/materials', [EducationController::class, 'destroyMaterialImage'])->name('material-files.destroy');
        Route::get('/utilities', [EducationController::class, 'utilities'])->name('utilities');
        Route::post('/utilities', [EducationController::class, 'storeUtility'])->name('utilities.store');
        Route::put('/utilities/{utility}', [EducationController::class, 'updateUtility'])
            ->where('utility', '[A-Za-z0-9\\-_]+')->name('utilities.update');
        Route::delete('/utilities/{utility}', [EducationController::class, 'destroyUtility'])
            ->where('utility', '[A-Za-z0-9\\-_]+')->name('utilities.destroy');
        Route::get('/tests', [EducationController::class, 'tests'])->name('tests');
        Route::post('/tests', [EducationController::class, 'storeTest'])->name('tests.store');
        Route::put('/tests/{test}', [EducationController::class, 'updateTest'])
            ->whereNumber('test')->name('tests.update');
        Route::delete('/tests/{test}', [EducationController::class, 'destroyTest'])
            ->whereNumber('test')->name('tests.destroy');
        Route::post('/tests/{test}/submit', [EducationController::class, 'submit'])
            ->whereNumber('test')
            ->name('tests.submit');
        Route::get('/know-yourself', [EducationController::class, 'knowYourself'])->name('know-yourself');
        Route::post('/know-yourself', [EducationController::class, 'storeKnowYourself'])->name('know-yourself.store');
        Route::put('/know-yourself/{test}', [EducationController::class, 'updateKnowYourself'])
            ->whereNumber('test')->name('know-yourself.update');
        Route::delete('/know-yourself/{test}', [EducationController::class, 'destroyKnowYourself'])
            ->whereNumber('test')->name('know-yourself.destroy');
        Route::post('/education-categories', [EducationController::class, 'storeCategory'])->name('categories.store');
        Route::put('/education-categories/{category}', [EducationController::class, 'updateCategory'])
            ->whereNumber('category')->name('categories.update');
        Route::delete('/education-categories/{category}', [EducationController::class, 'destroyCategory'])
            ->whereNumber('category')->name('categories.destroy');
    });
    Route::get('/dashboard/agent-chat', [DashboardAgentChatController::class, 'index'])->name('dashboard.agent-chat.index');
    Route::post('/dashboard/agent-chat', [DashboardAgentChatController::class, 'store'])->name('dashboard.agent-chat.store');
    Route::get('/blockchain-monitor', [BlockchainMonitorController::class, 'page'])->name('blockchain-monitor.index');
    Route::get('/blockchain-monitor/api/summary', [BlockchainMonitorController::class, 'summary'])->name('blockchain-monitor.summary');
    Route::get('/blockchain-monitor/api/events', [BlockchainMonitorController::class, 'events'])->name('blockchain-monitor.events');
    Route::post('/blockchain-monitor/api/sync', [BlockchainMonitorController::class, 'sync'])->name('blockchain-monitor.sync');
    Route::prefix('bank')->name('bank.')->group(function () {
        Route::get('/cash-accounts', [BankController::class, 'cashAccounts'])->name('cash-accounts');
        Route::post('/cash-accounts/operational-accounts', [BankController::class, 'storeOperationalAccount'])
            ->name('operational-accounts.store');
        Route::put('/cash-accounts/operational-accounts/{account}', [BankController::class, 'updateOperationalAccount'])
            ->whereNumber('account')
            ->name('operational-accounts.update');
        Route::delete('/cash-accounts/operational-accounts/{account}', [BankController::class, 'destroyOperationalAccount'])
            ->whereNumber('account')
            ->name('operational-accounts.destroy');
        Route::post('/cash-accounts/projects/{project}/accounts', [BankController::class, 'storeProjectAccount'])
            ->whereNumber('project')
            ->name('project-accounts.store');
        Route::put('/cash-accounts/projects/{project}/accounts/{account}', [BankController::class, 'updateProjectAccount'])
            ->whereNumber('project')
            ->whereNumber('account')
            ->name('project-accounts.update');
        Route::delete('/cash-accounts/projects/{project}/accounts/{account}', [BankController::class, 'destroyProjectAccount'])
            ->whereNumber('project')
            ->whereNumber('account')
            ->name('project-accounts.destroy');
        Route::post('/cash-accounts/persons/{person}/accounts', [BankController::class, 'storePersonAccount'])
            ->whereNumber('person')
            ->name('person-accounts.store');
        Route::delete('/cash-accounts/persons/{person}/accounts/{account}', [BankController::class, 'destroyPersonAccount'])
            ->whereNumber('person')
            ->whereNumber('account')
            ->name('person-accounts.destroy');
        Route::get('/loan', [BankController::class, 'loan'])->name('loanDocs.index');
        Route::get('/loans', fn () => redirect()->route('bank.loanDocs.index', request()->query()))->name('loans');
        Route::get('/loans/show', fn () => redirect()->route('bank.loanDocs.show', request()->query()))->name('loans.show');
        Route::post('/loan', [BankController::class, 'storeLoanRequest'])->name('loan.store');
        Route::post('/loan/payments', [BankController::class, 'storeLoanPayment'])->name('loan.payments.store');
        Route::prefix('loan')->name('loanDocs.')->group(function () {
            Route::get('/show', [DocumentController::class , 'show'])->name('show');
            Route::get('/print', [DocumentController::class , 'print'])->name('print');
            Route::post('/save', [DocumentController::class , 'save'])->name('save');
            Route::post('/delete', [DocumentController::class , 'destroy'])->name('destroy');
            Route::post('/provodka', [DocumentController::class , 'provodka'])->name('provodka');
            Route::post('/status', [DocumentController::class , 'bulkStatus'])->name('bulkStatus');
            Route::post('/set-client', [DocumentController::class , 'setClient'])->name('setClient');
            Route::post('/body/add', [DocumentController::class , 'bodyAdd'])->name('body.add');
            Route::post('/body/delete', [DocumentController::class , 'bodyDelete'])->name('body.delete');
            Route::post('/body/update', [DocumentController::class , 'bodyUpdate'])->name('body.update');
            Route::get('/product-mapping/search', [DocumentController::class , 'productMappingSearch'])->name('productMapping.search');
            Route::post('/product-mapping/save', [DocumentController::class , 'productMappingSave'])->name('productMapping.save');
        });
        Route::get('/deposit', [BankController::class, 'deposit'])->name('deposit');
        Route::get('/pools', [BankController::class, 'pools'])->name('pools');
        Route::post('/pools', [BankController::class, 'storePool'])->name('pools.store');
        Route::put('/pools/{pool}', [BankController::class, 'updatePool'])
            ->whereNumber('pool')
            ->name('pools.update');
        Route::post('/deposit', [BankController::class, 'storeDeposit'])->name('deposit.store');
        Route::post('/deposit/transfer', [BankController::class, 'storeDepositTransfer'])->name('deposit.transfer.store');
        Route::put('/deposit/transfer/{transfer}', [BankController::class, 'updateDepositTransfer'])
            ->whereNumber('transfer')
            ->name('deposit.transfer.update');
        Route::post('/deposit/transfer/{transfer}/reverse', [BankController::class, 'reverseDepositTransfer'])
            ->whereNumber('transfer')
            ->name('deposit.transfer.reverse');
        Route::delete('/deposit/transfer/{transfer}', [BankController::class, 'destroyDepositTransfer'])
            ->whereNumber('transfer')
            ->name('deposit.transfer.destroy');
        Route::put('/deposit/{deposit}', [BankController::class, 'updateDeposit'])
            ->whereNumber('deposit')
            ->name('deposit.update');
        Route::get('/invest', [BankController::class, 'invest'])->name('invest');
        Route::get('/pool-movements', [BankController::class, 'poolMovements'])->name('pool-movements');
        Route::get('/assets', [BankController::class, 'assets'])->name('assets');
        Route::get('/stock-analysis', [BankController::class, 'stockAnalysis'])->name('stock-analysis');
        Route::post('/stock-analysis', [BankController::class, 'storeStockAnalysis'])->name('stock-analysis.store');
        Route::get('/stock-analysis/parameters', [BankController::class, 'stockAnalysisParameters'])
            ->name('stock-analysis.parameters');
        Route::post('/stock-analysis/parameters', [BankController::class, 'storeStockAnalysisParameter'])
            ->name('stock-analysis.parameters.store');
        Route::put('/stock-analysis/parameters/{parameter}', [BankController::class, 'updateStockAnalysisParameter'])
            ->whereNumber('parameter')
            ->name('stock-analysis.parameters.update');
        Route::delete('/stock-analysis/parameters/{parameter}', [BankController::class, 'destroyStockAnalysisParameter'])
            ->whereNumber('parameter')
            ->name('stock-analysis.parameters.destroy');
        Route::delete('/stock-analysis/parameter-groups', [BankController::class, 'destroyStockAnalysisParameterGroup'])
            ->name('stock-analysis.parameter-groups.destroy');
        Route::post('/stock-analysis/multipliers', [BankController::class, 'storeStockAnalysisMultiplier'])
            ->name('stock-analysis.multipliers.store');
        Route::put('/stock-analysis/multipliers/{multiplier}', [BankController::class, 'updateStockAnalysisMultiplier'])
            ->whereNumber('multiplier')
            ->name('stock-analysis.multipliers.update');
        Route::delete('/stock-analysis/multipliers/{multiplier}', [BankController::class, 'destroyStockAnalysisMultiplier'])
            ->whereNumber('multiplier')
            ->name('stock-analysis.multipliers.destroy');
        Route::get('/stock-analysis/{stock}', [BankController::class, 'showStockAnalysis'])
            ->whereNumber('stock')
            ->name('stock-analysis.show');
        Route::put('/stock-analysis/{stock}', [BankController::class, 'updateStockAnalysis'])
            ->whereNumber('stock')
            ->name('stock-analysis.update');
        Route::post('/stock-analysis/{stock}/adapter/pull', [BankController::class, 'pullStockAnalysisAdapter'])
            ->whereNumber('stock')
            ->name('stock-analysis.adapter.pull');
        Route::post('/stock-analysis/{stock}/adapter', [BankController::class, 'updateStockAnalysisAdapter'])
            ->whereNumber('stock')
            ->name('stock-analysis.adapter.update');
        Route::delete('/stock-analysis/{stock}', [BankController::class, 'destroyStockAnalysis'])
            ->whereNumber('stock')
            ->name('stock-analysis.destroy');
        Route::post('/invest/assets', [BankController::class, 'storeInvestAsset'])
            ->name('invest-assets.store');
        Route::put('/invest/assets/{asset}', [BankController::class, 'updateInvestAsset'])
            ->whereNumber('asset')
            ->name('invest-assets.update');
        Route::delete('/invest/assets/{asset}', [BankController::class, 'destroyInvestAsset'])
            ->whereNumber('asset')
            ->name('invest-assets.destroy');
        Route::post('/invest/operations', [BankController::class, 'storeInvestOperation'])
            ->name('invest-operations.store');
        Route::put('/invest/operations/{operation}', [BankController::class, 'updateInvestOperation'])
            ->whereNumber('operation')
            ->name('invest-operations.update');
        Route::delete('/invest/operations/{operation}', [BankController::class, 'destroyInvestOperation'])
            ->whereNumber('operation')
            ->name('invest-operations.destroy');
        Route::get('/invest/operations/{operation}/reverse', [BankController::class, 'showReverseInvestOperation'])
            ->whereNumber('operation')
            ->name('invest-operations.reverse.show');
        Route::post('/invest/operations/{operation}/reverse', [BankController::class, 'reverseInvestOperation'])
            ->whereNumber('operation')
            ->name('invest-operations.reverse');
        Route::post('/invest/tracked-assets', [BankController::class, 'storeTrackedAsset'])
            ->name('tracked-assets.store');
        Route::post('/invest/tracked-assets/refresh', [BankController::class, 'refreshTrackedAssets'])
            ->name('tracked-assets.refresh');
        Route::post('/invest/tracked-assets/bulk', [BankController::class, 'bulkUpdateTrackedAssets'])
            ->name('tracked-assets.bulk');
        Route::post('/invest/tracked-assets/{asset}/adapter', [BankController::class, 'updateTrackedAssetAdapter'])
            ->whereNumber('asset')
            ->name('tracked-assets.adapter');
        Route::post('/invest/assets/{source}/{asset}', [BankController::class, 'updateAssetManifestItem'])
            ->whereIn('source', ['deposit', 'pool'])
            ->whereNumber('asset')
            ->name('asset-manifest.update');
        Route::post('/invest/assets/bulk', [BankController::class, 'bulkUpdateAssetManifestItems'])
            ->name('asset-manifest.bulk');
        Route::post('/invest/tokens/{token}', [BankController::class, 'updateTokenManifestItem'])
            ->whereNumber('token')
            ->name('token-manifest.update');
        Route::post('/invest/tokens/bulk', [BankController::class, 'bulkUpdateTokenManifestItems'])
            ->name('token-manifest.bulk');
        Route::get('/exchange', [BankController::class, 'exchange'])->name('exchange');
        Route::post('/exchange/crypto', [BankController::class, 'storeFiatCryptoExchange'])
            ->name('exchange.crypto.store');
        Route::post('/exchange/crypto/{order}', [BankController::class, 'updateFiatCryptoExchange'])
            ->whereNumber('order')
            ->name('exchange.crypto.update');
        Route::post('/exchange/crypto/{order}/reverse', [BankController::class, 'reverseFiatCryptoExchange'])
            ->whereNumber('order')
            ->name('exchange.crypto.reverse');
        Route::post('/exchange/orders/{order}/status', [BankController::class, 'updateExchangeOrderStatus'])
            ->whereNumber('order')
            ->name('exchange-orders.status');
        Route::get('/clearing', [BankController::class, 'clearing'])->name('clearing');
        Route::get('/payments', [BankController::class, 'payments'])->name('payments');
        Route::get('/reconciliation', [BankController::class, 'reconciliation'])->name('reconciliation');
    });
    Route::post('/dashboard/transport-lookup', [AuthController::class, 'transportLookup'])->name('dashboard.transportLookup');
    Route::prefix('team')->name('team.')->group(function () {
        Route::get('/show', [TeamController::class, 'show'])->name('show');
        Route::get('/users', [TeamController::class, 'users'])->name('users');
        Route::post('/attach', [TeamController::class, 'attach'])->name('attach');
        Route::get('/report', [TeamController::class, 'payrollReport'])->name('report');
        Route::post('/save', [TeamController::class, 'save'])->name('save');
        Route::post('/delete', [TeamController::class, 'destroy'])->name('destroy');
    });
    Route::post('/wallet/challenge', [AuthController::class, 'web3LinkChallenge'])->name('wallet.challenge');
    Route::post('/wallet/link', [AuthController::class, 'linkWallet'])->name('wallet.link');
    Route::post('/wallet/unlink', [AuthController::class, 'unlinkWallet'])->name('wallet.unlink');

    // ── Documents ─────────────────────────────────────────────────────────────
    Route::prefix('document')->name('document.')->group(function () {
            Route::get('/', [DocumentController::class , 'index'])->name('index');
            Route::get('/assets', [BusinessAssetController::class, 'index'])->name('assets.index');
            Route::post('/assets/operations', [BusinessAssetController::class, 'storeOperation'])->name('assets.operations.store');
            Route::post('/assets/operations/{operation}/post', [BusinessAssetController::class, 'post'])->whereNumber('operation')->name('assets.operations.post');
            Route::post('/assets/operations/{operation}/reverse', [BusinessAssetController::class, 'reverse'])->whereNumber('operation')->name('assets.operations.reverse');
            Route::delete('/assets/operations/{operation}', [BusinessAssetController::class, 'destroy'])->whereNumber('operation')->name('assets.operations.destroy');
            Route::get('/financing', [FinancingController::class, 'index'])->name('financing.index');
            Route::post('/financing/agreements', [FinancingController::class, 'storeAgreement'])->name('financing.agreements.store');
            Route::post('/financing/operations', [FinancingController::class, 'storeOperation'])->name('financing.operations.store');
            Route::post('/financing/operations/{operation}/post', [FinancingController::class, 'post'])->whereNumber('operation')->name('financing.operations.post');
            Route::post('/financing/operations/{operation}/reverse', [FinancingController::class, 'reverse'])->whereNumber('operation')->name('financing.operations.reverse');
            Route::delete('/financing/operations/{operation}', [FinancingController::class, 'destroy'])->whereNumber('operation')->name('financing.operations.destroy');
            Route::get('/show', [DocumentController::class , 'show'])->name('show');
            Route::get('/print', [DocumentController::class , 'print'])->name('print');
            Route::post('/save', [DocumentController::class , 'save'])->name('save');
            Route::get('/salary-statements/create', [DocumentController::class, 'salaryStatementCreate'])->name('salaryStatements.create');
            Route::get('/salary-statements/{id}', [DocumentController::class, 'salaryStatementShow'])->whereNumber('id')->name('salaryStatements.show');
            Route::post('/salary-statements', [DocumentController::class, 'salaryStatementStore'])->name('salaryStatements.store');
            Route::put('/salary-statements/{id}', [DocumentController::class, 'salaryStatementUpdate'])->whereNumber('id')->name('salaryStatements.update');
            Route::delete('/salary-statements/{id}', [DocumentController::class, 'salaryStatementDestroy'])
                ->whereNumber('id')->name('salaryStatements.destroy');
            Route::delete('/salary-statements/{id}/employees/{lineId}', [DocumentController::class, 'salaryStatementEmployeeDestroy'])
                ->whereNumber('id')->whereNumber('lineId')->name('salaryStatements.employees.destroy');
            Route::post('/salary-statements/{id}/employees/{lineId}/payout', [DocumentController::class, 'salaryStatementPayout'])
                ->whereNumber('id')->whereNumber('lineId')->name('salaryStatements.payout');
            Route::post('/delete', [DocumentController::class , 'destroy'])->name('destroy');
            Route::post('/provodka', [DocumentController::class , 'provodka'])->name('provodka');
            Route::post('/status', [DocumentController::class , 'bulkStatus'])->name('bulkStatus');
            Route::post('/set-client', [DocumentController::class , 'setClient'])->name('setClient');
            // z_body line items
            Route::post('/body/add', [DocumentController::class , 'bodyAdd'])->name('body.add');
            Route::post('/body/delete', [DocumentController::class , 'bodyDelete'])->name('body.delete');
            Route::post('/body/update', [DocumentController::class , 'bodyUpdate'])->name('body.update');
            Route::get('/product-mapping/search', [DocumentController::class , 'productMappingSearch'])->name('productMapping.search');
            Route::post('/product-mapping/save', [DocumentController::class , 'productMappingSave'])->name('productMapping.save');
        }
        );

    Route::prefix('loan')->name('loan.')->group(function () {
            Route::get('/', [DocumentController::class , 'index'])->name('index');
            Route::get('/show', [DocumentController::class , 'show'])->name('show');
            Route::get('/print', [DocumentController::class , 'print'])->name('print');
            Route::post('/save', [DocumentController::class , 'save'])->name('save');
            Route::post('/delete', [DocumentController::class , 'destroy'])->name('destroy');
            Route::post('/provodka', [DocumentController::class , 'provodka'])->name('provodka');
            Route::post('/status', [DocumentController::class , 'bulkStatus'])->name('bulkStatus');
            Route::post('/set-client', [DocumentController::class , 'setClient'])->name('setClient');
            Route::post('/body/add', [DocumentController::class , 'bodyAdd'])->name('body.add');
            Route::post('/body/delete', [DocumentController::class , 'bodyDelete'])->name('body.delete');
            Route::post('/body/update', [DocumentController::class , 'bodyUpdate'])->name('body.update');
            Route::get('/product-mapping/search', [DocumentController::class , 'productMappingSearch'])->name('productMapping.search');
            Route::post('/product-mapping/save', [DocumentController::class , 'productMappingSave'])->name('productMapping.save');
        }
        );

        // ── Clients ───────────────────────────────────────────────────────────────
        Route::prefix('client')->name('client.')->group(function () {
            Route::get('/', [ClientController::class , 'index'])->name('index');
            Route::get('/show', [ClientController::class , 'show'])->name('show');
            Route::get('/search', [ClientController::class , 'search'])->name('search');
            Route::post('/check-email', [ClientController::class , 'checkEmail'])->name('checkEmail');
            Route::get('/groups', [ClientController::class , 'groups'])->name('groups.index');
            Route::post('/groups', [ClientController::class , 'groupStore'])->name('groups.store');
            Route::put('/groups/{id}', [ClientController::class , 'groupUpdate'])->name('groups.update');
            Route::delete('/groups/{id}', [ClientController::class , 'groupDestroy'])->name('groups.destroy');
            Route::post('/garage/lookup', [ClientController::class , 'garageLookup'])->name('garage.lookup');
            Route::post('/garage/update', [ClientController::class , 'garageUpdate'])->name('garage.update');
            Route::get('/{id}/orders', [ClientController::class , 'orders'])->name('orders');
            Route::get('/{id}/kyc-photo/{type}', [ClientController::class , 'kycPhoto'])->name('kycPhoto');
            Route::post('/save', [ClientController::class , 'save'])->name('save');
            Route::post('/quick-store', [ClientController::class , 'storeQuick'])->name('quickStore');
            Route::post('/delete', [ClientController::class , 'destroy'])->name('destroy');
            Route::post('/delete-kyc-photo', [ClientController::class , 'deleteKycPhoto'])->name('deleteKycPhoto');
            Route::get('/saldo', [ClientController::class , 'saldo'])->name('saldo');
            Route::post('/firm', [ClientController::class , 'saveFirm'])->name('firm.save');
        }
        );

        // ── Products ──────────────────────────────────────────────────────────────
        Route::prefix('goods')->name('goods.')->group(function () {
            Route::get('/', [GoodsController::class , 'index'])->name('index');
            Route::get('/show', [GoodsController::class , 'show'])->name('show');
            Route::get('/catalog-filter-groups', [GoodsController::class, 'catalogFilterGroups'])->name('catalogFilterGroups');
            Route::get('/search', [GoodsController::class , 'search'])->name('search');
            Route::post('/save', [GoodsController::class , 'save'])->name('save');
            Route::post('/delete', [GoodsController::class , 'destroy'])->name('destroy');
            Route::post('/bulk-flags', [GoodsController::class , 'bulkFlags'])->name('bulkFlags');
            Route::post('/toggle-sklad', [GoodsController::class , 'toggleSklad'])->name('toggleSklad');
        }
        );

        Route::prefix('subscriptions')->name('subscriptions.')->group(function () {
            Route::get('/', [SubscriptionController::class, 'index'])->name('index');
            Route::post('/plans', [SubscriptionController::class, 'storePlan'])->name('plans.store');
            Route::put('/plans/{plan}', [SubscriptionController::class, 'updatePlan'])->name('plans.update');
            Route::delete('/plans/{plan}', [SubscriptionController::class, 'destroyPlan'])->name('plans.destroy');
            Route::post('/plans/{plan}/items', [SubscriptionController::class, 'storePlanItem'])->name('planItems.store');
            Route::delete('/items/{item}', [SubscriptionController::class, 'destroyPlanItem'])->name('planItems.destroy');
            Route::post('/', [SubscriptionController::class, 'storeSubscription'])->name('store');
            Route::put('/{subscription}', [SubscriptionController::class, 'updateSubscription'])->name('update');
            Route::delete('/{subscription}', [SubscriptionController::class, 'destroySubscription'])->name('destroy');
            Route::post('/{subscription}/bill', [SubscriptionController::class, 'bill'])->name('bill');
            Route::post('/invoices/{invoice}/paid', [SubscriptionController::class, 'markInvoicePaid'])->name('invoices.paid');
        });

        // ── Money ─────────────────────────────────────────────────────────────────
        Route::prefix('money')->name('money.')->group(function () {
            Route::get('/', [MoneyController::class , 'index'])->name('index');
            Route::get('/transfers', [MoneyController::class , 'transfers'])->name('transfers');
            Route::get('/show', [MoneyController::class , 'show'])->name('show');
            Route::get('/swap', [MoneyController::class , 'swap'])->name('swap');
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
            Route::get('/trial-balance', [ReportController::class, 'trialBalance'])->name('trialbalance');
            Route::get('/journal', [ReportController::class, 'journal'])->name('journal');
            Route::get('/sales-forecast', [ReportController::class, 'salesForecast'])->name('salesforecast');
            Route::get('/purchase-plan', [ReportController::class, 'purchasePlan'])->name('purchaseplan');
            Route::get('/profit-plan', [ReportController::class, 'profitPlan'])->name('profitplan');
            Route::get('/demand-trends', [ReportController::class, 'demandTrends'])->name('demandtrends');
            Route::get('/webchat-activity', [ReportController::class, 'webchatActivity'])->name('webchatactivity');
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
            Route::post('/profile-balances-update', [SettingsController::class , 'profileBalancesUpdate'])->name('profileBalancesUpdate');
            Route::post('/password-change', [SettingsController::class , 'passwordChange'])->name('passwordChange');
            Route::get('/employee-roles', [EmployeeRoleController::class, 'index'])->name('employeeRoles.index');
            Route::post('/employee-roles', [EmployeeRoleController::class, 'store'])->name('employeeRoles.store');
            Route::put('/employee-roles/{role}', [EmployeeRoleController::class, 'update'])->name('employeeRoles.update');
            Route::delete('/employee-roles/{role}', [EmployeeRoleController::class, 'destroy'])->name('employeeRoles.destroy');
            Route::put('/employee-roles/{role}/permissions', [EmployeeRoleController::class, 'updatePermissions'])->name('employeeRoles.permissions.update');
            Route::get('/sms-club', [SettingsController::class , 'smsClubSettings'])->name('smsClub.show');
            Route::post('/sms-club', [SettingsController::class , 'updateSmsClubSettings'])->name('smsClub.update');
            Route::get('/sms-club/balance', [SettingsController::class , 'smsClubBalance'])->name('smsClub.balance');
            Route::get('/price-plans', [SettingsController::class, 'pricePlansIndex'])->name('pricePlans.index');
            Route::put('/price-plans', [SettingsController::class, 'pricePlansUpdate'])->name('pricePlans.update');
            Route::get('/holdings', [SettingsController::class , 'holdingsIndex'])->name('holdings.index');
            Route::delete('/holdings/{id}', [SettingsController::class , 'holdingsDestroy'])->name('holdings.destroy');
            Route::get('/projects', [SettingsController::class , 'projectsIndex'])->name('projects.index');
            Route::get('/projects/{id}', [SettingsController::class , 'projectsShow'])->name('projects.show');
            Route::post('/projects', [SettingsController::class , 'projectsStore'])->name('projects.store');
            Route::put('/projects/{id}', [SettingsController::class , 'projectsUpdate'])->name('projects.update');
            Route::delete('/projects/{id}', [SettingsController::class , 'projectsDestroy'])->name('projects.destroy');
            Route::get('/accounts', [SettingsController::class , 'accountsIndex'])->name('accounts.index');
            Route::get('/analytical-accounts', [SettingsController::class, 'analyticalAccountsIndex'])->name('accounts.analytical');
            Route::get('/accounts/{id}', [SettingsController::class , 'accountsShow'])->name('accounts.show');
            Route::post('/accounts', [SettingsController::class , 'accountsStore'])->name('accounts.store');
            Route::put('/accounts/{id}', [SettingsController::class , 'accountsUpdate'])->name('accounts.update');
            Route::delete('/accounts/{id}', [SettingsController::class , 'accountsDestroy'])->name('accounts.destroy');
            Route::get('/payment-type-account-bindings', [SettingsController::class, 'paymentTypeAccountBindings'])->name('paymentTypeBindings.index');
            Route::put('/payment-type-account-bindings/{id}', [SettingsController::class, 'updatePaymentTypeAccountBinding'])->name('paymentTypeBindings.update');
            Route::get('/firms', [SettingsController::class , 'firmsIndex'])->name('firms.index');
            Route::get('/firms/{id}', [SettingsController::class , 'firmsShow'])->name('firms.show');
            Route::post('/firms', [SettingsController::class , 'firmsStore'])->name('firms.store');
            Route::put('/firms/{id}', [SettingsController::class , 'firmsUpdate'])->name('firms.update');
            Route::delete('/firms/{id}', [SettingsController::class , 'firmsDestroy'])->name('firms.destroy');
            Route::get('/fields', [SettingsController::class , 'fieldIndex'])->name('fields.index');
            Route::get('/fields/{id}', [SettingsController::class , 'fieldShow'])->name('fields.show');
            Route::post('/fields', [SettingsController::class , 'fieldStore'])->name('fields.store');
            Route::put('/fields/{id}', [SettingsController::class , 'fieldUpdate'])->name('fields.update');
            Route::delete('/fields/{id}', [SettingsController::class , 'fieldDestroy'])->name('fields.destroy');
            Route::get('/catalog', [SettingsController::class , 'catalogIndex'])->name('catalog.index');
            Route::get('/catalog/{id}', [SettingsController::class , 'catalogShow'])->name('catalog.show');
            Route::post('/catalog', [SettingsController::class , 'catalogStore'])->name('catalog.store');
            Route::put('/catalog/{id}', [SettingsController::class , 'catalogUpdate'])->name('catalog.update');
            Route::delete('/catalog/{id}', [SettingsController::class , 'catalogDestroy'])->name('catalog.destroy');
            Route::get('/catalog-filters/categories', [SettingsController::class, 'catalogFiltersCategories'])->name('catalogFilters.categories');
            Route::get('/catalog-filters', [SettingsController::class, 'catalogFiltersIndex'])->name('catalogFilters.index');
            Route::get('/catalog-filters/{id}', [SettingsController::class, 'catalogFiltersShow'])->name('catalogFilters.show');
            Route::post('/catalog-filters', [SettingsController::class, 'catalogFiltersStore'])->name('catalogFilters.store');
            Route::put('/catalog-filters/{id}', [SettingsController::class, 'catalogFiltersUpdate'])->name('catalogFilters.update');
            Route::delete('/catalog-filters/{id}', [SettingsController::class, 'catalogFiltersDestroy'])->name('catalogFilters.destroy');
            Route::get('/region-cities', [SettingsController::class, 'regionCitiesIndex'])->name('regionCities.index');
            Route::get('/region-cities/{id}', [SettingsController::class, 'regionCitiesShow'])->name('regionCities.show');
            Route::post('/region-cities', [SettingsController::class, 'regionCitiesStore'])->name('regionCities.store');
            Route::put('/region-cities/{id}', [SettingsController::class, 'regionCitiesUpdate'])->name('regionCities.update');
            Route::delete('/region-cities/{id}', [SettingsController::class, 'regionCitiesDestroy'])->name('regionCities.destroy');
            Route::get('/banners', [SettingsController::class , 'bannersIndex'])->name('banners.index');
            Route::get('/banners/{id}', [SettingsController::class , 'bannersShow'])->name('banners.show');
            Route::post('/banners', [SettingsController::class , 'bannersStore'])->name('banners.store');
            Route::post('/banners/{id}', [SettingsController::class , 'bannersUpdate'])->name('banners.update');
            Route::delete('/banners/{id}', [SettingsController::class , 'bannersDestroy'])->name('banners.destroy');
            // Async API
            Route::get('/api/web3-token-search', [SettingsController::class, 'web3TokenSearch'])->name('api.web3-token-search');
            Route::get('/api/office-city-search', [SettingsController::class, 'officeCitySearch'])->name('api.office-city-search');
            Route::get('/api/currency-exchange-settings', [SettingsController::class, 'currencyExchangeSettings'])->name('api.currency-exchange-settings.show');
            Route::put('/api/currency-exchange-settings', [SettingsController::class, 'updateCurrencyExchangeSettings'])->name('api.currency-exchange-settings.update');
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
