<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * FilterService
 * Migrated from: library/filter.php
 *
 * Builds parameterized WHERE fragments for document list queries.
 * KEY CHANGE: old filter.php built raw SQL with string interpolation (SQL injection).
 * Now returns ? placeholders + $params array → safe binding.
 */
class FilterService
{
    // ── Default date range (previous month–day → today) ───────────────────────

    private function defaultDates(): array
    {
        $month = (int)date('m');
        $year = (int)date('Y');
        if ($month === 1) {
            $month = 12;
            $year--;
        }
        else
            $month--;
        return [
            'fdata1' => $year . '-' . str_pad((string)$month, 2, '0', STR_PAD_LEFT) . '-' . date('d'),
            'fdata2' => date('Y-m-d'),
        ];
    }

    private function prefix(string $doc): string
    {
        return match (true) {
                in_array($doc, ['ZOUT', 'CH', 'WO1', 'SP', 'RN', 'PO']) => 'ZOUT',
                in_array($doc, ['ZIN', 'PN']) => 'ZIN',
                in_array($doc, ['RO', 'PP', 'RPO']) => 'RO',
                $doc === 'STAT' => 'STAT',
                default => 'DEFAULT',
            };
    }

    // ── Save filter from POST → session ──────────────────────────────────────

    public function save(Request $request, string $doc, string $fid): void
    {
        if (!$request->filled('filter'))
            return;

        if ($request->input('clear') === '1') {
            $this->clear($doc);
            return;
        }

        $pfx = $this->prefix($doc);
        session([
            "f.fdata1.{$pfx}" => $request->input('fdata1', ''),
            "f.fdata2.{$pfx}" => $request->input('fdata2', ''),
            "f.name.{$doc}" => $request->input('f_name', ''),
            "f.content.{$doc}" => $request->input('f_content', ''),
            "f.operator.{$doc}" => $request->input('f_operator', ''),
            "f.reteil.{$doc}" => $request->input('reteil', ''),
            "f.sklads.{$doc}" => $request->input('sklads', ''),
            "f.status.{$doc}" => $request->input('status', ''),
            "f.oplata.{$doc}" => $request->input('oplata', ''),
            "f.reestr.{$doc}" => $request->input('reestr', ''),
            "f.money_way.{$doc}" => $request->input('money_way', ''),
            "f.provodka.{$doc}" => $request->input('f_provodka', ''),
            'pos' => 0,
        ]);
    }

    public function clear(string $doc): void
    {
        $pfx = $this->prefix($doc);
        $keys = [
            "f.fdata1.{$pfx}", "f.fdata2.{$pfx}",
            "f.name.{$doc}", "f.content.{$doc}", "f.operator.{$doc}",
            "f.reteil.{$doc}", "f.sklads.{$doc}", "f.status.{$doc}",
            "f.oplata.{$doc}", "f.reestr.{$doc}", "f.money_way.{$doc}",
            "f.provodka.{$doc}",
        ];
        foreach ($keys as $k)
            session()->forget($k);
        session(['pos' => 0]);
    }

