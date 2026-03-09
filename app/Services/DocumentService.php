<?php

namespace App\Services;

use App\Models\Document;
use App\Models\ZBody;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
        $table = Document::tableForType($docType);

        $data = [
            'data'        => curdate($request->input('data', date('d-m-Y'))),
            'data2'       => curdate($request->input('data2', date('d-m-Y'))),
            'content'     => convert_to_base($request->input('content', '')),
            'ttn'         => $request->input('ttn', ''),
            'status'      => $request->input('status', '0'),
            'summa'       => (float)$request->input('summa', 0),
            'summa2'      => (float)$request->input('summa2', 0),
            'discount'    => (float)$request->input('discount', 0),
            'oplata'      => $request->input('oplata', ''),
            'oplata2'     => $request->input('oplata2', ''),
            'sklads'      => $request->input('sklads', ''),
            'reteil'      => $request->input('reteil', ''),
            'reestr'      => $request->input('reestr', ''),
            'docum'       => $request->input('docum', ''),
            'typeproduct' => $request->input('typeproduct', ''),
            'schet'       => $request->input('schet', ''),
            'manager'     => convert_to_base($request->input('manager', session('login', ''))),
            'numorder'    => $request->input('numorder', ''),
            'money'       => $request->input('money', ''),
            'bonus'       => (float)$request->input('bonus', 0),
            'sms_flag'    => $request->input('sms_flag', '0'),
        ];

        // numdoc auto-increment for PN / RN / CH
        if (in_array($docType, ['PN', 'RN', 'CH'], true) && $request->input('numdoc', '') === '') {
            $last = DB::table($table)->where('firma', $fid)->max('numdoc');
            $data['numdoc'] = $last ? (string)((int)$last + 1) : '1';
        } elseif ($request->filled('numdoc')) {
            $data['numdoc'] = $request->input('numdoc');
        }

        DB::table($table)->where('id', $docId)->update($data);

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
        $doc   = DB::table($table)->where('id', $docId)->first();
        if (!$doc) return;

        $numz  = (string)$doc->numz;
        $typez = (string)$doc->typez;

        $pnums  = $request->input('pnum',   []);
        $pids   = $request->input('pid',    []);
        $counts = $request->input('pcount', []);
        $prices = $request->input('pprice', []);
        $summas = $request->input('psumma', []);

        foreach ($pnums as $i => $pnum) {
            if ($pnum === '' || $pnum === '0') continue;

            $exists = ZBody::where('docid', $docId)
                            ->where('pnum', $pnum)
                            ->exists();

            $row = [
                'docnum' => $numz,
                'pid'    => $pids[$i]   ?? '',
                'pnum'   => $pnum,
                'pcount' => (float)($counts[$i] ?? 1),
                'pprice' => (float)($prices[$i] ?? 0),
                'psumma' => (float)($summas[$i] ?? 0),
                'type'   => $typez,
                'firma'  => $fid,
                'docid'  => $docId,
            ];

            if ($exists) {
                ZBody::where('docid', $docId)->where('pnum', $pnum)->update($row);
            } else {
                ZBody::create($row);
            }
        }
    }

    // ── Provodka (stock movements + cash) ────────────────────────────────────

    public function provodka(Request $request): void
    {
        $docId   = $request->input('doc_id', session('doc_id', '0'));
        $docType = $request->input('doc',    session('doc', ''));
        $fid     = session('fid', '');
        $table   = Document::tableForType($docType);

        $doc = DB::table($table)->where('id', $docId)->first();
        if (!$doc || (int)$doc->provodka === 1) return; // idempotent

        $lineItems = ZBody::where('docid', $docId)->get();
        $summa     = (float)$doc->summa;
        $sklads    = (string)$doc->sklads;
        $oplata    = (string)$doc->oplata;
        $client1   = (string)$doc->client1;
        $manager   = convert_from_base($doc->manager ?? '');
        $numz      = (string)$doc->numz;
        $typez     = (string)$doc->typez;

        DB::beginTransaction();
        try {
            // ── Stock movements ───────────────────────────────────────────────
            foreach ($lineItems as $item) {
                $pnum  = $item->pnum;
                $count = (float)$item->pcount;

                match ($docType) {
                    // Incoming stock
                    'PN' => DB::table('price')
                        ->where('pnum', $pnum)->where('firma', $fid)
                        ->increment('count', $count),
                    // Outgoing stock
                    'RN', 'WO1' => DB::table('price')
                        ->where('pnum', $pnum)->where('firma', $fid)
                        ->decrement('count', $count),
                    // ZOUT: reserve
                    'ZOUT' => DB::table('price')
                        ->where('pnum', $pnum)->where('firma', $fid)
                        ->increment('reserved', $count),
                    // Return: reverse RN
                    'VN' => DB::table('price')
                        ->where('pnum', $pnum)->where('firma', $fid)
                        ->increment('count', $count),
                    // Adjustment out
                    'AO' => DB::table('price')
                        ->where('pnum', $pnum)->where('firma', $fid)
                        ->decrement('count', $count),
                    default => null,
                };
            }

            // ── Cash movements ────────────────────────────────────────────────
            if (in_array($docType, ['PO', 'RO', 'PP'], true)) {
                $sign  = $docType === 'RO' ? -1 : 1;
                $kasId = $oplata;
                DB::table('kassa')
                    ->where('id', $kasId)
                    ->increment('balance', $sign * $summa);
            }

            // ── Close ZOUT if fully paid ──────────────────────────────────────
            if ($docType === 'PO' && $typez === 'ZOUT' && $numz !== '0') {
                $zout = DB::table('document')
                    ->where('num', $numz)->where('type', 'ZOUT')->where('firma', $fid)
                    ->first();
                if ($zout) {
                    $paid = DB::table('z_document')
                        ->where('numz', $numz)->where('typez', 'ZOUT')
                        ->where('type', 'PO')->where('firma', $fid)
                        ->where('provodka', 1)
                        ->sum('summa');
                    if ((float)$paid + $summa >= (float)$zout->summa) {
                        DB::table('document')->where('id', $zout->id)->update(['close' => 1]);
                    }
                }
            }

            // Mark provodka done
            DB::table($table)->where('id', $docId)->update(['provodka' => 1]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Provodka failed', ['docId' => $docId, 'error' => $e->getMessage()]);
            throw $e;
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

        $exists = DB::table('users_cashe')->where('userid', $userId)->exists();
        if ($exists) {
            DB::table('users_cashe')->where('userid', $userId)->update(['balance' => $balance]);
        } else {
            DB::table('users_cashe')->insert(['userid' => $userId, 'balance' => $balance]);
        }
    }

    // ── SMS (smsclub.mobi) ────────────────────────────────────────────────────

    private function sendSms(string $docId, string $docType, string $fid, Request $request): void
    {
        $phone = DB::table('document as d')
            ->join('users', 'users.id', '=', 'd.client1')
            ->where('d.id', $docId)
            ->value('users.phone');
        if (!$phone) return;

        $text = $request->input('sms_text', '');
        if ($text === '') return;

        try {
            Http::withToken(config('services.sms.api_key'))
                ->post('https://sms.smsclub.mobi/sms/send', [
                    'phone'  => [$phone],
                    'message'=> $text,
                    'src_addr' => config('services.sms.sender', 'av8fund'),
                ]);
        } catch (\Throwable $e) {
            Log::warning('SMS failed', ['phone' => $phone, 'error' => $e->getMessage()]);
        }
    }

    // ── Telegram ─────────────────────────────────────────────────────────────

    private function sendTelegram(string $docId, string $docType, string $fid, Request $request): void
    {
        $token  = config('services.telegram.bot_token');
        $chatId = config('services.telegram.chat_id');
        if (!$token || !$chatId) return;

        $text = $request->input('sms_text', '') ?: "Документ #{$docId} ({$docType}) збережено";

        try {
            Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text'    => $text,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Telegram failed', ['error' => $e->getMessage()]);
        }
    }
}
