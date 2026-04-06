<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GoodsController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ── Goods API ─────────────────────────────────────────────────────────────

Route::get('/goods/hits', [GoodsController::class, 'getHits']);
Route::get('/goods/search', [GoodsController::class, 'search']);
Route::get('/goods/sections', [GoodsController::class, 'getSections']);
Route::get('/goods/section/{id}', [GoodsController::class, 'getBySection']);
