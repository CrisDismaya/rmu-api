<?php

namespace App\Http\Controllers\api_v1;

use Illuminate\Http\Request;
use App\Http\Controllers\api_v1\BaseController as BaseController;
use Illuminate\Support\Facades\Auth;
use App\Models\branch;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class BranchController extends BaseController
{
    //

    public function createBranch(Request $request){
        $validator = Validator::make($request->all(), [
            'name' => 'required',
        ]);
   
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }

        $check = branch::where('name',$request->name)->count();

        if($check > 0){
            return $this->sendError('Validation Error.', 'Branch already added!');
        }

        $input = $request->all();
        $input['status'] = '1';

        $branch = branch::create($input);
        return $this->sendResponse([], 'Branch added successfully.');
    }

    public function branches(){
        return branch::where('status', '=', '1')
           // ->where('id', '!=', Auth::user()->branch)
            ->get();
    }

    public function updateBranch(Request $request, $id){
        $validator = Validator::make($request->all(), [
            'name' => 'required',
        ]);
   
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }

        $branch = branch::where('id',$id)->update($request->all());
        return $this->sendResponse([], 'Branch updated successfully.');
    }

    public function deactivateBranch($id,$status){
        $branch = branch::where('id',$id)->update(['status' => $status]);

        return $this->sendResponse([], 'Branch deactivate successfully.');
    }
}
