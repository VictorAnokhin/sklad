<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * TaxReceipt - Управление чеками, зарегистрированными в налоговой инспекции Украины
 */
class TaxReceipt extends Model
{
    protected $table = 'tax_receipts';
    public $timestamps = true;
    protected $guarded = [];
    protected $casts = [
        'registered_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ── Konstante ──────────────────────────────────────────────────────────

    const STATUS_PENDING = 'pending';
    const STATUS_REGISTERED = 'registered';
    const STATUS_FAILED = 'failed';

    const DOC_TYPE_INCOME = 'PO';
    const DOC_TYPE_OUTCOME = 'RO';

    // ── API конфигурация ──────────────────────────────────────────────────

    /**
     * Получить конфигурацию API налоговой инспекции Украины
     */
    public static function getApiConfig(): array
    {
        return [
            'base_url' => env('TAX_API_URL', 'https://api.tax.gov.ua'),
            'api_key' => env('TAX_API_KEY', ''),
            'secret_key' => env('TAX_API_SECRET', ''),
            'timeout' => env('TAX_API_TIMEOUT', 30),
        ];
    }

    // ── Регистрация чека ──────────────────────────────────────────────────

    /**
     * Регистрировать чек в налоговой инспекции
     */
    public function registerAtTaxOffice(): bool
    {
        try {
            $config = self::getApiConfig();
            if (empty($config['api_key'])) {
                $this->recordError('API ключ налоговой не сконфигурирован');
                return false;
            }

            $payload = $this->buildTaxApiPayload();
            
            $response = Http::timeout($config['timeout'])
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $config['api_key'],
                    'Content-Type' => 'application/json',
                ])
                ->post($config['base_url'] . '/receipts/register', $payload);

            if ($response->successful()) {
                $data = $response->json();
                $this->update([
                    'registration_status' => self::STATUS_REGISTERED,
                    'tax_office_receipt_id' => $data['receipt_id'] ?? $this->receipt_number,
                    'tax_office_response' => json_encode($data),
                    'registered_at' => now(),
                ]);
                Log::info("Tax receipt registered: {$this->receipt_number}", $data);
                return true;
            } else {
                $errorMsg = $response->json()['message'] ?? $response->body();
                $this->recordError('Ошибка регистрации: ' . $errorMsg);
                return false;
            }
        } catch (\Exception $e) {
            $this->recordError('Исключение: ' . $e->getMessage());
            Log::error("Failed to register tax receipt: {$this->receipt_number}", ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Построить payload для API налоговой
     */
    protected function buildTaxApiPayload(): array
    {
        return [
            'receipt_number' => $this->receipt_number,
            'document_id' => $this->document_id,
            'document_type' => $this->document_type,
            'taxpayer_id' => $this->taxpayer_id,
            'cashier_name' => $this->cashier_name,
            'amount' => (float) $this->amount,
            'goods_description' => $this->goods_description,
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * Записать ошибку
     */
    protected function recordError(string $error): void
    {
        $this->update([
            'registration_status' => self::STATUS_FAILED,
            'error_message' => $error,
        ]);
    }

    // ── Статические методы ────────────────────────────────────────────────

    /**
     * Создать чек для документа (PO/RO)
     */
    public static function createForDocument(
        int $firma,
        string $documentId,
        string $documentType,
        string $taxpayerId,
        string $cashierName,
        float $amount,
        ?string $goodsDescription = null
    ): self {
        $receipt = new self();
        $receipt->firma = $firma;
        $receipt->document_id = $documentId;
        $receipt->document_type = $documentType;
        $receipt->receipt_number = self::generateReceiptNumber($firma);
        $receipt->taxpayer_id = $taxpayerId;
        $receipt->cashier_name = $cashierName;
        $receipt->amount = $amount;
        $receipt->goods_description = $goodsDescription ?? '';
        $receipt->registration_status = self::STATUS_PENDING;
        $receipt->save();

        return $receipt;
    }

    /**
     * Сгенерировать уникальный номер чека
     */
    protected static function generateReceiptNumber(int $firma): string
    {
        $timestamp = now()->format('YmdHis');
        $random = str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
        $count = self::where('firma', $firma)->count() + 1;
        return sprintf('RCP-%d-%s-%d', $firma, $timestamp, $random);
    }

    /**
     * Получить статистику по фирме
     */
    public static function getStatistics(int $firma): array
    {
        return [
            'total' => self::where('firma', $firma)->count(),
            'registered' => self::where('firma', $firma)->where('registration_status', self::STATUS_REGISTERED)->count(),
            'pending' => self::where('firma', $firma)->where('registration_status', self::STATUS_PENDING)->count(),
            'failed' => self::where('firma', $firma)->where('registration_status', self::STATUS_FAILED)->count(),
        ];
    }

    /**
     * Получить список для UI
     */
    public static function getForUI(int $firma, int $limit = 50, int $offset = 0): array
    {
        $receipts = self::where('firma', $firma)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->offset($offset)
            ->get();

        return $receipts->map(fn ($r) => [
            'id' => $r->id,
            'receipt_number' => $r->receipt_number,
            'document_id' => $r->document_id,
            'document_type' => $r->document_type,
            'amount' => $r->amount,
            'status' => $r->registration_status,
            'registered_at' => $r->registered_at?->format('Y-m-d H:i'),
            'error_message' => $r->error_message,
        ])->toArray();
    }
}
