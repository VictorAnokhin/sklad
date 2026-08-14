<?php

namespace App\Console\Commands;

use App\Services\SubscriptionBillingService;
use Illuminate\Console\Command;

class BillSubscriptions extends Command
{
    protected $signature = 'subscriptions:bill {--project_id= : Bill only one project} {--enforce-only : Only apply overdue blocking rules}';

    protected $description = 'Create subscription invoices and block overdue unpaid subscriptions.';

    public function handle(SubscriptionBillingService $billing): int
    {
        $projectId = $this->option('project_id') ? (int) $this->option('project_id') : null;
        $created = $this->option('enforce-only') ? 0 : $billing->billDue($projectId);
        $changed = $billing->enforceBlocks($projectId);

        $this->info("Subscription billing complete. Created: {$created}. Status changes: {$changed}.");

        return self::SUCCESS;
    }
}
