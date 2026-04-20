<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Models\Document;

class Docs extends Model
{
    protected $table = 'docs';
    public $timestamps = false;
    protected $guarded = [];


    public function firmaObj()
    {
        return $this->belongsTo(Firma::class, 'firma');
    }

    // ── Legacy: client_info1 — full related-docs block with action buttons ───

    /**
     * Return HTML block with all related documents for a client.
     * Used on ZIN / ZOUT detail pages.
     *
     * @param int    $client  client1 id
     * @param string $numz    parent doc number
     * @param string $typez   parent doc type
     * @param string $doc     current doc type (ZIN/ZOUT) — if matches, hide links
     * @param int    $idstatus user status
     * @param string $year    year filter
     * @param int    $docid   document-group id
     * @param float  $summaZ  total of the parent ZIN/ZOUT
     * @param bool   $orderPosted whether the related order/purchase is posted
     * @return array{html: string, sums: array}
     */
    public static function clientInfo1($client, $numz, $typez, $doc, $idstatus, $year, $docid, $summaZ, bool $orderPosted = false)
    {
        // Balance
        $balance = DB::table('users')->where('id', $client)->value('balance') ?? 0;

        $strPO = $strRO = $strWO1 = $strPN = $strRN = $strCH = $strRA = '';
        $sumPO = $sumRO = $sumWO1 = $sumPN = $sumRN = 0;
        $sum = $summaZ;

        // Icon mapping per document type
        $typeIcons = [
            'ZIN'  => '📦',
            'ZOUT' => '🛒',
            'CH'   => '📄',
            'RN'   => '🚚',
            'PN'   => '📥',
            'WO1'  => '🔧',
            'PO'   => '💰',
            'RO'   => '💸',
            'RA'   => '📎',
        ];

        // ── document table (ZIN / ZOUT) ──────────────────────────────────────
        if ($idstatus != 2) {
            $query = DB::table('document')->where('client1', $client);
            if ($docid != 0 && in_array($year, range(2020, 2030))) {
                $query->where('docid', $docid);
            } else {
                $query->where('num', $numz)->where('type', $typez)->where('data', 'like', '%' . $year . '%');
            }
            $docs = $query->get();

            foreach ($docs as $row) {
                $id = $row->id;
                $type = $row->type;
                $data = $row->data;
                $num = $row->num;
                $summaZrow = $row->summa;
                $provodka = $row->provodka ?? 0;
                $postedLabel = $type === 'ZOUT'
                    ? ($orderPosted ? ' ✅ Проведено' : '')
                    : ($provodka > 0 ? ' ✅ Проведено' : '');
                $year_ = substr($data, 6, 4);
                $typeName = Document::typeName($type);
                $icon = $typeIcons[$type] ?? '📋';
                $isCurrentDoc = ($doc === $type);
                $showUrl = route('document.show', [
                    'doc'           => $type,
                    'doc_id'        => $id,
                    'parent_doc_id' => $docid,
                    'num'           => $num,
                    'year'          => $year_,
                ]);
                $link = $isCurrentDoc
                    ? "<span class='rel-doc-link rel-doc-link--current'>{$icon} {$typeName} №{$num} от {$data} : {$summaZrow} грн{$postedLabel}</span>"
                    : "<a href='{$showUrl}' class='rel-doc-link rel-doc-link--{$type}'>{$icon} {$typeName} №{$num} от {$data} : {$summaZrow} грн{$postedLabel}</a>";

                if (in_array($type, ['ZIN', 'ZOUT'])) {
                    $strWO1 .= "<div class='tstr'>$link</div>";
                }
            }
        }

        // ── z_document (CH, RN, PN, WO1, PO, RO, RA) ────────────────────────
        $query = DB::table('z_document')->where('client1', $client);
        if ($docid != 0 && in_array($year, range(2020, 2030))) {
            $query->where('docid', $docid);
        } else {
            $query->where('numz', $numz)->where('typez', $typez)->where('data', 'like', '%' . $year . '%');
        }
        $zdocs = $query->get();

        foreach ($zdocs as $row) {
            $id       = $row->id;
            $type     = $row->type;
            $data     = $row->data;
            $num      = $row->num;
            $summa    = $row->summa;
            $content  = $row->content;
            $provodka = $row->provodka ?? 0;
            $year_    = substr($data, 6, 4);
            $typeName = Document::typeName($type);
            $icon     = $typeIcons[$type] ?? '📋';
            $isCurrentDoc = ($doc === $type);
            $postedClass  = $provodka == 1 ? ' rel-doc-link--posted' : '';
            $showUrl = route('document.show', [
                'doc'           => $type,
                'doc_id'        => $id,
                'parent_doc_id' => $docid,
                'num'           => $num,
                'year'          => $year_,
            ]);
            $link = $isCurrentDoc
                ? "<span class='rel-doc-link rel-doc-link--current{$postedClass}'>{$icon} {$typeName} №{$num} от {$data} : {$summa} грн</span>"
                : "<a href='{$showUrl}' class='rel-doc-link rel-doc-link--{$type}{$postedClass}'>{$icon} {$typeName} №{$num} от {$data} : {$summa} грн</a>";

            switch ($type) {
                case 'CH':
                    $strCH .= "<div class='tstr'>$link</div>";
                    break;
                case 'RN':
                    $sumRN += $summa;
                    $strRN .= "<div class='tstr'>$link</div>";
                    break;
                case 'PN':
                    $sumPN += $summa;
                    $strPN .= "<div class='tstr'>$link</div>";
                    break;
                case 'WO1':
                    $sumWO1 += $summa;
                    $strWO1 .= "<div class='tstr'>$link</div>";
                    break;
                case 'PO':
                    $sum -= $summa;
                    $sumPO += $summa;
                    $strPO .= "<div class='tstr'>$link</div>";
                    break;
                case 'RO':
                    $sum -= $summa;
                    $sumRO += $summa;
                    $strRO .= "<div class='tstr'>$link</div>";
                    break;
                case 'RA':
                    $strRA .= "<div class='tstr'><a class='rel-doc-link rel-doc-link--RA' href='../document/show?doc={$type}&doc_id={$id}&num={$num}&year={$year}'>📎 Файл №{$num} від {$data} : {$content}</a></div>";
                    break;
            }
        }

        $remainingPayment = max(0, (float)$sum);
        $createWO1Url = route('document.show', ['doc' => 'WO1', 'doc_id' => 0, 'parent_doc_id' => $docid, 'num' => 0, 'year' => $year]);
        $createRNUrl  = route('document.show', ['doc' => 'RN',  'doc_id' => 0, 'parent_doc_id' => $docid, 'num' => 0, 'year' => $year]);
        $createCHUrl  = route('document.show', ['doc' => 'CH',  'doc_id' => 0, 'parent_doc_id' => $docid, 'num' => 0, 'year' => $year]);
        $createPOUrl  = route('document.show', ['doc' => 'PO',  'doc_id' => 0, 'parent_doc_id' => $docid, 'num' => 0, 'year' => $year, 'sumPO' => $remainingPayment]);
        $createPNUrl  = route('document.show', ['doc' => 'PN',  'doc_id' => 0, 'parent_doc_id' => $docid, 'num' => 0, 'year' => $year]);
        $createROUrl  = route('document.show', ['doc' => 'RO',  'doc_id' => 0, 'parent_doc_id' => $docid, 'num' => 0, 'year' => $year, 'sumPO' => $remainingPayment]);
        $createRAUrl  = route('document.show', ['doc' => 'RA',  'doc_id' => 0, 'parent_doc_id' => $docid, 'num' => 0, 'year' => $year]);
        $canCreateRN  = $summaZ <= 0 || $sumRN < $summaZ;
        $canCreatePO  = $summaZ <= 0 || $sumPO < $summaZ;
        $canCreatePN  = $summaZ <= 0 || $sumPN < $summaZ;
        $canCreateRO  = $summaZ <= 0 || $sumRO < $summaZ;

        // ── Build action HTML ────────────────────────────────────────────────
        $html    = '';
        $actions = '';
        $parentOrder = null;
        if ($docid) {
            $parentOrder = DB::table('document')
                ->where('id', $docid)
                ->first();
        }

        $parentOrderType = (string) ($parentOrder->type ?? $typez);
        $parentOrderNum  = (string) ($parentOrder->num  ?? $numz);
        $orderLabel      = $parentOrderType === 'ZIN' ? '📦 Закупка' : '🛒 Заказ';
        $parentOrderUrl  = $docid
            ? route('document.show', ['doc' => $parentOrderType, 'doc_id' => $docid, 'num' => $parentOrderNum, 'year' => $year])
            : null;

        if (!in_array($doc, ['ZIN', 'ZOUT'], true) && $parentOrderUrl) {
            $actions .= "<div class='ttable'><a href='{$parentOrderUrl}' class='rel-doc-action-btn'>← {$orderLabel} №{$parentOrderNum}</a></div>";
        }

        if ($parentOrderType === 'ZOUT') {
            $actions .= $strWO1;
            if ($sumWO1 < $summaZ && $sumRN < $summaZ) {
                $actions .= "<div class='tstr'><a href='{$createWO1Url}' class='rel-doc-action-btn'>🔧 В роботу</a></div>";
            }
            if ($idstatus != 2) {
                $actions .= "<div class='ttable rel-doc-section'><span class='rel-doc-section__title'>🚚 Відвантаження</span>";
                $actions .= $strRN;
                if ($canCreateRN) {
                    $actions .= "<div class='tstr'><a href='{$createRNUrl}' class='rel-doc-action-btn rel-doc-action-btn--create'>＋ Видача товару</a></div>";
                }
                $actions .= "</div><div class='ttable rel-doc-section'><span class='rel-doc-section__title'>💰 Оплата</span>";
                $actions .= $strCH;
                $actions .= $strPO;
                if ($canCreatePO && $strCH == '') {
                    $actions .= "<div class='tstr'><a href='{$createCHUrl}' class='rel-doc-action-btn rel-doc-action-btn--create'>＋ Пропозиція</a></div>";
                }
                if ($canCreatePO) {
                    $actions .= "<div class='tstr'><a href='{$createPOUrl}' class='rel-doc-action-btn rel-doc-action-btn--create'>＋ Отримання грошей</a></div>";
                }
                $actions .= "</div>";
            }
            $actions .= "<div class='ttable rel-doc-section'><span class='rel-doc-section__title'>📎 Файли</span>{$strRA}";
            if ($strRA === '') {
                $actions .= "<div class='tstr'><a href='{$createRAUrl}' class='rel-doc-action-btn rel-doc-action-btn--create'>＋ Додати файл</a></div>";
            }
            $actions .= "</div>";
        }

        if ($parentOrderType === 'ZIN') {
            $actions .= "<div class='ttable rel-doc-section'><span class='rel-doc-section__title'>📥 Отримання</span>";
            $actions .= $strPN;
            if ($canCreatePN) {
                $actions .= "<div class='tstr'><a href='{$createPNUrl}' class='rel-doc-action-btn rel-doc-action-btn--create'>＋ Отримання товару</a></div>";
            }
            $actions .= "</div><div class='ttable rel-doc-section'><span class='rel-doc-section__title'>💸 Оплата</span>";
            $actions .= $strRO;
            if ($canCreateRO) {
                $actions .= "<div class='tstr'><a href='{$createROUrl}' class='rel-doc-action-btn rel-doc-action-btn--create'>＋ Видача грошей</a></div>";
            }
            $actions .= "</div><div class='ttable rel-doc-section'><span class='rel-doc-section__title'>📎 Файли</span>{$strRA}";
            if ($strRA === '') {
                $actions .= "<div class='tstr'><a href='{$createRAUrl}' class='rel-doc-action-btn rel-doc-action-btn--create'>＋ Додати файл</a></div>";
            }
            $actions .= "</div>";
        }

        $html = "<div class='ttable'><div class='rel-doc-balance'>💳 На рахунку: <strong>{$balance} грн</strong></div>{$actions}</div>";

        return [
            'html' => $html,
            'sums' => compact('sumPO', 'sumRO', 'sumWO1', 'sumPN', 'sumRN'),
        ];
    }

