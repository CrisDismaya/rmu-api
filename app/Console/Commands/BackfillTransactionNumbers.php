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
            'customer' => $this->backfillCustomerProfile(),
            'inventory_in' => $this->backfillInventoryIn(),
            'inventory_out' => $this->backfillInventoryOut(),
            'stock_transfer' => $this->backfillStockTransfer(),
            'receive_transfer' => $this->backfillReceiveTransfer(),
            'sales' => $this->backfillSales(),
            'rdaf' => $this->backfillRDAF(),
            default => $this->error('Invalid type specified.'),
        };
    }

    private function backfillInventoryIn()
    {
        $stockTransfers = DB::table('stock_transfer_approval as sta')
            ->join('stock_transfer_unit as stu', 'sta.id', '=', 'stu.stock_transfer_id')
            ->where('sta.status', 1)
            ->where('stu.is_received', 1)
            ->selectRaw("'stock_transfer' AS module, stu.id, sta.updated_at AS date");

        $repos = DB::table('repo_details')
            ->selectRaw("'repo' AS module, id, created_at");

        $records = DB::query()
            ->fromSub(
                $stockTransfers->unionAll($repos),
                'InventoryIn_Temp'
            )
            ->orderBy('date')
            ->get();

        foreach ($records as $index => $record) {
            $rowNumber = $index + 1;
            $transactionNumber = $this->generateTransactionNumber('inventory_in', $rowNumber, $record->date);

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
        }
    }

    private function backfillCustomerProfile()
    {
        $customers = DB::table('customer_profile')->orderBy('created_at')->get();

        foreach ($customers as $index => $customer) {
            $rowNumber = $index + 1;
            $transactionNumber = $this->generateTransactionNumber('customer', $rowNumber, $customer->created_at);

            DB::table('customer_profile')
                ->where('id', $customer->id)
                ->update(['acumatica_id' => $transactionNumber]);
        }
    }

    private function backfillStockTransfer()
    {
        $stockTransfers = DB::table('stock_transfer_approval')->orderBy('created_at')->get();

        foreach ($stockTransfers as $index => $transfer) {
            $rowNumber = $index + 1;
            $transactionNumber = $this->generateTransactionNumber('stock_transfer', $rowNumber, $transfer->created_at);

            DB::table('stock_transfer_approval')
                ->where('id', $transfer->id)
                ->update(['reference_code' => $transactionNumber]);
        }
    }

    private function backfillSales()
    {
        $sales = DB::table('sold_units')->orderBy('created_at')->get();

        foreach ($sales as $index => $sale) {
            $rowNumber = $index + 1;
            $transactionNumber = $this->generateTransactionNumber('sales', $rowNumber, $sale->created_at);

            DB::table('sold_units')
                ->where('id', $sale->id)
                ->update(['transaction_number' => $transactionNumber]);
        }
    }

    private function backfillRDAF()
    {
        $rdafs = DB::table('request_approvals')->orderBy('created_at')->get();

        foreach ($rdafs as $index => $rdaf) {
            $rowNumber = $index + 1;
            $transactionNumber = $this->generateTransactionNumber('rdaf', $rowNumber, $rdaf->created_at);

            DB::table('request_approvals')
                ->where('id', $rdaf->id)
                ->update(['rdaf_transaction_number' => $transactionNumber]);
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

        foreach ($receiveds as $index => $received) {
            $rowNumber = $index + 1;
            $transactionNumber = $this->generateTransactionNumber('receive_transfer', $rowNumber, $received->updated_at);

            DB::table('stock_transfer_unit')
                ->where('id', $received->unit_id)
                ->update([
                    'trans_no_received' => $transactionNumber,
                    'received_at' => now(),
                ]);
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

        foreach ($records as $index => $record) {
            $rowNumber = $index + 1;
            $transactionNumber = $this->generateTransactionNumber('inventory_out', $rowNumber, $record->date);

            $updateData = [
                'transaction_number_inventory_out' => $transactionNumber,
                'inventory_out_at' => now(),
            ];

            $table = $record->module === 'stock_transfer' ? 'stock_transfer_unit' : 'sold_units';

            DB::table($table)
                ->where('id', $record->id)
                ->update($updateData);
        }
    }
}
