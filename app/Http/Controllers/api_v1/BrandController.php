<?php

namespace App\Http\Controllers\api_v1;

use Illuminate\Http\Request;
use App\Http\Controllers\api_v1\BaseController as BaseController;
use Illuminate\Support\Facades\Auth;
use App\Models\brand;
use Validator;


class BrandController extends BaseController
{
    //

    public function createBrand(Request $request)
    {

        try {

            $validator = Validator::make($request->all(), [
                'code' => 'required',
                'brandname' => 'required',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors());
            }

            $check = brand::where('brandname', $request->brandname)->count();



            if ($check > 0) {
                return $this->sendError('Validation Error.', 'Brand already added!');
            }

            $brand = brand::create($request->all());

            return $this->sendResponse([], 'Brand added successfully.');
        } catch (\Throwable $th) {
            return $this->sendError($th->errorInfo[2]);
        }
    }

    public function brands()
    {

        try {

            return brand::all();
        } catch (\Throwable $th) {
            return $this->sendError($th->errorInfo[2]);
        }
    }

    public function updateBrand(Request $request, $id)
    {

        try {

            $validator = Validator::make($request->all(), [
                'code' => 'required',
                'brandname' => 'required',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors());
            }

            $brand = brand::where('id', $id)->update($request->all());
            return $this->sendResponse([], 'Brand updated successfully.');
        } catch (\Throwable $th) {
            return $this->sendError($th->errorInfo[2]);
        }
    }
}
