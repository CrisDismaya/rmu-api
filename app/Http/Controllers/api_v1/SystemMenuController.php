<?php

namespace App\Http\Controllers\api_v1;

use App\Http\Controllers\api_v1\BaseController as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\system_menu;
use App\Models\menu_mapping;
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
}
