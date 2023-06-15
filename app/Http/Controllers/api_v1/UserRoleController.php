<?php

namespace App\Http\Controllers\api_v1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\api_v1\BaseController as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\user_role;

class UserRoleController extends BaseController
{
    //
    public function createUserRole(Request $request){
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

        if($check > 0){
            return $this->sendError('Validation Error.', 'Color already added!');
        }

		user_role::create($format);
        return $this->sendResponse([], 'Color added successfully.');
	}

    public function userRole(){
        return  DB::table('user_role')
        ->select('id', 'user_role_name', DB::raw("CASE WHEN role_status = '0' THEN 'Inactive' ELSE 'Active' END AS role_status"))
        ->get();
    }

    public function updateUserRole(Request $request, $id){
        $validator = Validator::make($request->all(), [
            'user_role_name' => 'required',
            'role_status' => 'required',
        ]);
   
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }

        $format = [
            'user_role_name' => $request->user_role_name,
            'role_status' => $request->role_status,
        ];

        user_role::where('id', $id)->update($format);
        return $this->sendResponse([], 'User Role updated successfully.');
    }
}
