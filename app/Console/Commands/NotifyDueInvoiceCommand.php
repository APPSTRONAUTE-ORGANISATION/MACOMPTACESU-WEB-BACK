<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Notifications\DueInvoiceNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class NotifyDueInvoiceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:notify-due-invoice';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notify due invoices';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $query = Invoice::query();

        $query->with([
            'User',
            'Client',
            'Activity',
        ]);

        $query->withSum([
            'Payments' => fn($query) => $query->select(DB::raw('IFNULL(SUM(amount), 0)')),
        ], 'amount');

        $due_date = now()->addDays(3);

        $query->whereDate('due_date', '<=', $due_date->toDateString());

        $query->havingRaw('payments_sum_amount < total');

        $this->info('Found ' . $query->count() . ' due invoices');
        $query->get()->each(function ($invoice) {
            $invoice->Client->notify(new DueInvoiceNotification($invoice));
        });
    }
}
