<?php

namespace App\Http\Controllers\api_v1;

use App\Http\Controllers\api_v1\BaseController as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\system_menu;
use App\Models\menu_mapping;
use App\Models\UserPermission;
use Illuminate\Support\Facades\Log;
use App\Enums\ApprovableModule;

class SystemMenuController extends BaseController
{
	//
	public function menu()
	{

		try {
			return DB::table('system_menu')
				->select('id', 'menu_name')
				->orderBy('menu_name')
				->get();
		} catch (\Throwable $th) {
			return $this->sendError($th->errorInfo[2]);
		}
	}

	public function menuList($id)
    {
        try {
            $approvableIds = ApprovableModule::values();
            $approvableIdsString = $approvableIds ? implode(',', $approvableIds) : 'NULL';

            return DB::table('system_menu as sm')
                ->select(
                    'sm.id',
                    'sm.category_name',
                    'sm.parent_id',
                    DB::raw("(SELECT pn.menu_name FROM system_menu pn WHERE pn.id = sm.parent_id) AS parent_name"),
                    'sm.menu_name',
                    'sm.file_path',
                    DB::raw("CASE WHEN sm.status = '1' THEN 'Active' ELSE 'Inactive' END AS menu_status"),
                    DB::raw("CASE WHEN rm.menu_id = sm.id THEN 'true' ELSE 'false' END AS isCheck"),
                    'rm.id AS map_id',
                    DB::raw("CASE WHEN sm.id IN ($approvableIdsString) THEN 'true' ELSE 'false' END AS is_approvable")
                )
                ->leftJoin('user_role_menu_mapping as rm', function ($join) use ($id) {
                    $join->on('sm.id', '=', 'rm.menu_id')
                        ->where('rm.user_role_id', '=', $id);
                })
                ->get();

        } catch (\Throwable $th) {
            return $this->sendError($th->getMessage());
        }
    }

	public function createSystemMenu(Request $request)
	{

		try {

			$validator = Validator::make($request->all(), [
				'category_menu' => 'required',
				'parent_menu' => 'required',
				'menu_name' => 'required',
				'menu_file_path' => 'nullable',
			]);

			if ($validator->fails()) {
				return $this->sendError('Validation Error.', $validator->errors());
			}

			$format = [
				'category_name' => $request->category_menu,
				'parent_id' => $request->parent_menu,
				'menu_name' => $request->menu_name,
				'file_path' => $request->menu_file_path,
			];

			system_menu::create($format);
			return $this->sendResponse([], 'User Access added successfully.');
		} catch (\Throwable $th) {
			return $this->sendError($th->errorInfo[2]);
		}
	}

	public function updateSystemMenu(Request $request, $id)
	{
		try {

			$validator = Validator::make($request->all(), [
				'category_menu' => 'required',
				'parent_menu' => 'required',
				'menu_name' => 'required',
				'menu_file_path' => 'nullable',
				'menu_status' => 'nullable',
			]);

			if ($validator->fails()) {
				return $this->sendError('Validation Error.', $validator->errors());
			}

			$format = [
				'category_name' => $request->category_menu,
				'parent_id' => $request->parent_menu,
				'menu_name' => $request->menu_name,
				'file_path' => $request->menu_file_path,
				'status' => $request->menu_status,
			];

			system_menu::where('id', $id)->update($format);
			return $this->sendResponse([], 'User Access added successfully.');
		} catch (\Throwable $th) {
			return $this->sendError($th->errorInfo[2]);
		}
	}

