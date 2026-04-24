<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BannerCarouselController;
use App\Http\Controllers\GoodsController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\ZakazController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('api')->prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'apiRegister']);
    Route::post('/login', [AuthController::class, 'apiLogin']);
    Route::post('/google', [AuthController::class, 'apiGoogleLogin']);
    Route::post('/web3/challenge', [AuthController::class, 'web3LoginChallenge']);
    Route::post('/web3/login', [AuthController::class, 'web3Login']);
    Route::middleware('auth:sanctum')->get('/user', [AuthController::class, 'apiUser']);
    Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'apiLogout']);
    Route::middleware('auth:sanctum')->put('/profile', [AuthController::class, 'apiUpdateProfile']);
    Route::middleware('auth:sanctum')->post('/wallet/challenge', [AuthController::class, 'web3LinkChallenge']);
    Route::middleware('auth:sanctum')->post('/wallet/link', [AuthController::class, 'linkWallet']);
    Route::middleware('auth:sanctum')->post('/wallet/update-token-data', [WalletController::class, 'updateTokenData']);
});

// ── Goods API ─────────────────────────────────────────────────────────────

Route::get('/goods/search', [GoodsController::class, 'searchWeb']);
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
Route::get('/wallet/protocols', [WalletController::class, 'protocols']);
Route::get('/wallet/overview', [WalletController::class, 'overview']);

// ── Orders (Zakaz) API ─────────────────────────────────────────────────────

Route::post('/order', [ZakazController::class, 'store']);
Route::get('/orders', [ZakazController::class, 'index']);
Route::middleware('auth:sanctum')->get('/my-orders', [ZakazController::class, 'apiOrders']);
