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

        $system_menu = [
            [ "id" => 1, "category_name" => "Dashboard", "parent_id" => 0, "menu_name" => "Dashboard", "file_path" => "dashboard.php", "status" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 2, "category_name" => "Pages", "parent_id" => 0, "menu_name" => "Customer Profiling", "file_path" => "_customer-profiling.php", "status" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 3, "category_name" => "Pages", "parent_id" => 28, "menu_name" => "Repo Details", "file_path" => "_repo-create.php", "status" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 4, "category_name" => "Pages", "parent_id" => 0, "menu_name" => "Receive of Units", "file_path" => "_receiving-of-units.php", "status" => 0, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 5, "category_name" => "Pages", "parent_id" => 29, "menu_name" => "Stock Transfer", "file_path" => "_stock_transfer.php", "status" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 6, "category_name" => "Pages", "parent_id" => 30, "menu_name" => "Request Price Appraisal", "file_path" => "_approval-unit.php", "status" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 7, "category_name" => "Pages", "parent_id" => 0, "menu_name" => "Inventory", "file_path" => "inventory.php", "status" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 8, "category_name" => "Pages", "parent_id" => 31, "menu_name" => "Sold Units", "file_path" => "sold-unit.php", "status" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 9, "category_name" => "Pages", "parent_id" => 0, "menu_name" => "Maintenance", "file_path" => null, "status" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 10, "category_name" => "Settings", "parent_id" => 9, "menu_name" => "Branch Management", "file_path" => "_branch-create.php", "status" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 11, "category_name" => "Settings", "parent_id" => 9, "menu_name" => "Brand Management", "file_path" => "_brand-create.php", "status" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 12, "category_name" => "Settings", "parent_id" => 9, "menu_name" => "Model Management", "file_path" => "_model-units-create.php", "status" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 13, "category_name" => "Settings", "parent_id" => 9, "menu_name" => "Model Color Management", "file_path" => "_color-create.php", "status" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 14, "category_name" => "Settings", "parent_id" => 9, "menu_name" => "Parts Management", "file_path" => "_model-parts-create.php", "status" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 15, "category_name" => "Settings", "parent_id" => 9, "menu_name" => "User Management", "file_path" => "_user-create.php", "status" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 16, "category_name" => "Settings", "parent_id" => 9, "menu_name" => "User Role Management", "file_path" => "_user-role.php", "status" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 17, "category_name" => "Settings", "parent_id" => 9, "menu_name" => "System Menu Management", "file_path" => "_system-menu.php", "status" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 18, "category_name" => "Settings", "parent_id" => 9, "menu_name" => "Aging Mapping", "file_path" => "_aging-map.php", "status" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 19, "category_name" => "Pages", "parent_id" => 29, "menu_name" => "Sales Tagging", "file_path" => "_sales-tagging.php", "status" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 21, "category_name" => "Settings", "parent_id" => 9, "menu_name" => "Document Type Management", "file_path" => "_files-upload-maintenance.php", "status" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 22, "category_name" => "Pages", "parent_id" => 28, "menu_name" => "Receive Stock Transfer", "file_path" => "_stock_transfer_received.php", "status" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 23, "category_name" => "Pages", "parent_id" => 30, "menu_name" => "Request Refurbishment", "file_path" => "_refurbish-unit.php", "status" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 24, "category_name" => "Pages", "parent_id" => 31, "menu_name" => "Transferred Units", "file_path" => "_report_transfers_units.php", "status" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 25, "category_name" => "Pages", "parent_id" => 32, "menu_name" => "Repo Tagging", "file_path" => "repo_tagging_approval.php", "status" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 26, "category_name" => "Pages", "parent_id" => 30, "menu_name" => "Settle Refurbishment", "file_path" => "_refurbish-process.php", "status" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 28, "category_name" => "Pages", "parent_id" => 0, "menu_name" => "Direct-Receipt", "file_path" => "", "status" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 29, "category_name" => "Pages", "parent_id" => 0, "menu_name" => "Direct-Issue", "file_path" => "", "status" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 30, "category_name" => "Pages", "parent_id" => 0, "menu_name" => "Ropa-Maintenance", "file_path" => "", "status" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 31, "category_name" => "Report", "parent_id" => 0, "menu_name" => "Reports", "file_path" => "", "status" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 32, "category_name" => "Pages", "parent_id" => 0, "menu_name" => "Tagging", "file_path" => "", "status" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 33, "category_name" => "Report", "parent_id" => 31, "menu_name" => "Appraisal Records", "file_path" => "appraisal-history.php", "status" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 34, "category_name" => "Settings", "parent_id" => 9, "menu_name" => "Location Mapping", "file_path" => "_location-mapping.php", "status" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 35, "category_name" => "Report", "parent_id" => 31, "menu_name" => "Refurbish Units", "file_path" => "refurbish-list.php", "status" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 36, "category_name" => "Report", "parent_id" => 31, "menu_name" => "Appraised Units", "file_path" => "appraised-unit.php", "status" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 37, "category_name" => "Report", "parent_id" => 31, "menu_name" => "Refurbish Unit (Accounting)", "file_path" => "refurbishment-unit-accounting.php", "status" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ]
        ];

        $user_role_menu_mapping = [
            [ "id" => 1, "user_role_id" => 1, "menu_id" => 2, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 2, "user_role_id" => 1, "menu_id" => 3, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 3, "user_role_id" => 1, "menu_id" => 6, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 4, "user_role_id" => 1, "menu_id" => 23, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 5, "user_role_id" => 1, "menu_id" => 5, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 6, "user_role_id" => 1, "menu_id" => 22, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 7, "user_role_id" => 1, "menu_id" => 7, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 8, "user_role_id" => 1, "menu_id" => 8, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 9, "user_role_id" => 1, "menu_id" => 19, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 10, "user_role_id" => 1, "menu_id" => 26, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 11, "user_role_id" => 1, "menu_id" => 28, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 12, "user_role_id" => 1, "menu_id" => 29, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 13, "user_role_id" => 1, "menu_id" => 30, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 14, "user_role_id" => 1, "menu_id" => 31, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 15, "user_role_id" => 1, "menu_id" => 36, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 16, "user_role_id" => 1, "menu_id" => 35, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 17, "user_role_id" => 2, "menu_id" => 1, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 18, "user_role_id" => 2, "menu_id" => 2, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 19, "user_role_id" => 2, "menu_id" => 3, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 20, "user_role_id" => 2, "menu_id" => 4, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 21, "user_role_id" => 2, "menu_id" => 5, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 22, "user_role_id" => 2, "menu_id" => 22, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 23, "user_role_id" => 2, "menu_id" => 7, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 24, "user_role_id" => 2, "menu_id" => 6, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 25, "user_role_id" => 2, "menu_id" => 23, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 26, "user_role_id" => 2, "menu_id" => 19, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 27, "user_role_id" => 2, "menu_id" => 8, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 28, "user_role_id" => 2, "menu_id" => 25, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 29, "user_role_id" => 2, "menu_id" => 26, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 30, "user_role_id" => 2, "menu_id" => 28, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 31, "user_role_id" => 2, "menu_id" => 29, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 32, "user_role_id" => 2, "menu_id" => 30, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 33, "user_role_id" => 2, "menu_id" => 31, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 34, "user_role_id" => 2, "menu_id" => 24, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 35, "user_role_id" => 2, "menu_id" => 32, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 36, "user_role_id" => 2, "menu_id" => 33, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 37, "user_role_id" => 2, "menu_id" => 34, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 38, "user_role_id" => 2, "menu_id" => 35, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 39, "user_role_id" => 2, "menu_id" => 36, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 40, "user_role_id" => 2, "menu_id" => 37, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 41, "user_role_id" => 3, "menu_id" => 5, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 42, "user_role_id" => 3, "menu_id" => 6, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 43, "user_role_id" => 3, "menu_id" => 7, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 44, "user_role_id" => 3, "menu_id" => 8, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 45, "user_role_id" => 3, "menu_id" => 23, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 46, "user_role_id" => 3, "menu_id" => 24, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 47, "user_role_id" => 3, "menu_id" => 4, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 48, "user_role_id" => 3, "menu_id" => 19, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 49, "user_role_id" => 3, "menu_id" => 25, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 50, "user_role_id" => 3, "menu_id" => 26, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 51, "user_role_id" => 3, "menu_id" => 28, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 52, "user_role_id" => 3, "menu_id" => 29, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 53, "user_role_id" => 3, "menu_id" => 30, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 54, "user_role_id" => 3, "menu_id" => 31, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 55, "user_role_id" => 3, "menu_id" => 33, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 56, "user_role_id" => 3, "menu_id" => 35, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 57, "user_role_id" => 3, "menu_id" => 36, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 58, "user_role_id" => 4, "menu_id" => 1, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 59, "user_role_id" => 4, "menu_id" => 2, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 60, "user_role_id" => 4, "menu_id" => 3, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 61, "user_role_id" => 4, "menu_id" => 4, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 62, "user_role_id" => 4, "menu_id" => 5, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 63, "user_role_id" => 4, "menu_id" => 6, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 64, "user_role_id" => 4, "menu_id" => 7, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 65, "user_role_id" => 4, "menu_id" => 8, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 66, "user_role_id" => 4, "menu_id" => 19, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 67, "user_role_id" => 4, "menu_id" => 22, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 68, "user_role_id" => 4, "menu_id" => 23, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 69, "user_role_id" => 4, "menu_id" => 24, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 70, "user_role_id" => 4, "menu_id" => 25, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 71, "user_role_id" => 4, "menu_id" => 26, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 72, "user_role_id" => 4, "menu_id" => 28, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 73, "user_role_id" => 4, "menu_id" => 29, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 74, "user_role_id" => 4, "menu_id" => 30, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 75, "user_role_id" => 4, "menu_id" => 31, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 76, "user_role_id" => 4, "menu_id" => 32, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 77, "user_role_id" => 4, "menu_id" => 9, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 78, "user_role_id" => 4, "menu_id" => 10, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 79, "user_role_id" => 4, "menu_id" => 11, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 80, "user_role_id" => 4, "menu_id" => 12, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 81, "user_role_id" => 4, "menu_id" => 13, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 82, "user_role_id" => 4, "menu_id" => 14, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 83, "user_role_id" => 4, "menu_id" => 15, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 84, "user_role_id" => 4, "menu_id" => 16, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 85, "user_role_id" => 4, "menu_id" => 17, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 86, "user_role_id" => 4, "menu_id" => 18, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 87, "user_role_id" => 4, "menu_id" => 21, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 88, "user_role_id" => 4, "menu_id" => 34, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 89, "user_role_id" => 4, "menu_id" => 33, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 90, "user_role_id" => 4, "menu_id" => 35, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ],
            [ "id" => 91, "user_role_id" => 4, "menu_id" => 36, "created_by" => 1, "created_at" => Carbon::now(), "updated_at" => Carbon::now() ]
        ];

        DB::beginTransaction();

            DB::unprepared('SET IDENTITY_INSERT system_menu ON');
                collect($system_menu)->chunk(10)->each(function ($chunk) {
                    DB::table('system_menu')->insert($chunk->toArray());
                });
            DB::unprepared('SET IDENTITY_INSERT system_menu OFF');

            DB::unprepared('SET IDENTITY_INSERT user_role_menu_mapping ON');
                collect($user_role_menu_mapping)->chunk(10)->each(function ($chunk) {
                    DB::table('user_role_menu_mapping')->insert($chunk->toArray());
                });
            DB::unprepared('SET IDENTITY_INSERT user_role_menu_mapping OFF');

        DB::commit();
    }
}
