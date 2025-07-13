<?php

namespace App\Http\Traits;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

trait TransactionNumberGenerator {

    const DEFAULT_LENGTH = 5;
    const DEFAULT_PREFIX = [
        'customer' => 'B',
        'inventory_in' => 'RI',
        'inventory_out' => 'RO',
        'stock_transfer' => 'ST',
        'receive_transfer' => 'RT',
        'sales' => 'RS',
        'rdaf' => 'RDAF',
    ];

    public function generateTransactionNumber(string $type, int $uniqueID, $date = null): string
    {
        $prefix = self::DEFAULT_PREFIX[$type] ?? strtoupper(Str::substr($type, 0, 2));
        $date = $date ?? Carbon::now()->toDateString();
        $year = Carbon::parse($date)->format('Y');

        if (strlen((string) $uniqueID) >= self::DEFAULT_LENGTH) {
            $formattedId = "0". (string) $uniqueID;
        } else {
            $formattedId = str_pad((string) $uniqueID, self::DEFAULT_LENGTH, '0', STR_PAD_LEFT);
        }

        return "{$prefix}-{$year}-{$formattedId}";
    }

    public function generateTransactionNumberInventoryOut(string $processType, int $uniqueID): void
    {
        $counter = self::forInventoryOut(); // 1

        if ($processType == 'stock_transfer') {
            $checker = DB::table('stock_transfer_unit')
                ->where('stock_transfer_id', $uniqueID)
                ->get();

            if ($checker->isNotEmpty()) {
                foreach ($checker as $index => $unit) {
                    $increment = $counter + $index;
                    $transactionNumber = $this->generateTransactionNumber('inventory_out', $increment);

                    DB::table('stock_transfer_unit')
                        ->where('id', $unit->id)
                        ->update([
                            'transaction_number_inventory_out' => $transactionNumber,
                            'inventory_out_at' => Carbon::now(),
                        ]);
                }
            }
        }

        if ($processType == 'sold_unit') {
            $checker = DB::table('sold_units')
                ->where('id', $uniqueID)
                ->get();

            if ($checker->isNotEmpty()) {
                foreach ($checker as $index => $unit) {
                    $increment = $counter + $index;
                    $transactionNumber = $this->generateTransactionNumber('inventory_out', $increment);

                    DB::table('sold_units')
                        ->where('id', $unit->id)
                        ->update([
                            'transaction_number_inventory_out' => $transactionNumber,
                            'inventory_out_at' => Carbon::now(),
                        ]);
                }
            }
        }
    }

    private static function forInventoryOut(): int
    {
        $result = DB::selectOne("
            SELECT COUNT(*) as total
            FROM (
                SELECT
                    'received_transfer' AS identif,
                    stu.recieved_unit_id AS unique_id,
                    stu.transaction_number_inventory_out,
                    stu.updated_at
                FROM stock_transfer_approval sta
                INNER JOIN stock_transfer_unit stu ON sta.id = stu.stock_transfer_id
                WHERE stu.transaction_number_inventory_out IS NOT NULL

                UNION ALL

                SELECT
                    'sold_unit',
                    sold.repo_id,
                    sold.transaction_number_inventory_out,
                    sold.updated_at
                FROM sold_units sold
                WHERE sold.transaction_number_inventory_out IS NOT NULL
            ) AS inventory_out_temp
        ");

        return (int) $result->total + 1;
    }

    private static function forReceiveTransfer(): int
    {
        $result = DB::selectOne("
            SELECT COUNT(*) as total
            FROM (
                SELECT
                    'received_transfer' AS identif,
                    stu.recieved_unit_id AS unique_id,
                    stu.transaction_number_inventory_out,
                    stu.updated_at
                FROM stock_transfer_approval sta
                INNER JOIN stock_transfer_unit stu ON sta.id = stu.stock_transfer_id
                WHERE stu.transaction_number_inventory_out IS NOT NULL
            ) AS receive_transfer_temp
        ");

        return (int) $result->total + 1;
    }
}
