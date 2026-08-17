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
use App\Http\Controllers\Av8SwapOrderController;
use App\Http\Controllers\BankController;
use App\Http\Controllers\BackendAgentChatController;
use App\Http\Controllers\BannerCarouselController;
use App\Http\Controllers\ComplianceController;
use App\Http\Controllers\CctpProxyController;
use App\Http\Controllers\DashboardAgentChatController;
use App\Http\Controllers\EducationController;
use App\Http\Controllers\FundPoolController;
use App\Http\Controllers\FundShareSettingsController;
use App\Http\Controllers\FundTokenController;
use App\Http\Controllers\GarageVehicleController;
use App\Http\Controllers\GoodsController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\NovaPoshtaController;
use App\Http\Controllers\RwaAdminCapController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SolanaRpcProxyController;
use App\Http\Controllers\TelegramWebchatController;
use App\Http\Controllers\WalrusProxyController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\WebchatIntelligenceController;
use App\Http\Controllers\WidgetIntelligenceController;
use App\Http\Controllers\UserWalletSecretController;
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
    Route::get('/email/resolve-zklogin', [AuthController::class, 'resolveZkLoginWalletByEmail']);
    Route::get('/zklogin/search', [AuthController::class, 'searchZkLoginWallets']);
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
    Route::middleware('auth:sanctum')->get('/kyc', [AuthController::class, 'apiKycStatus']);
    Route::middleware('auth:sanctum')->get('/kyc/image/{type}', [AuthController::class, 'apiKycImage']);
    Route::middleware('auth:sanctum')->post('/kyc/deepseek/check', [AuthController::class, 'apiRunKycDeepSeekCheck']);
    Route::middleware('auth:sanctum')->post('/kyc/passport-photo', [AuthController::class, 'apiUploadKycPassportPhoto']);
    Route::middleware('auth:sanctum')->post('/kyc/passport-back-photo', [AuthController::class, 'apiUploadKycPassportBackPhoto']);
    Route::middleware('auth:sanctum')->post('/kyc/passport-selfie', [AuthController::class, 'apiUploadKycPassportSelfie']);
    Route::middleware('auth:sanctum')->post('/kyc/kep-signature', [AuthController::class, 'apiUploadKycKepSignature']);
    Route::middleware('auth:sanctum')->post('/kyc/liveness-selfie', [AuthController::class, 'apiUploadKycLivenessSelfie']);
    Route::middleware('auth:sanctum')->post('/kyc/sumsub/token', [AuthController::class, 'apiCreateSumsubAccessToken']);
    Route::middleware('auth:sanctum')->post('/wallet/challenge', [AuthController::class, 'web3LinkChallenge']);
    Route::middleware('auth:sanctum')->post('/wallet/link', [AuthController::class, 'linkWallet']);
    Route::middleware('auth:sanctum')->post('/wallet/unlink', [AuthController::class, 'unlinkWallet']);
    Route::middleware('auth:sanctum')->delete('/wallet', [AuthController::class, 'unlinkWallet']);
    Route::middleware('auth:sanctum')->post('/wallet/update-token-data', [WalletController::class, 'updateTokenData']);
    Route::middleware('auth:sanctum')->get('/wallet-secrets/{kind}', [UserWalletSecretController::class, 'show']);
    Route::middleware('auth:sanctum')->put('/wallet-secrets/{kind}', [UserWalletSecretController::class, 'store']);
    Route::middleware('auth:sanctum')->delete('/wallet-secrets/{kind}', [UserWalletSecretController::class, 'destroy']);
});

Route::middleware(['api', 'sui.sponsor.log', 'auth:sanctum'])->post('/sui/shinami/sponsor-transaction', [AuthController::class, 'shinamiSponsorSuiTransaction']);

Route::middleware(['api', 'throttle:10,1'])->put('/walrus/{network}/v1/blobs', [WalrusProxyController::class, 'store']);
Route::middleware(['api', 'throttle:30,1'])->get('/cctp/v2/messages/{domain}', [CctpProxyController::class, 'v2Messages']);
Route::middleware(['api', 'throttle:120,1'])->post('/solana/rpc', [SolanaRpcProxyController::class, 'proxy']);

Route::middleware(['api', 'throttle:120,1'])->post('/v1/widget/handshake', [WidgetIntelligenceController::class, 'handshake']);

