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

        if (Schema::hasTable('conf')) {
            $cashAccounts = DB::table('conf')
                ->where('type', 'oplata')
                ->where('firma', (string) $project->id)
                ->orderBy('name')
                ->get()
                ->map(fn ($account) => $this->normalizeCashAccount($account));
        }

        return view('bank.cash_accounts', [
            'project' => $project,
            'cashAccounts' => $cashAccounts,
            'totalByCurrency' => $cashAccounts
                ->groupBy('currency')
                ->map(fn ($items) => (float) $items->sum('balance')),
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