    /**
     * Build WHERE fragments from session filter values.
     *
     * Returns:
     *   userSql  — fragment requiring JOIN users (clients filter)
     *   docSql   — fragment on doc table columns
     *   params   — bind values (userSql params first, then docSql params)
     *   fdata1, fdata2 — active date range
     *   isDefault — no active filters
     *   + raw field values for re-rendering the filter form
     */
    public function resolve(string $doc, string $fid): array
    {
        $pfx = $this->prefix($doc);
        $def = $this->defaultDates();

        $fdata1 = session("f.fdata1.{$pfx}", '');
        $fdata2 = session("f.fdata2.{$pfx}", '');
        $fName = session("f.name.{$doc}", '');
        $fContent = session("f.content.{$doc}", '');
        $fOperator = session("f.operator.{$doc}", '');
        $fReteil = session("f.reteil.{$doc}", '');
        $fSklads = session("f.sklads.{$doc}", '');
        $fStatus = session("f.status.{$doc}", 0);
        $fOplata = session("f.oplata.{$doc}", '');
        $fReestr = session("f.reestr.{$doc}", '');
        $fMoneyWay = session("f.money_way.{$doc}", '');
        $fProvodka = session("f.provodka.{$doc}", '');

        if (empty($fdata1) || empty($fdata2)) {
            $fdata1 = $def['fdata1'];
            $fdata2 = $def['fdata2'];
        }

        $userSql = '';
        $docSql = '';
        $params = [];

        // ── User / client columns ─────────────────────────────────────────────
        $userParts = [];

        if (!empty($fName)) {
            $like = '%' . $fName . '%';
            $userParts[] = 'u.orgname LIKE ? OR u.name2 LIKE ? OR u.name LIKE ?
                           OR u.secondname LIKE ? OR u.phone LIKE ?
                           OR u.phone1 LIKE ? OR u.city LIKE ?';
            array_push($params, $like, $like, $like, $like, $like, $like, $like);
        }
        if (!empty($fContent)) {
            $like = '%' . $fContent . '%';
            $userParts[] = 'd.content LIKE ? OR d.ttn LIKE ? OR d.num LIKE ?';
            array_push($params, $like, $like, $like);
        }

        if (!empty($userParts)) {
            $userSql = implode(' OR ', $userParts);
        }

        // ── Doc columns ───────────────────────────────────────────────────────
        if (!empty($fSklads)) {
            $docSql .= ' AND d.sklads LIKE ?';
            $params[] = '%' . $fSklads . '%';

        }
        if (!empty($fReteil)) {
            $docSql .= ' AND d.reteil LIKE ?';
            $params[] = '%' . $fReteil . '%';

        }
        if (!empty($fdata1)) {
            $ts = nextdate($fdata1);
            if ($ts) {
                $docSql .= ' AND d.dt >= ?';
                $params[] = $ts;

            }
        }
        if (!empty($fdata2)) {
            $ts = nextdate($fdata2);
            if ($ts) {
                $docSql .= ' AND d.dt <= ?';
                $params[] = $ts + 86399;
            }
        }
        if (!empty($fMoneyWay)) {
            $docSql .= ' AND d.type LIKE ?';
            $params[] = '%' . $fMoneyWay . '%';

        }
        if (!empty($fReestr)) {
            $docSql .= ' AND d.reestr = ?';
            $params[] = $fReestr;

        }
        if (!empty($fOplata)) {
            $like = '%' . $fOplata . '%';
            $docSql .= ' AND (d.oplata LIKE ? OR d.oplata2 LIKE ?)';
            array_push($params, $like, $like);
        }
        if (!empty($fOperator)) {
            $docSql .= ' AND d.manager LIKE ?';
            $params[] = '%' . $fOperator . '%';
        }
        if (empty($fProvodka) && in_array($doc, ['WO1', 'ZD'], true)) {
            $docSql .= " AND d.provodka = '0'";
        }

        // ── Status logic ─────────────────────────────────────────────────
        if (empty($fStatus)) {
            $docSql .= '';
        }
        elseif ($fStatus === 999) {
            $docSql .= ' AND d.status > 0';
        }
        else {
            $docSql .= ' AND d.status = ?';
            $params[] = $fStatus;
        }

        $isDefault = ($fName === '' && $fContent === '' && $fOperator === ''
            && $fReteil === '' && $fSklads === '' && $fStatus === 0
            && $fOplata === '' && $fReestr === '' && $fProvodka === ''
            && ($fdata1 === '' || $fdata1 === $def['fdata1'])
            && ($fdata2 === '' || $fdata2 === $def['fdata2']));

        return compact(
            'userSql', 'docSql', 'params',
            'fdata1', 'fdata2', 'isDefault',
            'fName', 'fContent', 'fOperator',
            'fReteil', 'fSklads', 'fStatus',
            'fOplata', 'fReestr', 'fMoneyWay', 'fProvodka'
        );
    }
}