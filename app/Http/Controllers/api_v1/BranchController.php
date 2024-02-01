<?php

namespace App\Http\Controllers\api_v1;

use Illuminate\Http\Request;
use App\Http\Controllers\api_v1\BaseController as BaseController;
use Illuminate\Support\Facades\Auth;
use App\Models\branch;
use App\Models\location;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class BranchController extends BaseController
{
    //

    public function createBranch(Request $request)
    {

        try {

            $validator = Validator::make($request->all(), [
                'name' => 'required',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors());
            }

            $check = branch::where('name', $request->name)->count();

            if ($check > 0) {
                return $this->sendError('Validation Error.', 'Branch already added!');
            }

            $input = $request->all();
            $input['status'] = '1';

            $branch = branch::create($input);
            return $this->sendResponse([], 'Branch added successfully.');
        } catch (\Throwable $th) {
            return $this->sendError($th->errorInfo[2]);
        }
    }

    public function branches()
    {

        try {

            return branch::all();
            //where('status', '=', '1')
            // ->where('id', '!=', Auth::user()->branch)
            // ->get();

        } catch (\Throwable $th) {
            return $this->sendError($th->errorInfo[2]);
        }
    }

    public function updateBranch(Request $request, $id)
    {

        try {

            $validator = Validator::make($request->all(), [
                'name' => 'required',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors());
            }

            $branch = branch::where('id', $id)->update($request->all());
            return $this->sendResponse([], 'Branch updated successfully.');
        } catch (\Throwable $th) {
            return $this->sendError($th->errorInfo[2]);
        }
    }

    public function deactivateBranch($id, $status)
    {

        try {

            $branch = branch::where('id', $id)->update(['status' => $status]);

            return $this->sendResponse([], 'Branch deactivate successfully.');
        } catch (\Throwable $th) {
            return $this->sendError($th->errorInfo[2]);
        }
    }

    public function locationList()
    {
        return location::all();
    }

    public function createLocation(Request $request)
    {

        try {

            $validator = Validator::make($request->all(), [
                'name' => 'required',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors());
            }

            $check = location::where('name', $request->name)->count();

            if ($check > 0) {
                return $this->sendError('Validation Error.', 'Branch already added!');
            }

            $input = $request->all();
            $input['status'] = '1';

            $location = location::create($input);
            return $this->sendResponse([], 'Location added successfully.');
        } catch (\Throwable $th) {
            return $this->sendError($th->errorInfo[2]);
        }
    }

    public function updateLocation(Request $request, $id)
    {

        try {

            $validator = Validator::make($request->all(), [
                'name' => 'required',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors());
            }

            $branch = location::where('id', $id)->update($request->all());
            return $this->sendResponse([], 'Location updated successfully.');
        } catch (\Throwable $th) {
            return $this->sendError($th->errorInfo[2]);
        }
    }

    public function deactivateLocation($id, $status)
    {

        try {

            $branch = location::where('id', $id)->update(['status' => $status]);

            return $this->sendResponse([], 'Location deactivate successfully.');
        } catch (\Throwable $th) {
            return $this->sendError($th->errorInfo[2]);
        }
    }
}