	public function createMenuMapping(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_role_id' => 'required',
                'menu_id' => 'required',
                'map_id' => 'required',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors());
            }

            $userRoleId = $request->user_role_id;
            $menuId = $request->menu_id;
            $mapId = $request->map_id;

            if ($mapId > 0) {
                // Get the menu being removed
                $menu = system_menu::find($menuId);
                if (!$menu) {
                    return $this->sendError('Error', 'Menu not found.');
                }

                // Delete the child menu mapping
                menu_mapping::find($mapId)?->delete();

                // If it has a parent, check if other children still exist
                if ($menu->parent_id != 0) {
                    $remainingChildren = system_menu::where('parent_id', $menu->parent_id)->pluck('id')->toArray();

                    $childMapped = menu_mapping::where('user_role_id', $userRoleId)
                        ->whereIn('menu_id', $remainingChildren)
                        ->exists();

                    if (!$childMapped) {
                        // If no children are mapped, remove the parent mapping
                        menu_mapping::where('user_role_id', $userRoleId)
                            ->where('menu_id', $menu->parent_id)
                            ->delete();
                    }
                }

                return $this->sendResponse([], 'Removed successfully.');
            }

            $menu = system_menu::find($menuId);
            if (!$menu) {
                return $this->sendError('Error', 'Menu not found.');
            }

            // Check and insert parent menu if it's a child
            if ($menu->parent_id != 0) {
                $parentMenuId = $menu->parent_id;
                $parentMapped = menu_mapping::where('user_role_id', $userRoleId)
                    ->where('menu_id', $parentMenuId)
                    ->exists();

                if (!$parentMapped) {
                    menu_mapping::create([
                        'user_role_id' => $userRoleId,
                        'menu_id' => $parentMenuId,
                        'created_by' => Auth::id(),
                    ]);
                }
            }

            // Avoid duplicate mapping
            $alreadyMapped = menu_mapping::where('user_role_id', $userRoleId)
                ->where('menu_id', $menuId)
                ->exists();

            if (!$alreadyMapped) {
                menu_mapping::create([
                    'user_role_id' => $userRoleId,
                    'menu_id' => $menuId,
                    'created_by' => Auth::id(),
                ]);
            }

            return $this->sendResponse([], 'Added successfully.');
        } catch (\Throwable $th) {
            return $this->sendError($th->getMessage());
        }
    }

    public function getUserMenus($userId, $roleId)
    {
        try {
            Log::debug('[getUserMenus] Start', [
                'userId' => $userId,
                'roleId' => $roleId,
            ]);

            $user = $userId ? User::find($userId) : null;
            Log::debug('User fetched', ['user' => $user]);

            // Base query
            $query = system_menu::query()
                ->from('system_menu as menu')
                ->join('user_role_menu_mapping as map', 'menu.id', '=', 'map.menu_id')
                ->join('user_role as role', 'role.id', '=', 'map.user_role_id')
                ->where('menu.status', 1);

            Log::debug('Base query initialized');

            // Columns (default)
            $columns = [
                'menu.id',
                'menu.category_name',
                'menu.menu_name',
                'menu.file_path',
                'menu.parent_id',
                'menu.sort',
            ];

            // When user has ID (for permissions)
            $hasPermissions = $userId != 0;
            Log::debug('Has Permissions', ['hasPermissions' => $hasPermissions]);

            if ($hasPermissions) {
                $columns = array_merge($columns, [
                    DB::raw('COALESCE(perm.view_permission, 0) as view_permission'),
                    DB::raw('COALESCE(perm.add_permission, 0) as add_permission'),
                    DB::raw('COALESCE(perm.update_permission, 0) as update_permission'),
                    DB::raw('COALESCE(perm.approval_permission, 0) as approval_permission'),
                ]);
            }

            $query->select($columns);
            Log::debug('Columns set', ['columns' => $columns]);

            // Join `users` and `user_module_permissions` only when userId != 0
            if ($hasPermissions) {
                $query->join('users as usr', 'usr.userrole', '=', 'role.user_role_name')
                    ->leftJoin('user_module_permissions as perm', function ($join) {
                        $join->on('usr.id', '=', 'perm.user_id')
                            ->on('role.id', '=', 'perm.role_id')
                            ->on('menu.id', '=', 'perm.menu_id');
                    })
                    ->where('usr.id', $userId);

                Log::debug('User and permission joins applied');
            }

            // Role filtering
            if (is_numeric($roleId)) {
                $query->where('role.id', $roleId);
                Log::debug('Role filter by ID', ['roleId' => $roleId]);
            } else {
                $query->where('role.user_role_name', $roleId);
                Log::debug('Role filter by name', ['roleName' => $roleId]);
            }

            // Edge case: userId != 0 but user's role doesn't match provided role name
            if (
                $userId != 0 &&
                !is_numeric($roleId) &&
                $user &&
                $user->userrole !== $roleId
            ) {
                Log::debug('Edge case: user role mismatch', [
                    'userRole' => $user->userrole,
                    'providedRole' => $roleId,
                ]);

                $columns = [
                    'menu.id',
                    'menu.category_name',
                    'menu.menu_name',
                    'menu.file_path',
                    'menu.parent_id',
                    'menu.sort',
                    DB::raw('0 as view_permission'),
                    DB::raw('0 as add_permission'),
                    DB::raw('0 as update_permission'),
                    DB::raw('0 as approval_permission'),
                ];

                $query = system_menu::query()
                    ->select($columns)
                    ->from('system_menu as menu')
                    ->join('user_role_menu_mapping as map', 'menu.id', '=', 'map.menu_id')
                    ->join('user_role as role', 'role.id', '=', 'map.user_role_id')
                    ->where('menu.status', 1)
                    ->where('role.user_role_name', $roleId);

                Log::debug('Rebuilt query for mismatch role');
            }

            // Sort
            $query->orderBy('menu.sort');
            Log::debug('Sorting applied');

            // SQL Logging
            Log::info('🧾 [getUserMenus] Generated SQL', [
                'sql' => $query->toSql(),
                'bindings' => $query->getBindings(),
            ]);

            $result = $query->get();
            Log::debug('✅ Query executed successfully', ['count' => $result->count()]);

            return $result;

        } catch (\Throwable $th) {
            Log::error('[getUserMenus] Error fetching user menus', [
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
                'userId' => $userId,
                'roleId' => $roleId,
            ]);
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    public function saveUserAccess(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
                'role_id' => 'required|exists:user_role,id',
                'menus'   => 'required|array|min:1',
                'menus.*.menu_id' => 'required|integer|exists:system_menu,id',
                'menus.*.add_permission' => 'nullable|boolean',
                'menus.*.update_permission' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors());
            }

            DB::beginTransaction();

            $userId = $request->user_id;
            $roleId = $request->role_id;
            $menus = $request->menus;

            foreach ($menus as $menu) {
                $existing = UserPermission::where('user_id', $userId)
                    ->where('role_id', $roleId)
                    ->where('menu_id', $menu['menu_id'])
                    ->first();

                $data = [
                    'view_permission'     => 1,
                    'add_permission'      => $menu['add_permission'] ?? 0,
                    'update_permission'   => $menu['update_permission'] ?? 0,
                    'approval_permission' => 0,
                ];

                if ($existing) {
                    $existing->update($data);
                } else {
                    UserPermission::create(array_merge($data, [
                        'user_id' => $userId,
                        'role_id' => $roleId,
                        'menu_id' => $menu['menu_id'],
                    ]));
                }
            }

            DB::commit();

            return $this->sendResponse([], 'User access permissions saved successfully.');
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->sendError($th->getMessage());
        }
    }
}