    // ── Legacy: client_info — compact icon strip for related docs ────────────

    /**
     * Return compact icon strip showing related-doc stages for a client.
     *
     * @param int    $client
     * @param string $numz
     * @param string $typez
     * @param string $year
     * @param int    $docid
     * @param float  $summa_
     * @return string HTML
     */
    public static function clientInfo($client, $numz, $typez, $year, $docid, $summa_)
    {
        $summa_ = ceil($summa_);
        if ($year == '') $year = date('Y');

        $str = ''; $str1 = '';
        $sum = $sum1 = $sum2 = $sumRA = 0;
        $type_cur = '';

        $query = DB::table('z_document')->where('client1', $client);
        if ($docid != 0 && in_array($year, range(2020, 2030))) {
            $query->where('docid', $docid);
        } else {
            $query->where('numz', $numz)->where('typez', $typez)->where('data', 'like', '%' . $year . '%');
        }
        $zdocs = $query->get();

        foreach ($zdocs as $row) {
            $type = $row->type;
            $summa = $row->summa;
            $provodka = $row->provodka ?? 0;
            $content = $row->content ?? '';
            $cnt_str = substr_count($content, 'EPS');
            if ($cnt_str > 0) $type = '';

            switch ($type) {
                case 'WO1':
                    if ($type_cur != 'RN') {
                        $str .= $provodka == 1
                            ? "<img src='../img/hammer_30.jpg' title='У виробництві'>  "
                            : "<img src='../img/molot.gif' title='У виробництві' width='30px'>  ";
                    }
                    $type_cur = 'WO1';
                    break;
                case 'RN':
                    $sum1 += $summa;
                    $str .= "<img src='../img/out.png' title='Відвантажено'>  ";
                    $type_cur = 'RN';
                    break;
                case 'PN':
                    $sum2 += $summa;
                    $str .= "<img src='../img/out.png' title='Отримано на склад'>  ";
                    break;
                case 'PO':
                    $sum += $summa;
                    break;
                case 'RO':
                    $sum += $summa;
                    break;
                case 'RA':
                    if ($sumRA == 0) {
                        $sumRA++;
                        $str1 .= "<img src='../img/pngwing20.png' title='Файл'>  ";
                    }
                    break;
            }
        }

        $sum = ceil($sum);
        if ($sum != 0) {
            $str .= "<img src='../img/money.png' title='Отримано $summa_ грн' width='40px;'><br>";
            $dolg = $summa_ - $sum;
            if ($dolg != 0) {
                if ($dolg < 0) {
                    $str .= "на депозиті " . abs($dolg) . " грн<br>";
                } else {
                    $str .= "<br>винний " . abs($dolg) . " грн<br>";
                }
            }
        } else {
            $str .= "<br>винний " . abs($summa_) . " грн<br>";
        }

        return $str1 . $str;
    }
}
