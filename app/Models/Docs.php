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
                    ? ($orderPosted ? ' | Проведен' : '')
                    : ($provodka > 0 ? ' | Проведен' : '');
                $year_ = substr($data, 6, 4);
                $typeName = Document::typeName($type);
                $isCurrentDoc = ($doc === $type);
                $showUrl = route('document.show', [
                    'doc' => $type,
                    'doc_id' => $id,
                    'parent_doc_id' => $docid,
                    'num' => $num,
                    'year' => $year_,
                ]);
                $link = $isCurrentDoc
                    ? "<span>{$typeName} № $num от $data : $summaZrow грн{$postedLabel}</span>"
                    : "<a href='{$showUrl}'>{$typeName} № $num от $data : $summaZrow грн{$postedLabel}</a>";

                if (in_array($type, ['ZIN', 'ZOUT'])) {
                    if ($type === 'ZIN')
                        $strWO1 .= "<div class='tstr'>$link</div>";
                    else
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
            $id = $row->id;
            $type = $row->type;
            $data = $row->data;
            $num = $row->num;
            $summa = $row->summa;
            $content = $row->content;
            $provodka = $row->provodka ?? 0;
            $provText = $provodka == 1 ? "style='color:red;'" : '';
            $year_ = substr($data, 6, 4);
            $typeName = Document::typeName($type);
            $isCurrentDoc = ($doc === $type);
            $showUrl = route('document.show', [
                'doc' => $type,
                'doc_id' => $id,
                'parent_doc_id' => $docid,
                'num' => $num,
                'year' => $year_,
            ]);
            $link = $isCurrentDoc
                ? "<span $provText>{$typeName} №$num от $data : $summa грн</span>"
                : "<a href='{$showUrl}' $provText>{$typeName} №$num от $data : $summa грн</a>";

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
                    $strRA .= "<a class='button' href='../document/show?doc={$type}&doc_id={$id}&num={$num}&year={$year}'>Файл №$num от $data : $content</a>";
                    break;
            }
        }

        $remainingPayment = max(0, (float)$sum);
        $createWO1Url = route('document.show', ['doc' => 'WO1', 'doc_id' => 0, 'parent_doc_id' => $docid, 'num' => 0, 'year' => $year]);
        $createRNUrl = route('document.show', ['doc' => 'RN', 'doc_id' => 0, 'parent_doc_id' => $docid, 'num' => 0, 'year' => $year]);
        $createCHUrl = route('document.show', ['doc' => 'CH', 'doc_id' => 0, 'parent_doc_id' => $docid, 'num' => 0, 'year' => $year]);
        $createPOUrl = route('document.show', ['doc' => 'PO', 'doc_id' => 0, 'parent_doc_id' => $docid, 'num' => 0, 'year' => $year, 'sumPO' => $remainingPayment]);
        $createPNUrl = route('document.show', ['doc' => 'PN', 'doc_id' => 0, 'parent_doc_id' => $docid, 'num' => 0, 'year' => $year]);
        $createROUrl = route('document.show', ['doc' => 'RO', 'doc_id' => 0, 'parent_doc_id' => $docid, 'num' => 0, 'year' => $year, 'sumPO' => $remainingPayment]);
        $createRAUrl = route('document.show', ['doc' => 'RA', 'doc_id' => 0, 'parent_doc_id' => $docid, 'num' => 0, 'year' => $year]);

        // ── Build action HTML ────────────────────────────────────────────────
        $html = '';
        $actions = '';
        $parentOrder = null;
        if ($docid) {
            $parentOrder = DB::table('document')
                ->where('id', $docid)
                ->first();
        }

        $parentOrderType = (string) ($parentOrder->type ?? $typez);
        $parentOrderNum = (string) ($parentOrder->num ?? $numz);
        $orderLabel = $parentOrderType === 'ZIN' ? 'Закупка' : 'Заказ';
        $parentOrderUrl = $docid
            ? route('document.show', ['doc' => $parentOrderType, 'doc_id' => $docid, 'num' => $parentOrderNum, 'year' => $year])
            : null;
        $disabledButton = "class='button' style='pointer-events:none; opacity:0.55;'";
        $disabledInline = "style='pointer-events:none; opacity:0.55;'";

        if (!in_array($doc, ['ZIN', 'ZOUT'], true) && $parentOrderUrl) {
            $actions .= "<div class='ttable'><a href='{$parentOrderUrl}' class='button'>← {$orderLabel} №{$parentOrderNum}</a></div>";
        }

        if ($doc === 'ZOUT') {
            $actions .= $strWO1;
            if ($sumWO1 < $summaZ && $sumRN < $summaZ) {
                if ($orderPosted) {
                    $actions .= "<div class='tstr'><span {$disabledButton}>В роботу</span></div>";
                } else {
                    $actions .= "<div class='tstr'><a href='{$createWO1Url}' class='button'>В роботу</a></div>";
                }
            }
            if ($idstatus != 2) {
                $actions .= "<div class='ttable'>Отгрузка";
                $actions .= $strRN;
                if ($sumRN < $summaZ) {
                    if ($orderPosted) {
                        $actions .= "<div class='tstr'><span {$disabledInline}><image src='../img/icon-truck.png' style='height:70%'> Видача товару</span></div>";
                    } else {
                        $actions .= "<div class='tstr'><a href='{$createRNUrl}'><image src='../img/icon-truck.png' style='height:70%'> Видача товару</a></div>";
                    }
                }
                $actions .= "</div><div class='ttable'>Оплата";
                $actions .= $strCH;
                $actions .= $strPO;
                if ($sumPO < $summaZ && $strCH == '') {
                    if ($orderPosted) {
                        $actions .= "<span {$disabledInline}><image src='../img/icon-file-invoice.png' style='height:70%'> Пропозиція</span>";
                    } else {
                        $actions .= "<a href='{$createCHUrl}'><image src='../img/icon-file-invoice.png' style='height:70%'> Пропозиція</a>";
                    }
                }
                if ($sumPO < $summaZ) {
                    if ($orderPosted) {
                        $actions .= "<span {$disabledButton}><image src='../img/icon-multitasking.png'> Отримання грошей</span>";
                    } else {
                        $actions .= "<a href='{$createPOUrl}' class='button'><image src='../img/icon-multitasking.png'> Отримання грошей</a>";
                    }
                }
                $actions .= "</div>";
            }
            $actions .= "<div class='ttable'>Файли<br>$strRA";
            if ($orderPosted) {
                $actions .= "<span {$disabledButton}><span style='font-size:3em'>+</span></span>";
            } else {
                $actions .= "<a href='{$createRAUrl}' class='button'><span style='font-size:3em'>+</span></a>";
            }
            $actions .= "</div>";
        }

        if ($doc === 'ZIN') {
            $actions .= "Отримання" . $strPN;
            if ($sumPN < $summaZ) {
                if ($orderPosted) {
                    $actions .= "<div class='tstr' style='width:100%; text-align:center;'><span {$disabledButton}><image src='../img/icon-warehouse.png' style='height:70%'> Отримання товару</span></div>";
                } else {
                    $actions .= "<div class='tstr' style='width:100%; text-align:center;'><a href='{$createPNUrl}' class='button'><image src='../img/icon-warehouse.png' style='height:70%'> Отримання товару</a></div>";
                }
            }
            $actions .= "Оплата" . $strRO;
            if ($sumRO < $summaZ) {
                if ($orderPosted) {
                    $actions .= "<div class='tstr'><span {$disabledButton}><image src='../img/icon-hand-bill.png'> Видача грошей</span></div>";
                } else {
                    $actions .= "<div class='tstr'>
                <a href='{$createROUrl}' class='button'><image src='../img/icon-hand-bill.png'> Видача грошей</a></div>";
                }
            }
            $actions .= "<div class='ttable'>Файли<br>$strRA";
            if ($orderPosted) {
                $actions .= "<span {$disabledButton}><span style='font-size:3em'>+</span></span>";
            } else {
                $actions .= "<a href='{$createRAUrl}' class='button'><span style='font-size:3em'>+</span></a>";
            }
            $actions .= "</div>";
        }

        $html = "<div class='ttable'>Документи:<br>$actions<br>На рахунку: $balance грн</div>";

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
