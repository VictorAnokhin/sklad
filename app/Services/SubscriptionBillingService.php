<?php

namespace App\Services;

use App\Models\Document;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SubscriptionBillingService
{
    public function billDue(?int $projectId = null): int
    {
        $query = DB::table('customer_subscriptions')
            ->where('status', 'active')
            ->where('auto_create_invoice', true)
            ->whereNotNull('next_billing_at')
            ->whereDate('next_billing_at', '<=', now()->toDateString());

        if ($projectId) {
            $query->where('project_id', $projectId);
        }

        $created = 0;
        foreach ($query->orderBy('next_billing_at')->orderBy('id')->get() as $subscription) {
            if ($this->billSubscription((int) $subscription->id)) {
                $created++;
            }
        }

        return $created;
    }

    public function billSubscription(int $subscriptionId): bool
    {
        $subscription = DB::table('customer_subscriptions')->where('id', $subscriptionId)->first();
        if (! $subscription || (string) $subscription->status !== 'active') {
            return false;
        }

        $plan = DB::table('subscription_plans')->where('id', $subscription->plan_id)->first();
        if (! $plan || ! (bool) $plan->active) {
            return false;
        }

        $items = DB::table('subscription_plan_items')
            ->where('plan_id', $plan->id)
            ->orderBy('sort')
            ->orderBy('id')
            ->get();

        $periodFrom = CarbonImmutable::parse($subscription->next_billing_at ?: now()->toDateString());
        $periodTo = $this->nextDate($periodFrom, (string) $plan->billing_period, (int) $plan->interval_count)->subDay();
        $dueAt = $periodFrom->addDays((int) ($plan->payment_due_days ?? 5));

        $exists = DB::table('subscription_invoices')
            ->where('subscription_id', $subscription->id)
            ->whereDate('period_from', $periodFrom->toDateString())
            ->whereDate('period_to', $periodTo->toDateString())
            ->exists();
        if ($exists) {
            return false;
        }

        DB::transaction(function () use ($subscription, $plan, $items, $periodFrom, $periodTo, $dueAt): void {
            $now = now();
            $year = $now->format('Y');
            $documentNumber = Document::nextNum('ZOUT', (string) $subscription->project_id, $year);
            $linesTotal = (float) $items->sum(fn ($item): float => (float) $item->quantity * (float) $item->price);
            $amount = $linesTotal > 0 ? $linesTotal : (float) $plan->price;

            $documentId = $this->createDocument($subscription, $plan, $documentNumber, $amount, $periodFrom, $periodTo);
            $this->createLines($documentId, $documentNumber, $subscription, $plan, $items, $amount);

            DB::table('subscription_invoices')->insert([
                'subscription_id' => $subscription->id,
                'document_id' => $documentId,
                'period_from' => $periodFrom->toDateString(),
                'period_to' => $periodTo->toDateString(),
                'due_at' => $dueAt->toDateString(),
                'status' => 'pending',
                'amount' => $amount,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('customer_subscriptions')->where('id', $subscription->id)->update([
                'payment_status' => 'pending',
                'next_billing_at' => $periodTo->addDay()->toDateString(),
                'grace_until' => $dueAt->addDays((int) ($plan->grace_days ?? 3))->toDateString(),
                'updated_at' => $now,
            ]);
        });

        return true;
    }

    public function enforceBlocks(?int $projectId = null): int
    {
        $query = DB::table('customer_subscriptions as cs')
            ->join('subscription_plans as sp', 'sp.id', '=', 'cs.plan_id')
            ->whereIn('cs.status', ['active', 'blocked'])
            ->where('sp.block_on_overdue', true);

        if ($projectId) {
            $query->where('cs.project_id', $projectId);
        }

        $changed = 0;
        foreach ($query->select('cs.*', 'sp.grace_days')->get() as $subscription) {
            $oldest = DB::table('subscription_invoices')
                ->where('subscription_id', $subscription->id)
                ->whereIn('status', ['pending', 'overdue'])
                ->orderBy('due_at')
                ->first();

            if (! $oldest) {
                if ((string) $subscription->status === 'blocked' && (string) $subscription->block_reason === 'unpaid_invoice') {
                    DB::table('customer_subscriptions')->where('id', $subscription->id)->update([
                        'status' => 'active',
                        'payment_status' => 'paid',
                        'blocked_at' => null,
                        'grace_until' => null,
                        'block_reason' => '',
                        'updated_at' => now(),
                    ]);
                    $changed++;
                }
                continue;
            }

            if ($oldest->due_at && CarbonImmutable::parse($oldest->due_at)->lt(CarbonImmutable::today())) {
                DB::table('subscription_invoices')->where('id', $oldest->id)->update([
                    'status' => 'overdue',
                    'updated_at' => now(),
                ]);

                $graceUntil = CarbonImmutable::parse($oldest->due_at)->addDays((int) ($subscription->grace_days ?? 3));
                $shouldBlock = $graceUntil->lt(CarbonImmutable::today());
                DB::table('customer_subscriptions')->where('id', $subscription->id)->update([
                    'status' => $shouldBlock ? 'blocked' : 'active',
                    'payment_status' => 'overdue',
                    'blocked_at' => $shouldBlock ? now() : null,
                    'grace_until' => $graceUntil->toDateString(),
                    'block_reason' => $shouldBlock ? 'unpaid_invoice' : '',
                    'updated_at' => now(),
                ]);
                $changed++;
            }
        }

        return $changed;
    }

    public function markInvoicePaid(int $invoiceId): void
    {
        $invoice = DB::table('subscription_invoices')->where('id', $invoiceId)->first();
        if (! $invoice) {
            return;
        }

        DB::transaction(function () use ($invoice): void {
            DB::table('subscription_invoices')->where('id', $invoice->id)->update([
                'status' => 'paid',
                'paid_at' => now(),
                'updated_at' => now(),
            ]);

            $hasDebt = DB::table('subscription_invoices')
                ->where('subscription_id', $invoice->subscription_id)
                ->whereIn('status', ['pending', 'overdue'])
                ->exists();

            DB::table('customer_subscriptions')->where('id', $invoice->subscription_id)->update([
                'status' => $hasDebt ? 'active' : 'active',
                'payment_status' => $hasDebt ? 'overdue' : 'paid',
                'last_paid_until' => $invoice->period_to,
                'blocked_at' => $hasDebt ? DB::raw('blocked_at') : null,
                'block_reason' => $hasDebt ? DB::raw('block_reason') : '',
                'updated_at' => now(),
            ]);
        });
    }

    private function createDocument(object $subscription, object $plan, int|string $documentNumber, float $amount, CarbonImmutable $periodFrom, CarbonImmutable $periodTo): int
    {
        $payload = [
            'num' => (string) $documentNumber,
            'type' => 'ZOUT',
            'firma' => (string) $subscription->project_id,
            'client1' => (string) $subscription->client_id,
            'client2' => '0',
            'summa' => $amount,
            'data' => now()->format('d-m-Y'),
            'data2' => now()->format('d-m-Y'),
            'time' => now()->format('H:i:s'),
            'dt' => now()->timestamp,
            'manager' => 'subscriptions',
            'user' => 'subscriptions',
            'content' => "Subscription #{$subscription->id}; plan: {$plan->name}; period: {$periodFrom->toDateString()} - {$periodTo->toDateString()}",
            'numz' => (string) $documentNumber,
            'typez' => 'ZOUT',
            'docum' => 'subscription',
            'provodka' => 0,
            'dostup' => 1,
            'numdoc' => 'subscription',
            'close' => 0,
            'typeproduct' => 'subscription',
        ];

        $payload = array_intersect_key($payload, array_flip(Schema::getColumnListing('document')));

        return (int) DB::table('document')->insertGetId($payload);
    }

    private function createLines(int $documentId, int|string $documentNumber, object $subscription, object $plan, iterable $items, float $amount): void
    {
        if (! Schema::hasTable('z_body')) {
            return;
        }

        $rows = collect($items)->map(function (object $item) use ($documentId, $documentNumber, $subscription): array {
            $quantity = (float) $item->quantity;
            $price = (float) $item->price;

            return [
                'docnum' => (string) $documentNumber,
                'pid' => '0',
                'pnum' => (string) $item->product_id,
                'pcount' => $quantity,
                'pprice' => $price,
                'psumma' => $quantity * $price,
                'type' => 'ZOUT',
                'firma' => (string) $subscription->project_id,
                'docid' => (string) $documentId,
            ];
        });

        if ($rows->isEmpty()) {
            $rows->push([
                'docnum' => (string) $documentNumber,
                'pid' => '0',
                'pnum' => '0',
                'pcount' => 1,
                'pprice' => $amount,
                'psumma' => $amount,
                'type' => 'ZOUT',
                'firma' => (string) $subscription->project_id,
                'docid' => (string) $documentId,
            ]);
        }

        $columns = Schema::getColumnListing('z_body');
        DB::table('z_body')->insert($rows->map(fn (array $row): array => array_intersect_key($row, array_flip($columns)))->all());
    }

    private function nextDate(CarbonImmutable $date, string $period, int $interval): CarbonImmutable
    {
        $interval = max(1, $interval);

        return match ($period) {
            'week' => $date->addWeeks($interval),
            'quarter' => $date->addMonths(3 * $interval),
            'year' => $date->addYears($interval),
            default => $date->addMonths($interval),
        };
    }
}
