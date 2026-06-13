<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class BankController extends Controller
{
    public function cashAccounts(): View
    {
        $project = $this->bankProject();
        $cashAccounts = collect();
        $clientAccounts = collect();
        $projectAccounts = collect();

        if (Schema::hasTable('conf')) {
            $cashAccounts = DB::table('conf')
                ->where('type', 'oplata')
                ->where('firma', (string) $project->id)
                ->orderBy('name')
                ->get()
                ->map(fn ($account) => $this->normalizeCashAccount($account));
        }

        if (Schema::hasTable('users') && Schema::hasTable('users_cashe')) {
            $clientAccounts = $this->clientAccounts((string) $project->id, $cashAccounts);
        }
        if (Schema::hasTable('project')) {
            $projectAccounts = $this->projectAccounts();
        }

        $totalByCurrency = $clientAccounts
            ->groupBy('currency')
            ->map(fn ($items) => (float) $items->sum('balance'));
        $operationalTotalByCurrency = $cashAccounts
            ->groupBy('currency')
            ->map(fn ($items) => (float) $items->sum('balance'));
        $ownerTypeTotals = $clientAccounts
            ->groupBy('owner_type')
            ->map(fn ($items) => [
                'count' => $items->count(),
                'balance' => (float) $items->sum('balance'),
            ]);

        return view('bank.cash_accounts', [
            'project' => $project,
            'cashAccounts' => $cashAccounts,
            'clientAccounts' => $clientAccounts,
            'projectAccounts' => $projectAccounts,
            'personOwners' => $this->personOwners($clientAccounts),
            'totalByCurrency' => $totalByCurrency,
            'operationalTotalByCurrency' => $operationalTotalByCurrency,
            'ownerTypeTotals' => $ownerTypeTotals,
        ]);
    }

    public function loans(): View
    {
        return $this->placeholder('Внутренние кредиты', 'Учет займов между проектами холдинга, графиков погашения и начисленных процентов.');
    }

    public function exchange(): View
    {
        return $this->placeholder('Обмен фиат/крипта', 'Операции обмена между фиатными кассами и крипто-кошельками с курсом, комиссией и проводками.');
    }

    public function clearing(): View
    {
        return $this->placeholder('Клиринг проектов', 'Взаимозачеты, долги и требования между проектами холдинга.');
    }

    public function payments(): View
    {
        return $this->placeholder('Платежи', 'Исходящие и входящие платежи через кассы-кошельки банка.');
    }

    public function reconciliation(): View
    {
        return $this->placeholder('Сверка', 'Сверка остатков касс, ledger-проводок и blockchain-транзакций.');
    }

    private function placeholder(string $title, string $description): View
    {
        return view('bank.placeholder', [
            'project' => $this->bankProject(),
            'title' => $title,
            'description' => $description,
        ]);
    }

    private function bankProject(): Project
    {
        abort_unless(Schema::hasTable('project'), 404);

        $project = Project::query()->find((int) session('fid', 0));
        abort_unless($project instanceof Project, 404);
        abort_unless(strtolower(trim((string) ($project->project_type ?? ''))) === 'bank', 403);

        return $project;
    }

    private function normalizeCashAccount(object $account): object
    {
        $account->balance = (float) ($account->value ?? 0);
        $account->currency = trim((string) ($account->currency ?? '')) ?: $this->currencyFromName((string) ($account->name ?? ''));
        $account->label = trim((string) ($account->name ?? '')) ?: 'Касса #' . (string) ($account->id ?? '');
        $account->doc = trim((string) ($account->doc ?? ''));
        $account->color = trim((string) ($account->color ?? ''));

        return $account;
    }

    private function clientAccounts(string $fid, $cashAccounts)
    {
        $userColumns = Schema::getColumnListing('users');
        $cacheColumns = Schema::getColumnListing('users_cashe');

        if (! in_array('userid', $cacheColumns, true) || ! in_array('balance', $cacheColumns, true)) {
            return collect();
        }

        $hasCacheFirma = in_array('firma', $cacheColumns, true);
        $hasValuta = in_array('valuta', $cacheColumns, true);
        $select = [
            'uc.userid',
            'uc.balance',
        ];
        $select[] = $hasCacheFirma ? 'uc.firma as account_firma' : DB::raw("'{$fid}' as account_firma");
        $select[] = $hasValuta ? 'uc.valuta' : DB::raw("'UAH' as valuta");

        foreach (['id', 'firma', 'orgname', 'kod1', 'name', 'name2', 'secondname', 'fathername', 'phone', 'email', 'city', 'idstatus', 'ustype', 'created_at'] as $column) {
            if (in_array($column, $userColumns, true)) {
                $select[] = "u.{$column}";
            }
        }

        $cashByCurrency = $cashAccounts->groupBy('currency');

        return DB::table('users_cashe as uc')
            ->leftJoin('users as u', 'u.id', '=', 'uc.userid')
            ->when($hasCacheFirma, fn ($query) => $query->where('uc.firma', (int) $fid))
            ->when(in_array('orgname', $userColumns, true), fn ($query) => $query->orderBy('u.orgname'))
            ->when(in_array('secondname', $userColumns, true), fn ($query) => $query->orderBy('u.secondname'))
            ->when(in_array('name', $userColumns, true), fn ($query) => $query->orderBy('u.name'))
            ->orderBy('uc.userid')
            ->when($hasValuta, fn ($query) => $query->orderBy('uc.valuta'))
            ->get($select)
            ->map(function ($row) use ($cashByCurrency) {
                $currency = trim((string) ($row->valuta ?? 'UAH')) ?: 'UAH';
                $serviceAccount = optional($cashByCurrency->get($currency, collect())->first())->label;
                $ownerType = $this->ownerType($row);

                return (object) [
                    'account_number' => $this->clientAccountNumber($row, $currency),
                    'owner_id' => (string) ($row->userid ?? ''),
                    'owner_name' => $this->ownerName($row),
                    'owner_type' => $ownerType,
                    'owner_type_label' => $ownerType === 'company' ? 'Компания' : 'Физлицо',
                    'tax_code' => trim((string) ($row->kod1 ?? '')),
                    'phone' => trim((string) ($row->phone ?? '')),
                    'email' => trim((string) ($row->email ?? '')),
                    'contact' => $this->ownerContact($row),
                    'city' => trim((string) ($row->city ?? '')),
                    'currency' => $currency,
                    'balance' => (float) ($row->balance ?? 0),
                    'status' => ((int) ($row->idstatus ?? $row->ustype ?? 1)) > 0 ? 'Активен' : 'На проверке',
                    'service_account' => $serviceAccount ?: 'Операционный счет не назначен',
                ];
            })
            ->values();
    }

    private function projectAccounts()
    {
        $projects = Project::query()
            ->orderBy('num')
            ->orderBy('name')
            ->get();

        $cashByProject = collect();
        if (Schema::hasTable('conf')) {
            $cashByProject = DB::table('conf')
                ->where('type', 'oplata')
                ->orderBy('name')
                ->get()
                ->map(fn ($account) => $this->normalizeCashAccount($account))
                ->groupBy(fn ($account) => (string) ($account->firma ?? ''));
        }

        return $projects
            ->map(function (Project $project) use ($cashByProject) {
                $accounts = $cashByProject->get((string) $project->id, collect())->values();

                return (object) [
                    'id' => (int) $project->id,
                    'name' => trim((string) ($project->name ?? '')) ?: 'Проект #' . $project->id,
                    'type' => trim((string) ($project->project_type ?? '')) ?: 'project',
                    'holding_name' => trim((string) ($project->holding_name ?? '')),
                    'phone' => trim((string) ($project->phone ?? '')),
                    'email' => trim((string) ($project->email ?? '')),
                    'cash_accounts' => $accounts,
                    'cash_count' => $accounts->count(),
                    'total_by_currency' => $accounts
                        ->groupBy('currency')
                        ->map(fn ($items) => (float) $items->sum('balance')),
                ];
            })
            ->values();
    }

    private function personOwners($clientAccounts)
    {
        return $clientAccounts
            ->filter(fn ($account) => $account->owner_type === 'person')
            ->groupBy('owner_id')
            ->map(function ($accounts) {
                $first = $accounts->first();

                return (object) [
                    'owner_id' => $first->owner_id,
                    'owner_name' => $first->owner_name,
                    'contact' => $first->contact,
                    'city' => $first->city,
                    'status' => $first->status,
                    'accounts' => $accounts->values(),
                    'accounts_count' => $accounts->count(),
                    'search_text' => mb_strtolower(implode(' ', [
                        $first->owner_name,
                        $first->phone,
                        $first->email,
                        $first->contact,
                        $first->city,
                    ])),
                    'total_by_currency' => $accounts
                        ->groupBy('currency')
                        ->map(fn ($items) => (float) $items->sum('balance')),
                ];
            })
            ->values();
    }

    private function ownerType(object $row): string
    {
        return trim((string) ($row->orgname ?? '')) !== '' || trim((string) ($row->kod1 ?? '')) !== ''
            ? 'company'
            : 'person';
    }

    private function ownerName(object $row): string
    {
        $orgName = trim((string) ($row->orgname ?? ''));
        if ($orgName !== '') {
            return $orgName;
        }

        $parts = array_filter([
            trim((string) ($row->secondname ?? '')),
            trim((string) ($row->name ?? $row->name2 ?? '')),
            trim((string) ($row->fathername ?? '')),
        ]);

        return $parts !== [] ? implode(' ', $parts) : 'Клиент #' . (string) ($row->userid ?? '');
    }

    private function ownerContact(object $row): string
    {
        $email = trim((string) ($row->email ?? ''));
        $phone = trim((string) ($row->phone ?? ''));

        if ($email !== '' && $phone !== '') {
            return "{$phone} · {$email}";
        }

        return $phone !== '' ? $phone : ($email !== '' ? $email : '—');
    }

    private function clientAccountNumber(object $row, string $currency): string
    {
        return 'AV8-' . str_pad((string) ($row->account_firma ?? '0'), 4, '0', STR_PAD_LEFT)
            . '-' . str_pad((string) ($row->userid ?? '0'), 6, '0', STR_PAD_LEFT)
            . '-' . $currency;
    }

    private function currencyFromName(string $name): string
    {
        $upper = mb_strtoupper($name);
        foreach (['USDC', 'USDT', 'USD', 'EUR', 'UAH', 'AV8', 'SUI'] as $currency) {
            if (str_contains($upper, $currency)) {
                return $currency;
            }
        }

        return 'UAH';
    }
}
