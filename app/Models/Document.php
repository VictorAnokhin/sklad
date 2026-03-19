<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Document extends Model
{
    protected $table = 'document';
    public $timestamps = false;
    protected $guarded = [];


    public function firmaObj()
    {
        return $this->belongsTo(Firma::class , 'firma');
    }
    public static function tableForType($doc)
    {
        return match ($doc) {
                'ZIN', 'ZOUT' => 'document',
                default => 'z_document',
            };
    }

    public function scopeFilter($query, $filters)
    {
        return $query->when($filters['year'] ?? null, function ($q) use ($filters) {
            $q->whereYear('data', $filters['year']);
        });
    }

    /**
     * Fetch document list rows + confMap from DB.
     *
     * @return array{rows: array, total: int, confMap: array}
     */
    public static function init($doc, $pos, $fd, $fid, $login, $idstatus, $idsklad, $idkassa)
    {
        $table    = self::tableForType($doc);
        $hasUserF = $fd['userSql'] !== '' || $fd['fName'] !== '';

        if ($hasUserF) {
            $base = "FROM {$table} d JOIN users u ON u.id = d.client1
                     WHERE d.firma = ? AND d.type LIKE ?
                     {$fd['userSql']} {$fd['docSql']}";
            $bp   = [$fid, "%{$doc}%", ...$fd['params']];
        } else {
            $base = "FROM {$table} d JOIN users u ON u.id = d.client1
                     WHERE (d.dostup <= ? OR d.manager = ? OR d.sklads = ? OR d.oplata = ?)
                       AND d.firma = ? AND d.type LIKE ?
                     {$fd['docSql']}";
            $bp   = [$idstatus, $login, $idsklad, $idkassa, $fid, "%{$doc}%", ...$fd['params']];
        }

        $total = DB::selectOne("SELECT COUNT(*) AS n {$base}", $bp)->n;

        $cols = "d.id, d.num, d.client1, d.time, d.data, d.data2, d.type,
                 d.summa, d.bonus, d.status, d.content, d.ttn,
                 d.sklads, d.reteil, d.oplata, d.reestr, d.docum,
                 d.manager, d.provodka, d.money, d.numz, d.typez, d.client2,
                 u.orgname, u.kod1, u.secondname, u.name, u.fathername,
                 u.name2, u.region, u.city, u.poshta, u.phone, u.top";

        $sort = 'ORDER BY d.dt DESC, d.time DESC, d.num DESC';
        $rows = DB::select(
            "SELECT {$cols} {$base} {$sort} LIMIT ?, ?",
            [...$bp, $pos, 30]
        );

        // Batch-load conf (status, money, sklads, reteil) to avoid N+1
        $confIds = [];
        foreach ($rows as $r) {
            if ($r->status) $confIds[] = $r->status;
            if ($r->money)  $confIds[] = $r->money;
            if ($r->sklads) $confIds[] = $r->sklads;
            if ($r->reteil) $confIds[] = $r->reteil;
        }
        $confMap = [];
        if (!empty($confIds)) {
            $confMap = DB::table('conf')
                ->whereIn('id', array_unique($confIds))
                ->get(['id', 'name', 'color', 'status'])
                ->keyBy('id')->toArray();
        }

        return [
            'rows'    => $rows,
            'total'   => $total,
            'confMap' => $confMap,
        ];
    }

    public static function showDocumentList($rows, $confMap, $doc)
    {
        $data = [];
        $total_sum = 0;

        foreach ($rows as $row) {
            $statusId   = $row->status ?? '';
            $conf       = $confMap[$statusId] ?? null;
            $statusName = $conf ? h(convert_from_base($conf->name)) : '';
            $color      = h($conf->color ?? '');
            
            $summa      = (float)$row->summa;
            $total_sum += $summa;
            $summaFmt   = number_format($summa, 2, ',', "'");
            
            $year = strlen((string)($row->data ?? '')) >= 10 ? substr((string)$row->data, 6, 4) : date('Y');
            
            $content = h(convert_from_base($row->content ?? ''));
            if ($row->ttn) {
                $content .= '<br>НП:' . h($row->ttn);
            }
            
            $orgname = h(convert_from_base($row->orgname ?? ''));
            $kod1    = h($row->kod1 ?? '');
            $org     = $orgname ? "{$orgname}, {$kod1}" : '';
            
            $fullName = h(trim(
                convert_from_base($row->secondname ?? '') . ' '
                . convert_from_base($row->name ?? '') . ' '
                . convert_from_base($row->fathername ?? '')
            ));
            
            $city    = h(convert_from_base($row->city ?? ''));
            $poshta  = $row->poshta ? 'НП ' . h($row->poshta) : '';
            $phone   = h(formatPhone((string)($row->phone ?? '')));
            $manager = h(strtolower(convert_from_base($row->manager ?? '')));
            
            $signal  = ($statusName === '' && $doc === 'ZOUT') ? "<span class='alink3'>new</span>" : '';
            
            $linkUrl = route('document.show', [
                'doc_id' => $row->id, 'num' => $row->num, 'year' => $year, 'doc' => $doc,
            ]);

            $data[] = [
                'id'         => $row->id,
                'num'        => h($row->num),
                'linkUrl'    => $linkUrl,
                'data'       => h($row->data),
                'time'       => h($row->time),
                'org'        => $org,
                'fullName'   => $fullName,
                'city'       => $city,
                'poshta'     => $poshta,
                'phone'      => $phone,
                'color'      => $color,
                'statusName' => $statusName,
                'signal'     => $signal,
                'summaFmt'   => $summaFmt,
                'content'    => $content,
                'manager'    => $manager,
            ];
        }

        return [
            'items'     => $data,
            'total_sum' => $total_sum,
        ];
    }

}