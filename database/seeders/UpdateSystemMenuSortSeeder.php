<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class UpdateSystemMenuSortSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $system_menu = [
            // MAIN MENUS
            ["id" => 1, "sort" => 10.000],
            ["id" => 2, "sort" => 20.000],
            ["id" => 38, "sort" => 30.000],
            ["id" => 7, "sort" => 40.000],
            ["id" => 28, "sort" => 50.000],
            ["id" => 29, "sort" => 60.000],
            ["id" => 30, "sort" => 70.000],
            ["id" => 32, "sort" => 80.000],
            ["id" => 31, "sort" => 90.000],
            ["id" => 9, "sort" => 100.000],

            // SUBMENUS UNDER 28
            ["id" => 3, "sort" => 50.100],
            ["id" => 4, "sort" => 50.200],
            ["id" => 22, "sort" => 50.300],

            // SUBMENUS UNDER 29
            ["id" => 5, "sort" => 60.100],
            ["id" => 19, "sort" => 60.200],

            // SUBMENUS UNDER 30
            ["id" => 6, "sort" => 70.100],
            ["id" => 23, "sort" => 70.200],
            ["id" => 26, "sort" => 70.300],

            // SUBMENUS UNDER 32
            ["id" => 25, "sort" => 80.100],

            // SUBMENUS UNDER 31
            ["id" => 8, "sort" => 90.100],
            ["id" => 24, "sort" => 90.200],
            ["id" => 33, "sort" => 90.300],
            ["id" => 35, "sort" => 90.400],
            ["id" => 36, "sort" => 90.500],
            ["id" => 37, "sort" => 90.600],

            // SUBMENUS UNDER 9
            ["id" => 10, "sort" => 100.100],
            ["id" => 16, "sort" => 100.200],
            ["id" => 15, "sort" => 100.300],
            ["id" => 17, "sort" => 100.400],
            ["id" => 39, "sort" => 100.500],
            ["id" => 11, "sort" => 100.600],
            ["id" => 12, "sort" => 100.700],
            ["id" => 13, "sort" => 100.800],
            ["id" => 14, "sort" => 100.900],
            ["id" => 21, "sort" => 101.000],
            ["id" => 34, "sort" => 101.100],
            ["id" => 18, "sort" => 101.200],
        ];

        DB::beginTransaction();
        try {
            foreach ($system_menu as $menu) {
                DB::table('system_menu')->where('id', $menu['id'])->update([
                    'sort' => $menu['sort'],
                    'updated_at' => Carbon::now(),
                ]);
            }

            DB::commit();
            $this->command->info('System menu sort values updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('Failed to update system menu sort values: ' . $e->getMessage());
        }
    }
}
