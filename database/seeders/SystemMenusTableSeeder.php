<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class SystemMenusTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $system_menu = [
            [ "id" => 38, "category_name" => "Pages", "parent_id" => 0, "menu_name" => "Physical Inventory", "file_path" => "_physical_inventory.php", "status" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 39, "category_name" => "Settings", "parent_id" => 9, "menu_name" => "User Access Management", "file_path" => "_user-access-management.php", "status" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
        ];

        DB::beginTransaction();
            DB::unprepared('SET IDENTITY_INSERT system_menu ON');
                foreach ($system_menu as $menu) {
                    DB::table('system_menu')->updateOrInsert(
                        ['id' => $menu['id']],
                        $menu
                    );
                }
            DB::unprepared('SET IDENTITY_INSERT system_menu OFF');
        DB::commit();
    }
}
