<?php

namespace App\Services;

use App\Models\TaxReceipt;
use App\Models\Money;
use Illuminate\Support\Facades\Log;

/**
 * TaxReceiptService - Сервис для управления чеками ДПІ
 * Интегрирует регистрацию чеков при работе с денежными документами (PO/RO)
 */
class TaxReceiptService
{
    /**
     * Автоматически создать и зарегистрировать чек для денежного документа
     * 
     * @param int $firma - ID компании
     * @param int|string $documentId - ID документа (денежного)
     * @param string $documentType - Тип документа (PO или RO)
     * @param float $amount - Сумма документа
     * @param array $metadata - Доп. данные (ІПН, касір и т.д.)
     * @return TaxReceipt|null
     */
    public static function createReceiptForMoneyDocument(
        int $firma,
        $documentId,
        string $documentType,
        float $amount,
        array $metadata = []
    ): ?TaxReceipt {
        try {
            // Валидация типа документа
            if (!in_array($documentType, ['PO', 'RO'], true)) {
                Log::warning("Invalid tax receipt document type: {$documentType}", ['doc_id' => $documentId]);
                return null;
            }

            // Извлечение метаданных
            $taxpayerId = $metadata['taxpayer_id'] ?? '';
            $cashierName = $metadata['cashier_name'] ?? '';
            $goodsDescription = $metadata['goods_description'] ?? '';

            if (empty($taxpayerId)) {
                Log::warning("Missing taxpayer ID for tax receipt", ['doc_id' => $documentId, 'firma' => $firma]);
                return null;
            }

            // Создать чек
            $receipt = TaxReceipt::createForDocument(
                firma: $firma,
                documentId: (string) $documentId,
                documentType: $documentType,
                taxpayerId: $taxpayerId,
                cashierName: $cashierName,
                amount: $amount,
                goodsDescription: $goodsDescription
            );

            Log::info("Tax receipt created for money document", [
                'receipt_id' => $receipt->id,
                'receipt_number' => $receipt->receipt_number,
                'doc_id' => $documentId,
            ]);

            return $receipt;
        } catch (\Exception $e) {
            Log::error("Failed to create tax receipt for money document", [
                'exception' => $e->getMessage(),
                'doc_id' => $documentId,
                'firma' => $firma,
            ]);
            return null;
        }
    }

    /**
     * Получить настройки интеграции для ДПІ
     */
    public static function getIntegrationSettings(int $firma): array
    {
        return [
            'enabled' => (bool) env('TAX_RECEIPTS_ENABLED', true),
            'auto_register' => (bool) env('TAX_RECEIPTS_AUTO_REGISTER', false),
            'api_configured' => !empty(env('TAX_API_KEY')),
            'api_url' => env('TAX_API_URL', 'https://api.tax.gov.ua'),
        ];
    }

    /**
     * Синхронизировать статусы чеков с ДПІ
     */
    public static function syncReceiptStatuses(int $firma): array
    {
        $results = [
            'synced' => 0,
            'updated' => 0,
            'errors' => 0,
        ];

        try {
            // Получить все pending чеки
            $pending = TaxReceipt::where('firma', $firma)
                ->where('registration_status', TaxReceipt::STATUS_PENDING)
                ->get();

            foreach ($pending as $receipt) {
                // Попробовать зарегистрировать в налоговой
                if ($receipt->registerAtTaxOffice()) {
                    $results['updated']++;
                } else {
                    $results['errors']++;
                }
                $results['synced']++;
            }
        } catch (\Exception $e) {
            Log::error("Failed to sync tax receipt statuses", [
                'exception' => $e->getMessage(),
                'firma' => $firma,
            ]);
        }

        return $results;
    }

    /**
     * Получить статистику по чекам для фирмы
     */
    public static function getReceiptStatistics(int $firma): array
    {
        return TaxReceipt::getStatistics($firma);
    }

    /**
     * Удалить старые непройденные чеки (старше 30 дней)
     */
    public static function cleanupFailedReceipts(int $firma, int $daysOld = 30): int
    {
        $deleted = TaxReceipt::where('firma', $firma)
            ->where('registration_status', TaxReceipt::STATUS_FAILED)
            ->where('created_at', '<', now()->subDays($daysOld))
            ->delete();

        if ($deleted > 0) {
            Log::info("Cleaned up failed tax receipts", [
                'firma' => $firma,
                'deleted' => $deleted,
                'days_old' => $daysOld,
            ]);
        }

        return $deleted;
    }

    /**
     * Получить информацию о чеке по документу
     */
    public static function getReceiptByDocument(int $firma, string $documentId, string $documentType): ?TaxReceipt
    {
        return TaxReceipt::where('firma', $firma)
            ->where('document_id', $documentId)
            ->where('document_type', $documentType)
            ->latest('created_at')
            ->first();
    }

    /**
     * Проверить если для документа уже создан чек
     */
    public static function receiptExistsForDocument(int $firma, string $documentId, string $documentType): bool
    {
        return TaxReceipt::where('firma', $firma)
            ->where('document_id', $documentId)
            ->where('document_type', $documentType)
            ->exists();
    }
}
