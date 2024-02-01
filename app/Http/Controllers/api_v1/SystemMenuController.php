<?php

namespace App\Http\Controllers\api_v1;

use App\Http\Controllers\api_v1\BaseController as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\system_menu;
use App\Models\menu_mapping;

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

			return DB::table('system_menu as sm')
				->select(
					'sm.id',
					'sm.category_name',
					'sm.parent_id',
					DB::raw("(SELECT  pn.menu_name FROM system_menu pn WHERE pn.id = sm.parent_id) AS parent_name"),
					'sm.menu_name',
					'sm.file_path',
					DB::raw("CASE WHEN sm.status = '1' THEN 'Active' ELSE 'Inactive' END AS menu_status"),
					DB::raw("CASE WHEN rm.menu_id = sm.id THEN 'true' ELSE 'false' END AS isCheck"),
					'rm.id AS map_id'
				)
				->leftJoin('user_role_menu_mapping as rm', function ($join) use ($id) {
					$join->on('sm.id', '=', 'rm.menu_id')
						->where('rm.user_role_id', '=', $id);
				})
				->get();
		} catch (\Throwable $th) {
			return $this->sendError($th->errorInfo[2]);
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

			$format = [
				'user_role_id' => $request->user_role_id,
				'menu_id' => $request->menu_id,
				'created_by' => Auth::user()->id
			];

			if ($request->map_id > 0) {
				menu_mapping::find($request->map_id)->delete();
				return $this->sendResponse([], 'Remove Successfully.');
			} else {
				menu_mapping::create($format);
				return $this->sendResponse([], 'Added Successfully.');
			}
		} catch (\Throwable $th) {
			return $this->sendError($th->errorInfo[2]);
		}
	}
}
