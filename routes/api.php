<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BannerCarouselController;
use App\Http\Controllers\FundShareSettingsController;
use App\Http\Controllers\FundTokenController;
use App\Http\Controllers\GoodsController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\RwaAdminCapController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\ZakazController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('api')->prefix('auth')->group(function () {
    Route::get('/config', [AuthController::class, 'apiAuthConfig']);
    Route::get('/zklogin/config', [AuthController::class, 'zkLoginConfig']);
    Route::post('/zklogin/google/salt', [AuthController::class, 'zkLoginGoogleSalt']);
    Route::post('/zklogin/google/proof', [AuthController::class, 'zkLoginGoogleProof']);
    Route::post('/zklogin/google/login', [AuthController::class, 'zkLoginGoogleLogin']);
    Route::get('/wallet/resolve', [AuthController::class, 'resolveUserByWallet']);
    Route::post('/register', [AuthController::class, 'apiRegister']);
    Route::post('/login', [AuthController::class, 'apiLogin']);
    Route::post('/phone/send-code', [AuthController::class, 'apiSendPhoneCode']);
    Route::post('/phone/verify', [AuthController::class, 'apiVerifyPhoneCode']);
    Route::post('/google', [AuthController::class, 'apiGoogleLogin']);
    Route::post('/web3/challenge', [AuthController::class, 'web3LoginChallenge']);
    Route::post('/web3/login', [AuthController::class, 'web3Login']);
    Route::middleware('auth:sanctum')->get('/user', [AuthController::class, 'apiUser']);
    Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'apiLogout']);
    Route::middleware('auth:sanctum')->put('/profile', [AuthController::class, 'apiUpdateProfile']);
    Route::middleware('auth:sanctum')->post('/wallet/challenge', [AuthController::class, 'web3LinkChallenge']);
    Route::middleware('auth:sanctum')->post('/wallet/link', [AuthController::class, 'linkWallet']);
    Route::middleware('auth:sanctum')->post('/wallet/unlink', [AuthController::class, 'unlinkWallet']);
    Route::middleware('auth:sanctum')->post('/wallet/update-token-data', [WalletController::class, 'updateTokenData']);
});

Route::middleware(['api', 'sui.sponsor.log', 'auth:sanctum'])->post('/sui/shinami/sponsor-transaction', [AuthController::class, 'shinamiSponsorSuiTransaction']);

Route::middleware('api')->post('/debug/frontend', function (Request $request) {
    $payload = $request->validate([
        'area' => ['nullable', 'string', 'max:80'],
        'stage' => ['required', 'string', 'max:120'],
        'data' => ['nullable', 'array'],
    ]);

    Log::info('Frontend diagnostic.', [
        'area' => $payload['area'] ?? null,
        'stage' => $payload['stage'],
        'data' => $payload['data'] ?? [],
    ]);

    return response()->json(['ok' => true]);
});

// ── Goods API ─────────────────────────────────────────────────────────────

Route::get('/goods/search', [GoodsController::class, 'searchWeb']);
Route::get('/goods/catalog-filter-groups', [GoodsController::class, 'catalogFilterGroupsApi']);
Route::get('/goods/hits', [GoodsController::class, 'getHits']);
Route::get('/goods/sections', [GoodsController::class, 'getSections']);
Route::get('/goods/section/{id}', [GoodsController::class, 'getBySection']);
Route::middleware('auth:sanctum')->post('/goods/rating/{id}', [GoodsController::class, 'saveRating']);
Route::middleware('auth:sanctum')->post('/goods/{id}/rating', [GoodsController::class, 'saveRating']);
Route::get('/goods/{id}', [GoodsController::class, 'getOne']);
Route::get('/regions', [GoodsController::class, 'getRegions']);

// ── News API ──────────────────────────────────────────────────────────────

Route::get('/news', [NewsController::class, 'apiIndex']);
Route::get('/news/{id}', [NewsController::class, 'apiShow']);
Route::get('/banners', [BannerCarouselController::class, 'apiIndex']);
Route::get('/projects/{id}', [SettingsController::class, 'projectsPublicShow']);
Route::get('/offices', [SettingsController::class, 'officesPublicIndex']);
Route::get('/wallet/tokens', [WalletController::class, 'tokens']);
Route::get('/wallet/{address}/tokens', [WalletController::class, 'walletTokens']);
Route::match(['get', 'put'], '/wallet/{address}/tokens/settings', [WalletController::class, 'walletTokenSettings']);
Route::get('/wallet/{address}/tokens/search', [WalletController::class, 'walletTokenSearch']);
Route::get('/wallet/protocols', [WalletController::class, 'protocols']);
Route::get('/wallet/overview', [WalletController::class, 'overview']);
Route::post('/wallet/swap/price', [WalletController::class, 'swapPrice']);
Route::post('/wallet/swap/quote', [WalletController::class, 'swapQuote']);
Route::get('/transparency/overview', [WalletController::class, 'transparencyOverview']);
Route::get('/rwa/admin-caps', [RwaAdminCapController::class, 'index']);
Route::post('/rwa/admin-caps', [RwaAdminCapController::class, 'store']);
Route::delete('/rwa/admin-caps/{id}', [RwaAdminCapController::class, 'destroy']);
Route::get('/fund/tokens', [FundTokenController::class, 'index']);
Route::post('/fund/tokens', [FundTokenController::class, 'store']);
Route::put('/fund/tokens/{id}', [FundTokenController::class, 'update']);
Route::delete('/fund/tokens/{id}', [FundTokenController::class, 'destroy']);
Route::get('/fund/share-settings', [FundShareSettingsController::class, 'show']);
Route::put('/fund/share-settings', [FundShareSettingsController::class, 'update']);

// ── Orders (Zakaz) API ─────────────────────────────────────────────────────

Route::post('/order', [ZakazController::class, 'store']);
Route::get('/orders', [ZakazController::class, 'index']);
Route::middleware('auth:sanctum')->get('/my-orders', [ZakazController::class, 'apiOrders']);
