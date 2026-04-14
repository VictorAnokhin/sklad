<?php

namespace App\Services;

use App\Models\Document;
use App\Models\ZBody;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * DocumentService
 * Migrated from: library/doc-run.php
 *
 * Handles: save (head), save (body/z_body), provodka (stock movements + cash)
 */
class DocumentService
{
    // ── Save document header ──────────────────────────────────────────────────

    public function saveHead(Request $request, string $docId, string $docType, string $fid): void
    {
        Log::info('saveHead called', [
            'docType' => $docType,
            'docId' => $docId,
            'fid' => $fid,
        ]);
        
        $table = Document::tableForType($docType);
        $summa = (float) $request->input('summa', 0);

        if (!in_array($docType, ['PO', 'RO', 'PP'], true)) {
            $summa = collect($request->input('psumma', []))
                ->reduce(function (float $carry, $value) {
                    return $carry + (float) $value;
                }, 0.0);
        }

        $data = [
            'data' => curdate($request->input('data', date('d-m-Y'))),
            'data2' => curdate($request->input('data2', date('d-m-Y'))),
            'content' => $request->input('content', ''),
            'ttn' => $request->input('ttn', ''),
            'status' => $request->input('status', '0'),
            'summa' => $summa,
            'summa2' => (float)$request->input('summa2', 0),
            'discount' => (float)$request->input('discount', 0),
            'oplata' => $request->input('oplata', ''),
            'oplata2' => $request->input('oplata2', ''),
            'sklads' => $request->input('sklads', ''),
            'reteil' => $request->input('reteil', ''),
            'reestr' => $request->input('reestr', ''),
            'docum' => $request->input('docum', ''),
            'typeproduct' => $request->input('typeproduct', ''),
            'manager' => $request->input('manager', session('login', '')),
            'money' => $request->input('money', ''),
            'bonus' => (float)$request->input('bonus', 0),
            'sms_flag' => $request->input('sms_flag', '0'),
            'schet' => $request->input('schet', ''),
            'num' => $request->input('num', ''),
            'time' => $request->input('time', ''),
        ];

        $existingColumns = Schema::getColumnListing($table);
        Log::info('saveHead columns info', [
            'table' => $table,
            'existingColumns' => $existingColumns,
            'dataKeys' => array_keys($data),
        ]);
        
        $data = array_intersect_key($data, array_flip($existingColumns));
        foreach (['content', 'ttn', 'status', 'oplata', 'oplata2', 'sklads', 'reteil', 'reestr', 'docum', 'typeproduct', 'manager', 'money', 'sms_flag', 'schet', 'num', 'time'] as $stringField) {
            if (array_key_exists($stringField, $data) && $data[$stringField] === null) {
                $data[$stringField] = '';
            }
        }

        // Ensure we save a manually changed client1 via the form
        if (in_array('client1', $existingColumns, true) && $request->has('client1') && $request->input('client1') !== '') {
            $data['client1'] = $request->input('client1');
        }

        // numdoc auto-increment for PN / RN / CH
        if (
            in_array('numdoc', $existingColumns, true)
            && in_array($docType, ['PN', 'RN', 'CH'], true)
            && $request->input('numdoc', '') === ''
        ) {
            $last = DB::table($table)->where('firma', $fid)->max('numdoc');
            $data['numdoc'] = $last ? (string)((int)$last + 1) : '1';
        }
        elseif (in_array('numdoc', $existingColumns, true) && $request->filled('numdoc')) {
            $data['numdoc'] = $request->input('numdoc');
        }

        Log::info('saveHead about to update', [
            'table' => $table,
            'docId' => $docId,
            'data' => $data,
        ]);
        
        $affectedRows = DB::table($table)->where('id', $docId)->update($data);
        
        Log::info('saveHead update completed', [
            'affectedRows' => $affectedRows,
        ]);

        // ── Update users_cashe (balance cache) ────────────────────────────────
        $client1 = DB::table($table)->where('id', $docId)->value('client1');
        if ($client1) {
            $this->updateCache((string)$client1, $fid);
        }

        // ── Notifications ─────────────────────────────────────────────────────
        $smsFlag = $data['sms_flag'];
        if ($smsFlag === '1') {
            $this->sendSms($docId, $docType, $fid, $request);
        }
        if ($smsFlag === '2' || $smsFlag === '3') {
            $this->sendTelegram($docId, $docType, $fid, $request);
        }
    }

    // ── Save z_body rows ──────────────────────────────────────────────────────

