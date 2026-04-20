<?php

namespace App\Http\Controllers;

use App\Models\TaxReceipt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TaxReceiptController extends Controller
{
    /**
     * Получить список чеков для компании
     */
    public function index(Request $request)
    {
        $fid = session('fid', '');
        $limit = $request->input('limit', 50);
        $offset = $request->input('offset', 0);

        $receipts = TaxReceipt::where('firma', $fid)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->offset($offset)
            ->get();

        $total = TaxReceipt::where('firma', $fid)->count();

        return response()->json([
            'data' => $receipts->map(fn ($r) => [
                'id' => $r->id,
                'receipt_number' => $r->receipt_number,
                'document_id' => $r->document_id,
                'document_type' => $r->document_type,
                'taxpayer_id' => $r->taxpayer_id,
                'amount' => $r->amount,
                'status' => $r->registration_status,
                'registered_at' => $r->registered_at?->format('Y-m-d H:i:s'),
                'error_message' => $r->error_message,
            ])->toArray(),
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
        ]);
    }

    /**
     * Создать новый чек
     */
    public function store(Request $request)
    {
        $fid = session('fid', '');
        
        $validated = $request->validate([
            'document_id' => 'required|string|max:100',
            'document_type' => 'required|in:PO,RO,OTHER',
            'taxpayer_id' => 'required|string|max:50',
            'cashier_name' => 'nullable|string|max:255',
            'amount' => 'required|numeric|min:0',
            'goods_description' => 'nullable|string',
        ]);

        $receipt = TaxReceipt::createForDocument(
            firma: (int) $fid,
            documentId: $validated['document_id'],
            documentType: $validated['document_type'],
            taxpayerId: $validated['taxpayer_id'],
            cashierName: $validated['cashier_name'] ?? 'Unknown',
            amount: $validated['amount'],
            goodsDescription: $validated['goods_description']
        );

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $receipt->id,
                'receipt_number' => $receipt->receipt_number,
                'status' => $receipt->registration_status,
            ]
        ]);
    }

    /**
     * Удалить чек (только pending)
     */
    public function destroy(Request $request)
    {
        $fid = session('fid', '');
        $id = $request->input('id');

        $receipt = TaxReceipt::where('id', $id)->where('firma', $fid)->first();
        if (!$receipt) {
            return response()->json(['error' => 'Чек не найден'], 404);
        }

        if ($receipt->registration_status !== TaxReceipt::STATUS_PENDING) {
            return response()->json(['error' => 'Можно удалять только зарегистрированные чеки'], 422);
        }

        $receipt->delete();
        return response()->json(['success' => true]);
    }

    /**
     * Получить статистику
     */
    public function statistics(Request $request)
    {
        $fid = session('fid', '');
        $stats = TaxReceipt::getStatistics((int) $fid);

        return response()->json(['data' => $stats]);
    }

    /**
     * Зарегистрировать чек в налоговой
     */
    public function register(Request $request)
    {
        $fid = session('fid', '');
        $id = $request->input('id');

        $receipt = TaxReceipt::where('id', $id)->where('firma', $fid)->first();
        if (!$receipt) {
            return response()->json(['error' => 'Чек не найден'], 404);
        }

        $success = $receipt->registerAtTaxOffice();
        
        return response()->json([
            'success' => $success,
            'status' => $receipt->registration_status,
            'error' => $receipt->error_message,
            'tax_receipt_id' => $receipt->tax_office_receipt_id,
        ]);
    }

    /**
     * Зарегистрировать все pending чеки
     */
    public function registerPending(Request $request)
    {
        $fid = session('fid', '');
        
        $pending = TaxReceipt::where('firma', $fid)
            ->where('registration_status', TaxReceipt::STATUS_PENDING)
            ->get();

        $registered = 0;
        $failed = 0;

        foreach ($pending as $receipt) {
            if ($receipt->registerAtTaxOffice()) {
                $registered++;
            } else {
                $failed++;
            }
        }

        return response()->json([
            'success' => true,
            'registered' => $registered,
            'failed' => $failed,
        ]);
    }

    /**
     * Получить настройки API
     */
    public function getSettings(Request $request)
    {
        $fid = session('fid', '');
        
        $config = TaxReceipt::getApiConfig();
        
        return response()->json([
            'api_configured' => !empty($config['api_key']),
            'base_url' => $config['base_url'],
            'timeout' => $config['timeout'],
        ]);
    }

    /**
     * Сохранить настройки API
     */
    public function saveSettings(Request $request)
    {
        // Это должно быть сохранено через env или settings таблицу
        // Здесь только валидация
        $validated = $request->validate([
            'api_key' => 'nullable|string',
            'secret_key' => 'nullable|string',
            'base_url' => 'nullable|url',
        ]);

        // TODO: Сохранить в database или .env через panel
        return response()->json(['success' => true]);
    }
}
