<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\system_menu AS SystemMenu;
use App\Models\UserPermission;

class UserModulePermission extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $command = $this->command;

        $command?->info('UserMenuSeeder started.');
        Log::info('UserMenuSeeder started.');

        // Fetch all active users with their role IDs
        $users = User::query()
            ->select(
                'user.id as user_id',
                'role.id as role_id'
            )
            ->from('users as user')
            ->join('user_role as role', 'role.user_role_name', '=', 'user.userrole')
            ->where('status', 1)
            ->get();

        if ($users->isEmpty()) {
            $command?->warn('No active users found.');
            Log::warning('No active users found.');
            return;
        }

        // Fetch all menus mapped to user roles, excluding specific IDs
        $menus = SystemMenu::query()
            ->select(
                'usr.id as user_id',
                'menu.id as menu_id',
                'menu.category_name',
                'menu.menu_name',
                'menu.file_path',
                'menu.parent_id',
                'menu.sort'
            )
            ->from('system_menu as menu')
            ->join('user_role_menu_mapping as map', 'menu.id', '=', 'map.menu_id')
            ->join('user_role as role', 'role.id', '=', 'map.user_role_id')
            ->join('users as usr', 'usr.userrole', '=', 'role.user_role_name')
            ->where('menu.status', 1)
            ->whereNotIn('menu.id', [9, 28, 29, 30, 31, 32])
            ->orderBy('menu.sort')
            ->orderBy('menu.parent_id')
            ->get()
            ->groupBy('user_id'); // Group menus per user


        if ($menus->isEmpty()) {
            $command?->warn('No menus mapped to any user.');
            Log::warning('No menus mapped to any user.');
            return;
        }

        $insertData = [];

        // Loop through users and assign menu permissions
        foreach ($users as $user) {
            $fullName = trim("{$user->firstname} {$user->middlename} {$user->lastname}") ?: "User ID {$user->user_id}";
            $mappedMenus = $menus->get($user->user_id, collect());

            if ($mappedMenus->isEmpty()) {
                $command?->warn("No menus mapped for user: {$fullName} (ID: {$user->user_id})");
                Log::info("No menus mapped for user: {$fullName} (ID: {$user->user_id})");
                continue;
            }

            foreach ($mappedMenus as $menu) {
                $insertData[] = [
                    'user_id'               => $user->user_id,
                    'role_id'               => $user->role_id,
                    'menu_id'               => $menu->menu_id,
                    'view_permission'       => true,
                    'add_permission'        => true,
                    'update_permission'     => true,
                    'approval_permission'   => in_array($user->role_id, [2, 3]),
                    'created_at'            => now(),
                    'updated_at'            => now(),
                ];
            }

            $command?->info("Prepared permissions for user: {$fullName} (ID: {$user->user_id})");
        }

        // Insert data in batch for performance
        if (!empty($insertData)) {
            $chunks = collect($insertData)->chunk(100); // adjust chunk size if needed
            DB::transaction(function () use ($chunks, $command) {
                foreach ($chunks as $chunk) {
                    UserPermission::insert($chunk->toArray());
                }
            });

            $command?->info("User permissions seeded successfully: " . count($insertData) . " records inserted.");
            Log::info('User permissions seeded successfully.', ['count' => count($insertData)]);
        } else {
            $command?->warn('No permissions to insert.');
            Log::warning('No permissions to insert.');
        }

        $command?->info('UserMenuSeeder finished.');
        Log::info('UserMenuSeeder finished.');
    }
}