Route::middleware(['api', 'throttle:60,1'])->group(function () {
    Route::get('/education/tests/first', [EducationController::class, 'publicFirstTest']);
    Route::post('/education/tests/first/submit', [EducationController::class, 'publicSubmitFirstTest']);
    Route::get('/education/utilities', [EducationController::class, 'publicUtilities']);
    Route::middleware('auth:sanctum')->post('/education/utilities/{utility}/install', [EducationController::class, 'installUtilityForUser'])
        ->where('utility', '[A-Za-z0-9\\-_]+');
    Route::middleware('auth:sanctum')->delete('/education/utilities/{utility}/install', [EducationController::class, 'destroyUserUtility'])
        ->where('utility', '[A-Za-z0-9\\-_]+');
    Route::get('/education/course', [EducationController::class, 'publicCourse']);
    Route::get('/education/course/material/{material}/tests', [EducationController::class, 'publicCourseMaterialTests'])
        ->whereNumber('material');
    Route::post('/education/course/test/submit', [EducationController::class, 'publicSubmitCourseTest']);
    Route::middleware('auth:sanctum')->post('/education/course/order', [EducationController::class, 'ensureCourseOrder']);
    Route::middleware('auth:sanctum')->post('/education/course/payment', [EducationController::class, 'recordCoursePayment']);
    Route::post('/consultation/order', [EducationController::class, 'storeConsultationOrder']);
    Route::middleware('auth:sanctum')->get('/education/profile', [EducationController::class, 'profile']);
    Route::get('/education/know-yourself/tests', [EducationController::class, 'publicKnowYourselfTests']);
    Route::post('/education/know-yourself/submit', [EducationController::class, 'publicSubmitKnowYourselfTest']);
    Route::middleware('auth:sanctum')->post('/education/know-yourself/rating/apply', [EducationController::class, 'applyKnowYourselfRating']);
});

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

// ── Telegram operator bridge for website webchats ─────────────────────────────
Route::middleware(['api', 'throttle:120,1'])->post('/telegram/webchat/webhook/{secret}', [TelegramWebchatController::class, 'webhook']);

// ── AI Chat & Knowledge Base ─────────────────────────────────────────────────────
Route::middleware(['api', 'throttle:20,1'])->post('/ai/chat', [AiChatController::class, 'chat']);
Route::middleware(['api', 'throttle:30,1'])->group(function () {
    Route::get('/ai/chat/sessions', [AiChatController::class, 'sessions']);
    Route::get('/ai/chat/sessions/{sessionToken}/history', [AiChatController::class, 'history']);
    Route::patch('/ai/chat/sessions/{sessionToken}/archive', [AiChatController::class, 'archive']);
    Route::delete('/ai/chat/sessions/{sessionToken}', [AiChatController::class, 'destroy']);
});

