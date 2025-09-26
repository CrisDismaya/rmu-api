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
            'customer'        => $this->backfillCustomerProfile(),
            'inventory_in'    => $this->backfillInventoryIn(),
            'inventory_out'   => $this->backfillInventoryOut(),
            'stock_transfer'  => $this->backfillStockTransfer(),
            'receive_transfer'=> $this->backfillReceiveTransfer(),
            'sales'           => $this->backfillSales(),
            'rdaf'            => $this->backfillRDAF(),
            default           => $this->error("Invalid type: {$type}"),
        };
    }

    /**
     * Customer Profile Backfill
     */
    private function backfillCustomerProfile(): void
    {
        $customers = DB::table('customer_profile')
            ->orderBy('created_at')
            ->get();

        foreach ($customers as $customer) {
            $transactionNumber = $this->generateTransactionNumber('customer', $customer->created_at);

            DB::table('customer_profile')
                ->where('id', $customer->id)
                ->update(['acumatica_id' => $transactionNumber]);

            $this->info("Customer {$customer->id} updated → {$transactionNumber}");
        }
    }

    private function backfillInventoryIn()
    {
        $stockTransfers = DB::table('stock_transfer_approval as sta')
            ->join('stock_transfer_unit as stu', 'sta.id', '=', 'stu.stock_transfer_id')
            ->where('sta.status', 1)
            ->where('stu.is_received', 1)
            ->selectRaw("'stock_transfer' AS module, stu.id, sta.updated_at AS date");

        $repos = DB::table('repo_details')
            ->selectRaw("'repo' AS module, id, created_at AS date");

        $records = DB::query()
            ->fromSub(
                $stockTransfers->unionAll($repos),
                'InventoryIn_Temp'
            )
            ->orderBy('date')
            ->get();

        foreach ($records as $record) {
            $transactionNumber = $this->generateTransactionNumber('inventory_in', $record->date);

            $updateData = [
                'transaction_number_inventory_in' => $transactionNumber,
            ];

            if ($record->module === 'stock_transfer') {
                $updateData['inventory_in_at'] = now();
            }

            $table = $record->module === 'stock_transfer' ? 'stock_transfer_unit' : 'repo_details';

            DB::table($table)
                ->where('id', $record->id)
                ->update($updateData);

            $this->info("{$record->module} {$record->id} → {$transactionNumber}");
        }
    }

    private function backfillStockTransfer()
    {
        $stockTransfers = DB::table('stock_transfer_approval')->orderBy('created_at')->get();

        foreach ($stockTransfers as $index => $transfer) {
            $transactionNumber = $this->generateTransactionNumber('stock_transfer', $transfer->created_at);

            DB::table('stock_transfer_approval')
                ->where('id', $transfer->id)
                ->update(['reference_code' => $transactionNumber]);

            $this->info("Stock Transfer {$transfer->id} → {$transactionNumber}");
        }
    }

    private function backfillSales()
    {
        $sales = DB::table('sold_units')->orderBy('created_at')->get();

        foreach ($sales as $index => $sale) {
            $transactionNumber = $this->generateTransactionNumber('sales', $sale->created_at);

            DB::table('sold_units')
                ->where('id', $sale->id)
                ->update(['transaction_number' => $transactionNumber]);

            $this->info("Sales {$sale->id} updated → {$transactionNumber}");
        }
    }

    private function backfillRDAF()
    {
        $rdafs = DB::table('request_approvals')->orderBy('created_at')->get();

        foreach ($rdafs as $rdaf) {
            $transactionNumber = $this->generateTransactionNumber('rdaf', $rdaf->created_at);

            DB::table('request_approvals')
                ->where('id', $rdaf->id)
                ->update(['rdaf_transaction_number' => $transactionNumber]);

            $this->info("Rdaf {$rdaf->id} updated → {$transactionNumber}");
        }
    }

    private function backfillReceiveTransfer()
    {
        $receiveds = DB::table('stock_transfer_approval')
            ->join('stock_transfer_unit', 'stock_transfer_approval.id', '=', 'stock_transfer_unit.stock_transfer_id')
            ->select(
                'stock_transfer_unit.id as unit_id',
                'stock_transfer_unit.updated_at'
            )
            ->where('is_received', '=', 1)
            ->orderBy('stock_transfer_approval.created_at')
            ->get();

        foreach ($receiveds as $received) {
            $transactionNumber = $this->generateTransactionNumber('receive_transfer', $received->updated_at);

            DB::table('stock_transfer_unit')
                ->where('id', $received->unit_id)
                ->update([
                    'trans_no_received' => $transactionNumber,
                    'received_at' => now(),
                ]);

            $this->info("Received Transfer {$received->unit_id} updated → {$transactionNumber}");
        }
    }

    private function backfillInventoryOut()
    {
        $stockTransfers = DB::table('stock_transfer_approval as sta')
            ->join('stock_transfer_unit as stu', 'sta.id', '=', 'stu.stock_transfer_id')
            ->where('sta.status', 1)
            ->selectRaw("'stock_transfer' AS module, stu.id, sta.updated_at AS date");

        $soldUnits = DB::table('sold_units')
            ->where('status', 1)
            ->selectRaw("'sold_unit' AS module, id, updated_at");

        $records = DB::query()
            ->fromSub(
                $stockTransfers->unionAll($soldUnits),
                'InventoryOut_Temp'
            )
            ->orderBy('date')
            ->get();

        foreach ($records as $record) {
            $transactionNumber = $this->generateTransactionNumber('inventory_out', $record->date);

            $updateData = [
                'transaction_number_inventory_out' => $transactionNumber,
                'inventory_out_at' => now(),
            ];

            $table = $record->module === 'stock_transfer' ? 'stock_transfer_unit' : 'sold_units';

            DB::table($table)
                ->where('id', $record->id)
                ->update($updateData);

            $this->info("{$record->module} {$record->id} → {$transactionNumber}");
        }
    }
}