    public function saveBody(Request $request, string $docId, string $docType, string $fid): void
    {
        $table = Document::tableForType($docType);
        $doc = DB::table($table)->where('id', $docId)->first();
        if (!$doc)
            return;

        $numz = (string) ($docType === 'RN' || $docType === 'PN' ? $doc->num : $doc->numz);
        $rowType = in_array($docType, ['RN', 'PN'], true) ? $docType : (string) $doc->typez;
        $lineDocId = in_array($docType, ['ZIN', 'ZOUT', 'RN', 'PN'], true)
            ? $docId
            : (string) ($doc->docid ?: $docId);

        $pnums = $request->input('pnum', []);
        $pids = $request->input('pid', []);
        $counts = $request->input('pcount', []);
        $prices = $request->input('pprice', []);
        $summas = $request->input('psumma', []);

        foreach ($pnums as $i => $pnum) {
            if ($pnum === '' || $pnum === '0')
                continue;

            $exists = ZBody::where('docid', $lineDocId)
                ->where('pnum', $pnum)
                ->first();

            $costValue = '';
            if ($docType === 'RN') {
                $costValue = (string) ($exists->zvalue ?? '') !== ''
                    ? (string) $exists->zvalue
                    : \App\Models\ZBody::resolveUnitCost($pnum, $fid);
            }

            $row = [
                'docnum' => $numz,
                'pid' => $pids[$i] ?? '',
                'pnum' => $pnum,
                'pcount' => (float)($counts[$i] ?? 1),
                'pprice' => (float)($prices[$i] ?? 0),
                'psumma' => (float)($summas[$i] ?? 0),
                'type' => $rowType,
                'firma' => $fid,
                'docid' => $lineDocId,
                'zvalue' => $costValue,
            ];

            if ($exists) {
                // If it exists, update by docid + pnum
                ZBody::where('docid', $lineDocId)->where('pnum', $pnum)->update($row);
            }
            else {
                ZBody::create($row);
            }
        }
    }

    // ── Provodka (stock movements + cash) ────────────────────────────────────

