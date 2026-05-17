<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\AgentCommunicationController;
use App\Http\Controllers\AgentTaskController;
use App\Http\Controllers\AiChatController;
use App\Http\Controllers\AiKnowledgeBaseController;
use App\Http\Controllers\AiKnowledgeCategoryController;
use App\Http\Controllers\AiToolController;
use App\Http\Controllers\AiVoiceController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BackendAgentChatController;
use App\Http\Controllers\BannerCarouselController;
use App\Http\Controllers\FundPoolController;
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

// ── AI Voice (Speech-to-Text & Text-to-Speech) ─────────────────────────────────────
Route::middleware(['api', 'throttle:20,1'])->post('/ai/voice/stt', [AiVoiceController::class, 'stt']);
Route::middleware(['api', 'throttle:20,1'])->post('/ai/voice/tts', [AiVoiceController::class, 'tts']);

// ── AI Chat & Knowledge Base ─────────────────────────────────────────────────────
Route::middleware(['api', 'throttle:20,1'])->post('/ai/chat', [AiChatController::class, 'chat']);
Route::middleware(['api', 'throttle:30,1'])->group(function () {
    Route::get('/ai/chat/sessions', [AiChatController::class, 'sessions']);
    Route::get('/ai/chat/sessions/{sessionToken}/history', [AiChatController::class, 'history']);
    Route::patch('/ai/chat/sessions/{sessionToken}/archive', [AiChatController::class, 'archive']);
    Route::delete('/ai/chat/sessions/{sessionToken}', [AiChatController::class, 'destroy']);
});
Route::middleware(['api', 'throttle:60,1'])->group(function () {
    Route::get('/ai/knowledge-base', [AiKnowledgeBaseController::class, 'index']);
    Route::post('/ai/knowledge-base', [AiKnowledgeBaseController::class, 'store']);

    // Knowledge Base Categories (must be before /{id} routes)
    Route::get('/ai/knowledge-base/categories', [AiKnowledgeCategoryController::class, 'index']);
    Route::get('/ai/knowledge-base/categories/all', [AiKnowledgeCategoryController::class, 'all']);
    Route::post('/ai/knowledge-base/categories', [AiKnowledgeCategoryController::class, 'store']);
    Route::get('/ai/knowledge-base/categories/{id}', [AiKnowledgeCategoryController::class, 'show']);
    Route::put('/ai/knowledge-base/categories/{id}', [AiKnowledgeCategoryController::class, 'update']);
    Route::delete('/ai/knowledge-base/categories/{id}', [AiKnowledgeCategoryController::class, 'destroy']);

    Route::get('/ai/knowledge-base/{id}', [AiKnowledgeBaseController::class, 'show']);
    Route::put('/ai/knowledge-base/{id}', [AiKnowledgeBaseController::class, 'update']);
    Route::delete('/ai/knowledge-base/{id}', [AiKnowledgeBaseController::class, 'destroy']);
    Route::post('/ai/knowledge-base/search', [AiKnowledgeBaseController::class, 'search']);
    Route::post('/ai/knowledge-base/fetch', [AiKnowledgeBaseController::class, 'fetchAndSave']);
    Route::post('/ai/knowledge-base/save', [AiKnowledgeBaseController::class, 'saveInformation']);
    Route::post('/ai/chat/export', [AiKnowledgeBaseController::class, 'exportChat']);

    // AI Tools (function calling definitions)
    Route::get('/ai/tools', [AiToolController::class, 'index']);
    Route::get('/ai/tools/all', [AiToolController::class, 'all']);
    Route::post('/ai/tools', [AiToolController::class, 'store']);
    Route::get('/ai/tools/{id}', [AiToolController::class, 'show']);
    Route::put('/ai/tools/{id}', [AiToolController::class, 'update']);
    Route::delete('/ai/tools/{id}', [AiToolController::class, 'destroy']);
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
Route::get('/fund/pools', [FundPoolController::class, 'index']);
Route::post('/fund/pools', [FundPoolController::class, 'store']);
Route::get('/fund/pools/{id}/events', [FundPoolController::class, 'events']);
Route::put('/fund/pools/{id}', [FundPoolController::class, 'update']);
Route::delete('/fund/pools/{id}', [FundPoolController::class, 'destroy']);
Route::get('/fund/share-settings', [FundShareSettingsController::class, 'show']);
Route::put('/fund/share-settings', [FundShareSettingsController::class, 'update']);

// ── Orders (Zakaz) API ─────────────────────────────────────────────────────

Route::post('/order', [ZakazController::class, 'store']);

// ── Telegram Bot Webhook ───────────────────────────────────────────────────
// Принимает POST от Telegram. Секретный ключ передаётся в URL.
// Пример: POST /api/telegram/webhook/ваш_секретный_ключ
// Защищён секретным ключом, проверяется в контроллере.
Route::post('/telegram/webhook/{secret?}', App\Http\Controllers\TelegramWebhookController::class);
Route::get('/orders', [ZakazController::class, 'index']);
Route::middleware('auth:sanctum')->get('/my-orders', [ZakazController::class, 'apiOrders']);

// ── Agent System ────────────────────────────────────────────────────────────
// Система агентов: BackendAgent, TelegramAgent, FrontendAgent
// Задачи, коммуникации, чат с BackendAgent

Route::prefix('agent')->middleware(['api', 'throttle:30,1'])->group(function () {

    // ── BackendAgent Chat ────────────────────────────────────────────────
    Route::post('/backend/chat', [BackendAgentChatController::class, 'chat']);
    Route::get('/backend/tasks', [BackendAgentChatController::class, 'tasks']);

    // ── Agent Tasks ──────────────────────────────────────────────────────
    Route::post('/tasks', [AgentTaskController::class, 'store']);
    Route::get('/tasks', [AgentTaskController::class, 'index']);
    Route::get('/tasks/{uuid}', [AgentTaskController::class, 'show']);
    Route::patch('/tasks/{uuid}/status', [AgentTaskController::class, 'updateStatus']);

    // ── Agent Communications ─────────────────────────────────────────────
    Route::get('/communications', [AgentCommunicationController::class, 'index']);
    Route::post('/communications', [AgentCommunicationController::class, 'store']);
});
