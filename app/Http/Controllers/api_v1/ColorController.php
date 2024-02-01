<?php

namespace App\Http\Controllers\api_v1;

use Illuminate\Http\Request;
use App\Http\Controllers\api_v1\BaseController as BaseController;
use Illuminate\Support\Facades\Auth;
use App\Models\unit_color;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;


class ColorController extends BaseController
{
    //

    public function createColor(Request $request)
    {

        try {

            $validator = Validator::make($request->all(), [
                'code' => 'required',
                'name' => 'required',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors());
            }

            $check = unit_color::where('name', $request->name)->where('code', $request->code)->count();

            if ($check > 0) {
                return $this->sendError('Validation Error.', 'Color already added!');
            }

            $color = unit_color::create($request->all());
            return $this->sendResponse([], 'Color added successfully.');
        } catch (\Throwable $th) {
            return $this->sendError($th->errorInfo[2]);
        }
    }

    public function colors()
    {

        try {

            return unit_color::all();
        } catch (\Throwable $th) {
            return $this->sendError($th->errorInfo[2]);
        }
    }

    public function updateColor(Request $request, $id)
    {

        try {

            $validator = Validator::make($request->all(), [
                'code' => 'required',
                'name' => 'required',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors());
            }

            $color = unit_color::where('id', $id)->update($request->all());
            return $this->sendResponse([], 'Color updated successfully.');
        } catch (\Throwable $th) {
            return $this->sendError($th->errorInfo[2]);
        }
    }
}
