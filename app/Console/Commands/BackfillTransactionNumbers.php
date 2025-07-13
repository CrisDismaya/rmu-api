<?php

namespace App\Console\Commands;

use App\Http\Traits\TransactionNumberGenerator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillTransactionNumbers extends Command
{
    use TransactionNumberGenerator;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transaction:backfill {type}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill transaction numbers for old records';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $type = $this->argument('type');

        match ($type) {
            'customer' => $this->backfillCustomerProfile(), // done
            'inventory_in' => $this->backfillInventoryIn(), // done
            'inventory_out' => $this->backfillInventoryOut(),
            'stock_transfer' => $this->backfillStockTransfer(), // done
            'receive_transfer' => $this->backfillReceiveTransfer(),
            'sales' => $this->backfillSales(), // done
            'rdaf' => $this->backfillRDAF(),
            default => $this->error('Invalid type specified.'),
        };
    }

    private function backfillInventoryIn()
    {
        $repos = DB::table('repo_details')->get();

        foreach ($repos as $repo) {
            $transactionNumber = $this->generateTransactionNumber('inventory_in', $repo->id, $repo->created_at);
            DB::table('repo_details')
                ->where('id', $repo->id)
                ->update(['transaction_number_inventory_in' => $transactionNumber]);
        }
    }

    private function backfillCustomerProfile()
    {
        $customers = DB::table('customer_profile')->get();

        foreach ($customers as $customer) {
            $transactionNumber = $this->generateTransactionNumber('customer', $customer->id, $customer->created_at);
            DB::table('customer_profile')
                ->where('id', $customer->id)
                ->update(['acumatica_id' => $transactionNumber]);
        }
    }

    private function backfillStockTransfer()
    {
        $stockTransfers = DB::table('stock_transfer_approval')->get();

        foreach ($stockTransfers as $transfer) {
            $transactionNumber = $this->generateTransactionNumber('stock_transfer', $transfer->id, $transfer->created_at);
            DB::table('stock_transfer_approval')
                ->where('id', $transfer->id)
                ->update(['reference_code' => $transactionNumber]);
        }
    }

    private function backfillSales()
    {
        $sales = DB::table('sold_units')->get();

        foreach ($sales as $sale) {
            $transactionNumber = $this->generateTransactionNumber('sales', $sale->id, $sale->created_at);
            DB::table('sold_units')
                ->where('id', $sale->id)
                ->update(['transaction_number' => $transactionNumber]);
        }
    }
}