    public function provodka(Request $request): bool
    {
        $docId = $request->input('doc_id', session('doc_id', '0'));
        $docType = $request->input('doc', session('doc', ''));
        $fid = session('fid', '');
        $table = Document::tableForType($docType);

        $doc = DB::table($table)->where('id', $docId)->first();
        if (!$doc) {
            return false;
        }

        $lineItems = ZBody::where('docid', $docId)->get();
        $summa = (float)$doc->summa;
        $oplata = (string)$doc->oplata;
        $client1 = (string)$doc->client1;
        $numz = (string)$doc->numz;
        $typez = (string)$doc->typez;
        $parentDocId = (int)($doc->docid ?? 0);
        $isPosted = (int)($doc->provodka ?? 0) === 1;
        $direction = $isPosted ? -1 : 1;

        DB::beginTransaction();
        try {
            // ── Stock movements ───────────────────────────────────────────────
            foreach ($lineItems as $item) {
                $pnum = $item->pnum;
                $count = (float)$item->pcount;

                $priceQuery = DB::table('price')
                    ->where('pnum', $pnum)
                    ->where('firma', $fid);

                match ($docType) {
                    'PN' => $this->applyColumnDelta(clone $priceQuery, 'count', $direction * $count),
                    'RN', 'WO1' => $this->applyColumnDelta(clone $priceQuery, 'count', -1 * $direction * $count),
                    'ZOUT' => $this->applyColumnDelta(clone $priceQuery, 'reserved', $direction * $count),
                    'VN' => $this->applyColumnDelta(clone $priceQuery, 'count', $direction * $count),
                    'AO' => $this->applyColumnDelta(clone $priceQuery, 'count', -1 * $direction * $count),
                    default => null,
                };
            }

            // ── Cash movements ────────────────────────────────────────────────
            if (in_array($docType, ['PO', 'RO', 'PP'], true)) {
                $sign = $docType === 'RO' ? -1 : 1;
                $kasId = $oplata;
                $confColumns = Schema::getColumnListing('conf');
                $delta = $sign * $summa * $direction;

                if (in_array($docType, ['PO', 'RO'], true) && in_array('value', $confColumns, true)) {
                    $currentValue = (float) DB::table('conf')
                        ->where('id', $kasId)
                        ->where('type', 'oplata')
                        ->where('firma', $fid)
                        ->value('value');

                    DB::table('conf')
                        ->where('id', $kasId)
                        ->where('type', 'oplata')
                        ->where('firma', $fid)
                        ->update(['value' => $currentValue + $delta]);
                }
                else {
                    $this->applyColumnDelta(
                        DB::table('kassa')->where('id', $kasId),
                        'balance',
                        $delta
                    );
                }
            }

            DB::table($table)->where('id', $docId)->update(['provodka' => $isPosted ? 0 : 1]);

            $this->refreshLinkedOrderCloseState($docType, $typez, $numz, $parentDocId, $fid);
            $this->refreshLinkedOrderPostingState($docType, $typez, $numz, $parentDocId, $fid);
            if ($client1 !== '') {
                $this->updateCache($client1, $fid);
            }

            DB::commit();
            return !$isPosted;
        }
        catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Provodka failed', ['docId' => $docId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    private function refreshLinkedOrderCloseState(string $docType, string $typez, string $numz, int $parentDocId, string $fid): void
    {
        if ($docType !== 'PO') {
            return;
        }

        if ($parentDocId <= 0 && $typez !== 'ZOUT') {
            return;
        }

        $zout = $parentDocId > 0
            ? DB::table('document')
                ->where('id', $parentDocId)
                ->where('type', 'ZOUT')
                ->where('firma', $fid)
                ->first()
            : null;

        if (!$zout && $numz !== '0') {
            $zout = DB::table('document')
                ->where('num', $numz)
                ->where('type', 'ZOUT')
                ->where('firma', $fid)
                ->first();
        }

        if (!$zout) {
            return;
        }

        $paidQuery = DB::table('z_document')
            ->where('type', 'PO')
            ->where('firma', $fid)
            ->where('provodka', 1);

        if ($parentDocId > 0) {
            $paidQuery->where('docid', $zout->id);
        } else {
            $paidQuery
                ->where('numz', $numz)
                ->where('typez', 'ZOUT');
        }

        $paid = (float) $paidQuery->sum('summa');

        DB::table('document')
            ->where('id', $zout->id)
            ->update(['close' => $paid >= (float) $zout->summa ? 1 : 0]);
    }

    private function refreshLinkedOrderPostingState(string $docType, string $typez, string $numz, int $parentDocId, string $fid): void
    {
        if (!in_array($docType, ['RN', 'PO'], true)) {
            return;
        }

        if ($parentDocId <= 0 && $typez !== 'ZOUT') {
            return;
        }

        $zout = $parentDocId > 0
            ? DB::table('document')
                ->where('id', $parentDocId)
                ->where('type', 'ZOUT')
                ->where('firma', $fid)
                ->first()
            : null;

        if (!$zout && $numz !== '0') {
            $zout = DB::table('document')
                ->where('num', $numz)
                ->where('type', 'ZOUT')
                ->where('firma', $fid)
                ->first();
        }

        if (!$zout) {
            return;
        }

        $postedChildrenBase = DB::table('z_document')
            ->where('firma', $fid)
            ->where('provodka', 1);

        if ($parentDocId > 0) {
            $postedChildrenBase->where('docid', $zout->id);
        } else {
            $postedChildrenBase
                ->where('numz', $numz)
                ->where('typez', 'ZOUT');
        }

        $hasPostedRn = (clone $postedChildrenBase)
            ->where('type', 'RN')
            ->exists();

        $hasPostedPo = (clone $postedChildrenBase)
            ->where('type', 'PO')
            ->exists();

        if ($hasPostedRn && $hasPostedPo) {
            DB::table('document')
                ->where('id', $zout->id)
                ->update(['provodka' => 1]);
            return;
        }

        if (!$hasPostedRn && !$hasPostedPo) {
            DB::table('document')
                ->where('id', $zout->id)
                ->update(['provodka' => 0]);
        }
    }

    private function applyColumnDelta($query, string $column, float $delta): void
    {
        if ($delta > 0) {
            $query->increment($column, $delta);
            return;
        }

        if ($delta < 0) {
            $query->decrement($column, abs($delta));
        }
    }

    // ── Update balance cache ──────────────────────────────────────────────────

    private function updateCache(string $userId, string $fid): void
    {
        $zout = DB::table('document')
            ->where('client1', $userId)->where('firma', $fid)
            ->where('type', 'ZOUT')->sum('summa');
        $paid = DB::table('z_document')
            ->where('client1', $userId)->where('firma', $fid)
            ->where('type', 'PO')->where('provodka', 1)->sum('summa');
        $balance = (float)$paid - (float)$zout;

        DB::table('users_cashe')->updateOrInsert(
            ['userid' => $userId],
            [
                'balance' => $balance,
                'firma' => (int) $fid,
                'user_id' => (int) $userId,
            ]
        );
    }

    // ── SMS (smsclub.mobi) ────────────────────────────────────────────────────

    private function sendSms(string $docId, string $docType, string $fid, Request $request): void
    {
        $phone = DB::table('document as d')
            ->join('users', 'users.id', '=', 'd.client1')
            ->where('d.id', $docId)
            ->value('users.phone');
        if (!$phone)
            return;

        $text = $request->input('sms_text', '');
        if ($text === '')
            return;

        try {
            Http::withToken(config('services.sms.api_key'))
                ->post('https://sms.smsclub.mobi/sms/send', [
                'phone' => [$phone],
                'message' => $text,
                'src_addr' => config('services.sms.sender', 'av8fund'),
            ]);
        }
        catch (\Throwable $e) {
            Log::warning('SMS failed', ['phone' => $phone, 'error' => $e->getMessage()]);
        }
    }

    // ── Telegram ─────────────────────────────────────────────────────────────

    private function sendTelegram(string $docId, string $docType, string $fid, Request $request): void
    {
        $token = config('services.telegram.bot_token');
        $chatId = config('services.telegram.chat_id');
        if (!$token || !$chatId)
            return;

        $text = $request->input('sms_text', '') ?: "Документ #{$docId} ({$docType}) збережено";

        try {
            Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $text,
            ]);
        }
        catch (\Throwable $e) {
            Log::warning('Telegram failed', ['error' => $e->getMessage()]);
        }
    }
}
