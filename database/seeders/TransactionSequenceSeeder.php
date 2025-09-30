<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TransactionSequenceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::beginTransaction();

        $sequences = [
            ['type' => 'customer', 'current_number' => 0],
            ['type' => 'inventory_in', 'current_number' => 0],
            ['type' => 'inventory_out', 'current_number' => 0],
            ['type' => 'stock_transfer', 'current_number' => 0],
            ['type' => 'receive_transfer', 'current_number' => 0],
            ['type' => 'sales', 'current_number' => 0],
            ['type' => 'rdaf', 'current_number' => 0],
        ];

        foreach ($sequences as $sequence) {
            DB::table('transaction_sequences')->updateOrInsert(
                ['type' => $sequence['type']],
                $sequence
            );
        }

        DB::commit();

    }
}
