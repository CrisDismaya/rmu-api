<?php

namespace App\Http\Controllers\api_v1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\api_v1\BaseController as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\user_role;
use App\Models\menu_mapping;

class UserRoleController extends BaseController
{
    //
    public function createUserRole(Request $request)
    {

        try {

            $validator = Validator::make($request->all(), [
                'user_role_name' => 'required'
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors());
            }

            $format = [
                'user_role_name' => $request->user_role_name
            ];

            $check = user_role::where('user_role_name', $request->user_role_name)->count();

            if ($check > 0) {
                return $this->sendError('Validation Error.', 'Color already added!');
            }

            $role = user_role::create($format);

            menu_mapping::create([
                'user_role_id' => $role->id,
                'menu_id' => 1,
                'created_by' => 1,
            ]);

            return $this->sendResponse([], 'Color added successfully.');
        } catch (\Throwable $th) {
            return $this->sendError($th->errorInfo[2]);
        }
    }

    public function userRole()
    {

        try {

            return  DB::table('user_role')
                ->select('id', 'user_role_name', DB::raw("CASE WHEN role_status = '0' THEN 'Inactive' ELSE 'Active' END AS role_status"))
                ->get();
        } catch (\Throwable $th) {
            return $this->sendError($th->errorInfo[2]);
        }
    }

    public function updateUserRole(Request $request, $id)
    {

        try {

            $validator = Validator::make($request->all(), [
                'user_role_name' => 'required',
                'role_status' => 'required',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors());
            }

            $format = [
                'user_role_name' => $request->user_role_name,
                'role_status' => $request->role_status,
            ];

            user_role::where('id', $id)->update($format);
            return $this->sendResponse([], 'User Role updated successfully.');
        } catch (\Throwable $th) {
            return $this->sendError($th->errorInfo[2]);
        }
    }
}
