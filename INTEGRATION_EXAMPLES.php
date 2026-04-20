<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Примеры интеграции с MoneyController для автоматической регистрации чеков
 * 
 * Добавьте эти фрагменты в MoneyController::save() метод
 */

// ════════════════════════════════════════════════════════════════════════════════════════

/**
 * ВАРИАНТ 1: Автоматическая регистрация при сохранении PO документа
 * 
 * Добавить в MoneyController::save() после сохранения документа:
 */

// Пример кода для добавления в MoneyController::save()
/*

    public function save(Request $request)
    {
        $fid  = session('fid', '');
        $id   = (int)$request->input('id', 0);
        $type = $request->input('type', 'PO');
        
        // ... существующий код сохранения документа ...
        
        $money = trim((string) $request->input('money', ''));
        $summa = (float) $request->input('summa', 0);
        $content = $request->input('content', '');
        $reestr = $request->input('reestr', '');
        
        // Сохранить денежный документ
        $moneyDoc = Money::updateOrCreate(
            ['id' => $id, 'firma' => $fid],
            [
                'type' => $type,
                'summa' => $summa,
                'content' => $content,
                'money' => $money,
                'reestr' => $reestr,
                // ... другие поля ...
            ]
        );
        
        // ──────────────────────────────────────────────────────────────────
        // ДОБАВИТЬ РЕГИСТРАЦИЮ ЧЕКА (новый код)
        // ──────────────────────────────────────────────────────────────────
        
        // Проверить, нужно ли регистрировать чек
        $shouldRegisterReceipt = $request->boolean('register_receipt', false);
        
        if ($shouldRegisterReceipt && $type === 'PO') {
            $metadata = [
                'taxpayer_id' => session('taxpayer_id', ''),
                'cashier_name' => session('login', 'System'),
                'goods_description' => $content ?: 'Приход грошей',
            ];
            
            $receipt = TaxReceiptService::createReceiptForMoneyDocument(
                firma: (int) $fid,
                documentId: $moneyDoc->id,
                documentType: $type,
                amount: $summa,
                metadata: $metadata
            );
            
            // Если API настроен, зарегистрировать сразу
            if ($receipt && env('TAX_RECEIPTS_AUTO_REGISTER')) {
                $receipt->registerAtTaxOffice();
            }
        }
        
        // ... продолжить существующий код ...
        
        return redirect()->route('money.index', $returnFilters)
            ->with('success', 'Документ збережено');
    }

*/

// ════════════════════════════════════════════════════════════════════════════════════════

/**
 * ВАРИАНТ 2: Добавить checkbox в форму создания/редактирования PO
 * 
 * В resources/views/money/show.blade.php добавить:
 */

/*

<!-- Добавить перед кнопкой сохранения -->
@if($document->type === 'PO' && env('TAX_RECEIPTS_ENABLED'))
    <div class="mb-3">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="register_receipt" 
                   id="register_receipt" value="1" 
                   {{ old('register_receipt') ? 'checked' : '' }}>
            <label class="form-check-label" for="register_receipt">
                🧾 Зареєструвати чек у податковій інспекції
            </label>
            <div class="form-text">Автоматично створити та зареєструвати чек ДПІ для цього документа</div>
        </div>
    </div>
@endif

*/

// ════════════════════════════════════════════════════════════════════════════════════════

/**
 * ВАРИАНТ 3: Добавить метод в DocumentService для автоматической регистрации
 */

