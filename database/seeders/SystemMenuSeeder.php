<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;

class SystemMenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        DB::beginTransaction();
        DB::unprepared('SET IDENTITY_INSERT system_menu ON');
        //default menus
        DB::table('system_menu')->insert([
            'id'       => "1",
            'category_name' => 'Dashboard',
            'parent_id'       => "0",
            'menu_name'       => "Dashboard",
            'file_path'       => "dashboard.php",
            'status'       => "1",
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        DB::table('system_menu')->insert([
            'id'       => "2",
            'category_name' => 'Pages',
            'parent_id'       => "0",
            'menu_name'       => "Customer Profiling",
            'file_path'       => "_customer-profiling.php",
            'status'       => "1",
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        DB::table('system_menu')->insert([
            'id'       => "3",
            'category_name' => 'Pages',
            'parent_id'       => "0",
            'menu_name'       => "Repo Details",
            'file_path'       => "_repo-create.php",
            'status'       => "1",
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        DB::table('system_menu')->insert([
            'id'       => "4",
            'category_name' => 'Pages',
            'parent_id'       => "0",
            'menu_name'       => "Receive of Units",
            'file_path'       => "_receiving-of-units.php",
            'status'       => "1",
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        DB::table('system_menu')->insert([
            'id'       => "5",
            'category_name' => 'Pages',
            'parent_id'       => "0",
            'menu_name'       => "Stock Transfer",
            'file_path'       => "_stock_transfer.php",
            'status'       => "1",
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        DB::table('system_menu')->insert([
            'id'       => "6",
            'category_name' => 'Pages',
            'parent_id'       => "0",
            'menu_name'       => "Request Repo Price",
            'file_path'       => "_approval-unit.php",
            'status'       => "1",
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        DB::table('system_menu')->insert([
            'id'       => "7",
            'category_name' => 'Pages',
            'parent_id'       => "0",
            'menu_name'       => "Physical Inventory",
            'file_path'       => "inventory.php",
            'status'       => "1",
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        DB::table('system_menu')->insert([
            'id'       => "8",
            'category_name' => 'Pages',
            'parent_id'       => "0",
            'menu_name'       => "Sold Units",
            'file_path'       => "sold-unit.php",
            'status'       => "1",
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        DB::table('system_menu')->insert([
            'id'       => "9",
            'category_name' => 'Pages',
            'parent_id'       => "0",
            'menu_name'       => "Maintenance",
            'file_path'       => null,
            'status'       => "1",
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        DB::table('system_menu')->insert([
            'id'       => "10",
            'category_name' => 'Settings',
            'parent_id'       => "9",
            'menu_name'       => "Branch Management",
            'file_path'       => "_branch-create.php",
            'status'       => "1",
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        DB::table('system_menu')->insert([
            'id'       => "11",
            'category_name' => 'Settings',
            'parent_id'       => "9",
            'menu_name'       => "Brand Management",
            'file_path'       => "_brand-create.php",
            'status'       => "1",
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        DB::table('system_menu')->insert([
            'id'       => "12",
            'category_name' => 'Settings',
            'parent_id'       => "9",
            'menu_name'       => "Model Management",
            'file_path'       => "_model-units-create.php",
            'status'       => "1",
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        DB::table('system_menu')->insert([
            'id'       => "13",
            'category_name' => 'Settings',
            'parent_id'       => "9",
            'menu_name'       => "Model Color Management",
            'file_path'       => "_color-create.php",
            'status'       => "1",
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        DB::table('system_menu')->insert([
            'id'       => "14",
            'category_name' => 'Settings',
            'parent_id'       => "9",
            'menu_name'       => "Parts Management",
            'file_path'       => "_model-parts-create.php",
            'status'       => "1",
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        DB::table('system_menu')->insert([
            'id'       => "15",
            'category_name' => 'Settings',
            'parent_id'       => "9",
            'menu_name'       => "User Management",
            'file_path'       => "_user-create.php",
            'status'       => "1",
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        DB::table('system_menu')->insert([
            'id'       => "16",
            'category_name' => 'Settings',
            'parent_id'       => "9",
            'menu_name'       => "User Role Management",
            'file_path'       => "_user-role.php",
            'status'       => "1",
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        DB::table('system_menu')->insert([
            'id'       => "17",
            'category_name' => 'Settings',
            'parent_id'       => "9",
            'menu_name'       => "System Menu Management",
            'file_path'       => "_system-menu.php",
            'status'       => "1",
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        DB::table('system_menu')->insert([
            'id'       => "18",
            'category_name' => 'Settings',
            'parent_id'       => "9",
            'menu_name'       => "Aging Mapping",
            'file_path'       => "_aging-map.php",
            'status'       => "1",
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        DB::table('system_menu')->insert([
            'id'       => "19",
            'category_name' => 'Pages',
            'parent_id'       => "0",
            'menu_name'       => "Sales Tagging",
            'file_path'       => "_sales-tagging.php",
            'status'       => "1",
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        DB::table('system_menu')->insert([
            'id'       => "21",
            'category_name' => 'Settings',
            'parent_id'       => "9",
            'menu_name'       => "Document Type Management",
            'file_path'       => "_sales-tagging.php",
            'status'       => "1",
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        DB::table('system_menu')->insert([
            'id'       => "22",
            'category_name' => 'Pages',
            'parent_id'       => "0",
            'menu_name'       => "Receive Stock Transfer",
            'file_path'       => "_stock_transfer_received.php",
            'status'       => "1",
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        DB::table('system_menu')->insert([
            'id'       => "23",
            'category_name' => 'Pages',
            'parent_id'       => "0",
            'menu_name'       => "Request Repo Refurbish",
            'file_path'       => "_refurbish-unit.php",
            'status'       => "1",
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        DB::table('system_menu')->insert([
            'id'       => "24",
            'category_name' => 'Pages',
            'parent_id'       => "0",
            'menu_name'       => "Request Repo Refurbish",
            'file_path'       => "_report_transfers_units.php",
            'status'       => "1",
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
      

        DB::unprepared('SET IDENTITY_INSERT system_menu OFF');
        DB::commit();
    
    }
}
