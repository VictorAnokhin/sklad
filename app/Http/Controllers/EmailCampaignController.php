<?php

namespace App\Http\Controllers;

use App\Services\EmailProviderService;
use App\Support\HoldingScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EmailCampaignController extends Controller
{
    public function index(EmailProviderService $emailProvider)
    {
        $fid = (string) session('fid', '');

        return view('email_campaigns.index', [
            'providerSettings' => $emailProvider->settings(),
            'recipientCount' => $this->recipients($fid, 'clients')->count(),
            'subscribersCount' => $this->recipients($fid, 'subscribers')->count(),
            'inactiveCount' => $this->recipients($fid, 'inactive')->count(),
        ]);
    }

    public function send(Request $request, EmailProviderService $emailProvider)
    {
        $validated = $request->validate([
            'segment' => ['required', 'in:test,clients,subscribers,inactive'],
            'test_email' => ['required_if:segment,test', 'nullable', 'email', 'max:255'],
            'subject' => ['required', 'string', 'max:180'],
            'body' => ['required', 'string', 'max:8000'],
        ]);

        if (! $emailProvider->configured()) {
            return redirect()->route('email-campaigns.index')
                ->with('error', 'Сначала настройте email провайдера в settings.');
        }

        $fid = (string) session('fid', '');
        $recipients = $validated['segment'] === 'test'
            ? collect([(object) ['email' => $validated['test_email'] ?? '']])
            : $this->recipients($fid, $validated['segment']);

        if ($recipients->isEmpty()) {
            return redirect()->route('email-campaigns.index')->with('error', 'Получатели не найдены.');
        }

        $sent = 0;
        $failed = 0;
        $html = nl2br(e($validated['body']));

        foreach ($recipients as $recipient) {
            $email = trim((string) ($recipient->email ?? ''));
            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $failed++;
                continue;
            }

            try {
                $emailProvider->send($email, $validated['subject'], $html, strip_tags($validated['body']));
                $sent++;
            } catch (\Throwable) {
                $failed++;
            }
        }

        return redirect()->route('email-campaigns.index')
            ->with('success', "Рассылка завершена. Отправлено: {$sent}. Ошибок: {$failed}.");
    }

    private function recipients(string $fid, string $segment)
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'email')) {
            return collect();
        }

        $query = DB::table('users')
            ->whereIn('firma', HoldingScope::projectIdsFor($fid))
            ->whereNotNull('email')
            ->where('email', '<>', '');

        if ($segment === 'subscribers' && Schema::hasTable('customer_subscriptions')) {
            $query
                ->join('customer_subscriptions as cs', 'cs.client_id', '=', 'users.id')
                ->where('cs.status', 'active')
                ->select('users.email')
                ->distinct();
        } else {
            if ($segment === 'inactive' && Schema::hasColumn('users', 'last_login_at')) {
                $query->where(function ($inactiveQuery) {
                    $inactiveQuery
                        ->whereNull('last_login_at')
                        ->orWhere('last_login_at', '<', now()->subDays(30));
                });
            }
            $query->select('email')->distinct();
        }

        return $query->limit(500)->get();
    }
}