class DocumentServiceExtension
{
    /**
     * Регистрировать чек для денежного документа
     * 
     * @param int $firma
     * @param int $moneyDocId
     * @param string $type PO или RO
     * @param float $amount
     * @param array $metadata
     * @param bool $autoRegister
     * @return array
     */
    public static function registerMoneyReceipt(
        int $firma,
        int $moneyDocId,
        string $type,
        float $amount,
        array $metadata = [],
        bool $autoRegister = false
    ): array {
        try {
            $receipt = TaxReceiptService::createReceiptForMoneyDocument(
                firma: $firma,
                documentId: $moneyDocId,
                documentType: $type,
                amount: $amount,
                metadata: $metadata
            );

            if (!$receipt) {
                return [
                    'success' => false,
                    'message' => 'Не вдалось створити чек',
                ];
            }

            $registered = false;
            if ($autoRegister || env('TAX_RECEIPTS_AUTO_REGISTER')) {
                $registered = $receipt->registerAtTaxOffice();
            }

            return [
                'success' => true,
                'receipt_id' => $receipt->id,
                'receipt_number' => $receipt->receipt_number,
                'status' => $receipt->registration_status,
                'registered' => $registered,
                'message' => $registered 
                    ? 'Чек успішно зареєстровано' 
                    : 'Чек створено, очікує на реєстрацію',
            ];
        } catch (\Exception $e) {
            Log::error('Failed to register money receipt', [
                'exception' => $e->getMessage(),
                'money_doc_id' => $moneyDocId,
                'firma' => $firma,
            ]);

            return [
                'success' => false,
                'message' => 'Помилка: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Обновить метаданные чека при изменении документа
     */
    public static function updateReceiptMetadata(
        int $firma,
        int $moneyDocId,
        array $metadata
    ): bool {
        try {
            $receipt = TaxReceiptService::getReceiptByDocument(
                firma: $firma,
                documentId: (string) $moneyDocId,
                documentType: 'PO'  // или получить из параметра
            );

            if (!$receipt) {
                return false;
            }

            // Обновить только если статус pending
            if ($receipt->registration_status === TaxReceiptService::STATUS_PENDING) {
                $receipt->update([
                    'taxpayer_id' => $metadata['taxpayer_id'] ?? $receipt->taxpayer_id,
                    'cashier_name' => $metadata['cashier_name'] ?? $receipt->cashier_name,
                    'goods_description' => $metadata['goods_description'] ?? $receipt->goods_description,
                ]);
                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Failed to update receipt metadata', [
                'exception' => $e->getMessage(),
            ]);
            return false;
        }
    }
}

// ════════════════════════════════════════════════════════════════════════════════════════

/**
 * ВАРИАНТ 4: Middleware для автоматической регистрации всех PO
 * 
 * Создать: app/Http/Middleware/AutoRegisterTaxReceipts.php
 */

/*

namespace App\Http\Middleware;

use App\Services\TaxReceiptService;
use Closure;
use Illuminate\Http\Request;

class AutoRegisterTaxReceipts
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Если это было сохранение денежного документа
        if ($request->routeIs('money.save') && $request->method() === 'POST') {
            $fid = session('fid');
            
            // Попробовать зарегистрировать все pending чеки
            if (env('TAX_RECEIPTS_AUTO_REGISTER')) {
                TaxReceiptService::syncReceiptStatuses((int) $fid);
            }
        }

        return $response;
    }
}

// Зарегистрировать в app/Http/Kernel.php:
// protected $routeMiddleware = [
//     ...
//     'auto_register_receipts' => \App\Http\Middleware\AutoRegisterTaxReceipts::class,
// ];

// Использовать на маршруте:
// Route::post('/money/save', [MoneyController::class, 'save'])
//     ->middleware('auto_register_receipts');

*/

// ════════════════════════════════════════════════════════════════════════════════════════

/**
 * ВАРИАНТ 5: Laravel Command для периодической регистрации чеков
 * 
 * Создать: app/Console/Commands/RegisterPendingTaxReceipts.php
 */

/*

namespace App\Console\Commands;

use App\Models\Firma;
use App\Services\TaxReceiptService;
use Illuminate\Console\Command;

class RegisterPendingTaxReceipts extends Command
{
    protected $signature = 'tax:register-pending';
    protected $description = 'Регистрировать все pending чеки в налоговой';

    public function handle()
    {
        $firmas = Firma::all();

        foreach ($firmas as $firma) {
            $this->info("Processing firma: {$firma->name}");
            
            $result = TaxReceiptService::syncReceiptStatuses($firma->id);
            
            $this->line("  Synced: {$result['synced']}, Updated: {$result['updated']}, Errors: {$result['errors']}");
        }

        $this->info('Done!');
    }
}

// Использование:
// php artisan tax:register-pending

// Добавить в app/Console/Kernel.php для запуска по расписанию:
// protected function schedule(Schedule $schedule)
// {
//     $schedule->command('tax:register-pending')
//         ->everyFiveMinutes()
//         ->withoutOverlapping();
// }

*/

// ════════════════════════════════════════════════════════════════════════════════════════

echo "Примеры интеграции загружены. Смотрите комментарии выше для использования.\n";
