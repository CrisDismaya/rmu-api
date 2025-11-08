<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\user_role AS UserRole;
use App\Models\system_menu AS SystemMenu;
use App\Models\menu_mapping AS MenuMapping;

class UpdateSystemMenuUserAccessSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $roles = UserRole::where('role_status', '1')->get();
        $menus = SystemMenu::all()->keyBy('id');
        $menuMappings = MenuMapping::all()->groupBy('user_role_id');

        $this->command->info("🔍 Checking menu mappings for all active roles...\n");

        foreach ($roles as $role) {
            // Ensure menu_id = 1 exists for every role
            $hasMenuOne = MenuMapping::where('user_role_id', $role->id)
                ->where('menu_id', 1)
                ->exists();

            if (!$hasMenuOne) {
                MenuMapping::create([
                    'user_role_id' => $role->id,
                    'menu_id'      => 1,
                    'created_by'   => 1, // Seeder run by admin
                ]);

                $this->command->line("   ✅ Added default root menu (ID: 1) for role '{$role->role_name}' (ID: {$role->id}).");
                Log::info("Auto-added menu_id 1 for role '{$role->role_name}' (ID: {$role->id}).");
            } else {
                $this->command->line("   ⚙️ Menu ID 1 already mapped for role '{$role->role_name}' (skipped).");
            }

            // Continue with existing logic
            $existingMappings = $menuMappings->get($role->id, collect());
            $mappedMenuIds = $existingMappings->pluck('menu_id')->toArray();
            $missingParents = [];

            foreach ($mappedMenuIds as $menuId) {
                $menu = $menus->get($menuId);

                if (!$menu || $menu->parent_id == 0) {
                    continue;
                }

                if (!in_array($menu->parent_id, $mappedMenuIds)) {
                    $parent = $menus->get($menu->parent_id);
                    if ($parent) {
                        $missingParents[] = [
                            'user_role_id' => $role->id,
                            'menu_id'      => $parent->id,
                            'parent_name'  => $parent->menu_name,
                            'child_name'   => $menu->menu_name,
                        ];
                    }
                }
            }

            if (!empty($missingParents)) {
                $this->command->warn("⚠️  Role: {$role->role_name} (ID: {$role->id}) has missing parent menu mappings:");
                foreach ($missingParents as $item) {
                    $this->command->line(
                        "   • Child '{$item['child_name']}' → Missing Parent '{$item['parent_name']}'"
                    );
                }

                $this->command->line(''); // spacing

                // Ask confirmation before inserting
                if ($this->command->confirm("Do you want to insert missing parent menus for role '{$role->role_name}'?", true)) {
                    DB::transaction(function () use ($role, $missingParents) {
                        foreach ($missingParents as $item) {
                            $exists = MenuMapping::where('user_role_id', $role->id)
                                ->where('menu_id', $item['menu_id'])
                                ->exists();

                            if (!$exists) {
                                MenuMapping::create([
                                    'user_role_id' => $role->id,
                                    'menu_id'      => $item['menu_id'],
                                    'created_by'   => 1, // Seeder run by admin
                                ]);

                                $this->command->line(
                                    "   ✅ Added missing parent '{$item['parent_name']}' for child '{$item['child_name']}'."
                                );
                                Log::info("Parent menu '{$item['parent_name']}' auto-added for role '{$role->role_name}' (ID: {$role->id}).");
                            } else {
                                $this->command->line("   ⚙️ Parent '{$item['parent_name']}' already mapped (skipped).");
                            }
                        }
                    });

                    Log::warning("Auto-added missing parent menus for role: {$role->role_name} (ID: {$role->id})", $missingParents);
                } else {
                    $this->command->warn("⏭️  Skipped inserting missing parents for role '{$role->role_name}'.");
                    Log::info("Skipped adding missing parent menus for role '{$role->role_name}' (ID: {$role->id}).");
                }
            } else {
                $this->command->info("✅ Role: {$role->role_name} (ID: {$role->id}) — all parent menus are properly mapped.");
                Log::info("All menu mappings valid for role: {$role->role_name} (ID: {$role->id})");
            }

            $this->command->line(""); // spacing between roles
        }

        $this->command->info("✅ Menu mapping validation and optional fix complete.\n");
    }
}