// ── Webchat Intelligence: multi-site event tracking and dynamic UI by fid ─────
Route::middleware(['api', 'throttle:120,1'])->group(function () {
    Route::get('/webchat/config', [WebchatIntelligenceController::class, 'config']);
    Route::post('/webchat/events', [WebchatIntelligenceController::class, 'event']);
    Route::get('/webchat/visitor-context', [WebchatIntelligenceController::class, 'visitorContext']);
});
Route::middleware(['api', 'throttle:30,1'])->group(function () {
    Route::get('/webchat/analytics/summary', [WebchatIntelligenceController::class, 'summary']);
    Route::post('/webchat/manager-ai/sync', [WebchatIntelligenceController::class, 'syncManagerAi']);
});
Route::middleware(['api', 'throttle:120,1'])->prefix('external/webchat')->group(function () {
    Route::get('/visitors', [WebchatIntelligenceController::class, 'agentVisitors']);
    Route::get('/visitors/{visitorUid}', [WebchatIntelligenceController::class, 'agentVisitorShow']);
    Route::post('/visitors/upsert', [WebchatIntelligenceController::class, 'agentVisitorUpsert']);
    Route::get('/events', [WebchatIntelligenceController::class, 'agentEvents']);
    Route::post('/events', [WebchatIntelligenceController::class, 'agentEventStore']);
    Route::post('/unmet-needs', [WidgetIntelligenceController::class, 'storeUnmetNeed']);
});
Route::middleware(['api', 'throttle:60,1'])->prefix('external/dashboard-agent-chat')->group(function () {
    Route::match(['get', 'post'], '/context', [DashboardAgentChatController::class, 'agentContext']);
    Route::post('/messages', [DashboardAgentChatController::class, 'agentStore']);
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
    Route::post('/ai/knowledge-base/manager-ai-ingest', [AiKnowledgeBaseController::class, 'managerAiIngest']);
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
Route::middleware(['throttle:20,1'])->post('/goods/agent', [GoodsController::class, 'apiStoreBySecret']);
Route::get('/goods/catalog-filter-groups', [GoodsController::class, 'catalogFilterGroupsApi']);
Route::get('/goods/manager-ai/search', [GoodsController::class, 'managerAiSearch']);
Route::get('/goods/manager-ai/items', [GoodsController::class, 'managerAiItemsIndex']);
Route::get('/goods/manager-ai/items/by-category', [GoodsController::class, 'managerAiItemsByCategory']);
Route::get('/goods/manager-ai/items/by-pnum', [GoodsController::class, 'managerAiItemsByPnum']);
Route::post('/goods/manager-ai/items', [GoodsController::class, 'managerAiItemsStore']);
Route::post('/goods/manager-ai/items/upsert', [GoodsController::class, 'managerAiItemsUpsert']);
Route::put('/goods/manager-ai/items/{id}', [GoodsController::class, 'managerAiItemsUpdate']);
Route::delete('/goods/manager-ai/items/{id}', [GoodsController::class, 'managerAiItemsDestroy']);
Route::get('/goods/manager-ai/{identifier}', [GoodsController::class, 'managerAiShow']);
Route::get('/goods/hits', [GoodsController::class, 'getHits']);
Route::get('/goods/sections', [GoodsController::class, 'getSections']);
Route::get('/goods/section/{id}', [GoodsController::class, 'getBySection']);
Route::middleware('auth:sanctum')->post('/goods/rating/{id}', [GoodsController::class, 'saveRating']);
Route::middleware('auth:sanctum')->post('/goods/{id}/rating', [GoodsController::class, 'saveRating']);
Route::get('/goods/{id}', [GoodsController::class, 'getOne']);
Route::get('/regions', [GoodsController::class, 'getRegions']);
Route::get('/cities', [GoodsController::class, 'getCities']);

// ── News API ──────────────────────────────────────────────────────────────

Route::get('/news', [NewsController::class, 'apiIndex']);
Route::middleware(['throttle:20,1'])->post('/news/agent', [NewsController::class, 'apiStoreBySecret']);
Route::middleware('auth:sanctum')->post('/news', [NewsController::class, 'apiStore']);
Route::middleware('auth:sanctum')->post('/news/{id}/publish', [NewsController::class, 'apiPublish'])->whereNumber('id');
Route::get('/news/{identifier}', [NewsController::class, 'apiShow']);
Route::get('/banners', [BannerCarouselController::class, 'apiIndex']);
Route::middleware(['api', 'throttle:30,1'])->prefix('projects/manager-ai')->group(function () {
    Route::get('/', [SettingsController::class, 'managerAiProjectsIndex']);
    Route::post('/', [SettingsController::class, 'managerAiProjectsStore']);
    Route::put('/{id}', [SettingsController::class, 'managerAiProjectsUpdate']);
});
Route::get('/projects/{id}', [SettingsController::class, 'projectsPublicShow']);
Route::get('/offices', [SettingsController::class, 'officesPublicIndex']);
Route::get('/stocks', [BankController::class, 'publicStockAnalysis']);
Route::get('/stocks/{ticker}', [BankController::class, 'publicStockAnalysisShow'])
    ->where('ticker', '[A-Za-z0-9\\.\\-_]+');
Route::get('/bank/cash-accounts', [BankController::class, 'publicExchangeCashAccounts']);
Route::get('/bank/assets', [BankController::class, 'publicExchangeAssets']);
Route::get('/settings/currencies', [SettingsController::class, 'publicCurrencies']);
Route::get('/settings/faq', [SettingsController::class, 'publicFaq']);
Route::get('/wallet/tokens', [WalletController::class, 'tokens']);
Route::get('/wallet/{address}/performance', [WalletController::class, 'performance']);
Route::get('/wallet/{address}/tokens', [WalletController::class, 'walletTokens']);
Route::match(['get', 'put'], '/wallet/{address}/tokens/settings', [WalletController::class, 'walletTokenSettings']);
Route::get('/wallet/{address}/tokens/search', [WalletController::class, 'walletTokenSearch']);
Route::get('/wallet/protocols', [WalletController::class, 'protocols']);
Route::get('/wallet/manual-defi-positions', [WalletController::class, 'manualDefiPositions']);
Route::post('/wallet/manual-defi-positions', [WalletController::class, 'storeManualDefiPosition']);
Route::delete('/wallet/manual-defi-positions/{id}', [WalletController::class, 'deleteManualDefiPosition']);
Route::get('/wallet/overview', [WalletController::class, 'overview']);
Route::post('/wallet/swap/price', [WalletController::class, 'swapPrice']);
Route::post('/wallet/swap/quote', [WalletController::class, 'swapQuote']);
Route::middleware(['throttle:30,1'])->post('/compliance/screen-incoming-crypto', [ComplianceController::class, 'screenIncomingCrypto']);
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
Route::post('/fund/pools/events', [FundPoolController::class, 'storeEvents']);
Route::get('/fund/pools/positions', [FundPoolController::class, 'allPositions']);
Route::post('/fund/pools/positions', [FundPoolController::class, 'storePositions']);
Route::get('/fund/pools/credit-requests', [FundPoolController::class, 'creditRequests']);
Route::post('/fund/pools/credit-request', [FundPoolController::class, 'storeCreditRequest']);
Route::put('/fund/pools/{id}/credit-request', [FundPoolController::class, 'updateCreditRequestTerms']);
Route::post('/fund/pools/{id}/credit-request/claim', [FundPoolController::class, 'claimCreditRequest']);
Route::post('/fund/pools/{id}/credit-request/payment', [FundPoolController::class, 'payCreditRequestInstallment']);
Route::get('/fund/pools/{id}/events', [FundPoolController::class, 'events']);
Route::get('/fund/pools/{id}/positions', [FundPoolController::class, 'positions']);
Route::put('/fund/pools/{id}', [FundPoolController::class, 'update']);
Route::delete('/fund/pools/{id}', [FundPoolController::class, 'destroy']);
Route::get('/fund/share-settings', [FundShareSettingsController::class, 'show']);
Route::put('/fund/share-settings', [FundShareSettingsController::class, 'update']);
Route::middleware(['api', 'throttle:60,1'])->get('/av8-swap/orders', [Av8SwapOrderController::class, 'index']);
Route::middleware(['api', 'throttle:20,1'])->post('/av8-swap/orders', [Av8SwapOrderController::class, 'store']);
Route::middleware(['api', 'throttle:20,1'])->post('/av8-swap/orders/{id}/payment-receipt', [Av8SwapOrderController::class, 'paymentReceipt']);

// ── Orders (Zakaz) API ─────────────────────────────────────────────────────

Route::middleware(['api', 'throttle:60,1'])->prefix('nova-poshta')->group(function () {
    Route::get('/cities', [NovaPoshtaController::class, 'cities']);
    Route::get('/warehouses', [NovaPoshtaController::class, 'warehouses']);
});

Route::post('/order', [ZakazController::class, 'store']);
Route::middleware('auth:sanctum')->post('/car-request', [ZakazController::class, 'storeCarRequest']);
Route::middleware('auth:sanctum')->get('/garage', [ZakazController::class, 'apiGarage']);
Route::get('/garage/owner/{owner}', [ZakazController::class, 'apiGarageOwner']);
Route::middleware('auth:sanctum')->get('/garage/tracked', [GarageVehicleController::class, 'index']);
Route::middleware('auth:sanctum')->put('/garage/tracked/{vehicle}', [GarageVehicleController::class, 'update']);
Route::get('/garage/tracked/{owner}', [GarageVehicleController::class, 'owner']);
Route::middleware('auth:sanctum')->post('/garage/lookup', [GarageVehicleController::class, 'lookup']);

Route::get('/orders', [ZakazController::class, 'index']);
Route::middleware('auth:sanctum')->get('/my-orders', [ZakazController::class, 'apiOrders']);

// ── Agent System ────────────────────────────────────────────────────────────
// Система агентов: BackendAgent, FrontendAgent
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
