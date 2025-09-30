<?php

namespace App\Http\Traits;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

trait TransactionNumberGenerator {

    const DEFAULT_LENGTH = 5;
    const DEFAULT_PREFIX = [
        'customer'         => 'B',
        'inventory_in'     => 'RI',
        'inventory_out'    => 'RO',
        'stock_transfer'   => 'ST',
        'receive_transfer' => 'RT',
        'sales'            => 'RS',
        'rdaf'             => 'RDAF',
    ];

    private function getPrefix(string $type): string
    {
        return self::DEFAULT_PREFIX[$type] ?? strtoupper(Str::substr($type, 0, 2));
    }

    private function getTypeCount(string $type): int
    {
        return match ($type) {
            'customer' => self::forCustomerCount(),
            'inventory_in' => self::forInventoryInCount(),
            'stock_transfer' => self::forStockTransferCount(),
            'sales' => self::forSaleCount(),
            'rdaf' => self::forRdafPdfCount(),
            'receive_transfer' => self::forReceivedTransferCount(),
            'inventory_out' => self::forInventoryOutCount(),
            default => 1,
        };
    }

    private function getYear(?string $date): string
    {
        $date = $date ?? Carbon::now()->toDateString();
        return Carbon::parse($date)->format('Y');
    }

     /**
     * Fetch and increment the next sequence number for a type
     */
    private function getNextSequence(string $type): int
    {
        return DB::transaction(function () use ($type) {
            $row = DB::table('transaction_sequences')
                ->where('type', $type)
                ->lockForUpdate()
                ->first();

            if (!$row) {
                // Initialize if missing
                DB::table('transaction_sequences')->insert([
                    'type'           => $type,
                    'current_number' => 1,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
                return 1;
            }

            $next = $row->current_number + 1;

            DB::table('transaction_sequences')
                ->where('type', $type)
                ->update([
                    'current_number' => $next,
                    'updated_at'     => now(),
                ]);

            return $next;
        });
    }

    /**
     * Generate a transaction number
     */
    public function generateTransactionNumber(string $type, ?string $date = null): string
    {
        $prefix  = $this->getPrefix($type);
        $year    = $this->getYear($date);
        $counter = $this->getNextSequence($type);

        $length = strlen((string) $counter) > self::DEFAULT_LENGTH
            ? strlen((string) $counter) + 1
            : self::DEFAULT_LENGTH;

        $formattedId = str_pad((string) $counter, $length, '0', STR_PAD_LEFT);

        return "{$prefix}-{$year}-{$formattedId}";
    }

    public static function forCustomerCount(): int
    {
        $count = DB::table('customer_profile')->count();
        return (int) $count + 1;
    }

    public static function forInventoryInCount(): int
    {
        $stockTransfers = DB::table('stock_transfer_approval as sta')
            ->join('stock_transfer_unit as stu', 'sta.id', '=', 'stu.stock_transfer_id')
            ->where('sta.status', 1)
            ->where('is_received', '=', 1)
            ->selectRaw("'stock_transfer' AS module, stu.id, sta.updated_at AS date");

        $repos = DB::table('repo_details as repo')
            ->selectRaw("'repo' AS module, id, created_at AS date");

        $count = DB::query()
            ->fromSub(
                $stockTransfers->unionAll($repos),
                'InventoryOut_Temp'
            )
            ->count();

        return (int) $count + 1;
    }

    public static function forStockTransferCount(): int
    {
        $count = DB::table('stock_transfer_approval')->whereNotNull('reference_code')->count();
        return (int) $count + 1;
    }

    public static function forRdafPdfCount(): int
    {
        $count = DB::table('request_approvals')->whereNotNull('rdaf_transaction_number')->count();
        return (int) $count + 1;
    }

    public static function forSaleCount(): int
    {
        $count = DB::table('sold_units')->whereNotNull('transaction_number')->count();
        return (int) $count + 1;
    }

    public static function forReceivedTransferCount(): int
    {
        $count = DB::table('stock_transfer_approval')
            ->join('stock_transfer_unit', 'stock_transfer_approval.id', '=', 'stock_transfer_unit.stock_transfer_id')
            ->whereNotNull('trans_no_received')
            ->where('is_received', '=', 1)
            ->count();

        return (int) $count + 1;
    }

    public static function forInventoryOutCount(): int
    {
        $stockTransfers = DB::table('stock_transfer_approval as sta')
            ->join('stock_transfer_unit as stu', 'sta.id', '=', 'stu.stock_transfer_id')
            ->where('sta.status', 1)
            ->whereNotNull('stu.transaction_number_inventory_out')
            ->selectRaw("'stock_transfer' AS module, stu.id, sta.updated_at");

        $soldUnits = DB::table('sold_units')
            ->where('status', 1)
            ->whereNotNull('transaction_number_inventory_out')
            ->selectRaw("'sold_unit' AS module, id, updated_at");

        $count = DB::query()
            ->fromSub(
                $stockTransfers->unionAll($soldUnits),
                'InventoryOut_Temp'
            )
            ->count();

        return (int) $count + 1;
    }
}
