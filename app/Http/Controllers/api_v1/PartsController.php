<?php

namespace App\Http\Controllers\api_v1;

use Illuminate\Http\Request;
use App\Http\Controllers\api_v1\BaseController as BaseController;
use Illuminate\Support\Facades\Auth;
use App\Models\spare_parts;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class PartsController extends BaseController
{
    //

    public function createParts(Request $request)
    {

        try {

            $validator = Validator::make($request->all(), [
                //  'model_id' => 'required',
                'name' => 'required',
                'price' => 'required',
                //'inventory_code' => 'required',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors());
            }

            $check = spare_parts::where('name', $request->name)->count();



            if ($check > 0) {
                return $this->sendError('Validation Error.', 'Spare Parts already added!');
            }

            $brand = spare_parts::create($request->all());

            return $this->sendResponse([], 'Spare Parts added successfully.');
        } catch (\Throwable $th) {
            return $this->sendError($th->errorInfo[2]);
        }
    }

    public function parts()
    {

        try {

            $query = DB::table('spare_parts as a')
                // ->join('unit_models as b','b.id','a.model_id')
                ->select('a.*')
                ->get();
            return $query;
        } catch (\Throwable $th) {
            return $this->sendError($th->errorInfo[2]);
        }
    }

    public function deactivateParts($id, $status)
    {

        try {

            $branch = spare_parts::where('id', $id)->update(['status' => $status]);

            return $this->sendResponse([], 'Parts deactivate successfully.');
        } catch (\Throwable $th) {
            return $this->sendError($th->errorInfo[2]);
        }
    }

    public function updateParts(Request $request, $id)
    {

        try {

            $validator = Validator::make($request->all(), [
                // 'model_id' => 'required',
                'name' => 'required',
                'price' => 'required',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors());
            }

            $brand = spare_parts::where('id', $id)->update($request->all());
            return $this->sendResponse([], 'Spare Parts updated successfully.');
        } catch (\Throwable $th) {
            return $this->sendError($th->errorInfo[2]);
        }
    }

    public function partsPerModel()
    {
        try {
            // Retrieve data from the cache
            $cachedData = Cache::get('parts_per_model');

            // Check if cache exists and the count matches the current database count
            if ($cachedData && count($cachedData) === DB::table('spare_parts')->where('status', '=', 'A')->where('name', '!=', '')->count()) {
                return $cachedData;
            }

            // If cache is empty or counts don't match, update the cache
            $newData = DB::table('spare_parts')
                ->selectRaw("
                    id as value,
                    TRIM(name) as label
                ")
                ->where('status', '=', 'A')
                ->where('name', '!=', '')
                ->orderBy('name', 'ASC')
                ->get();

            // Cache the new data for 14400 minutes
            Cache::put('parts_per_model', $newData, 14400);

            return $newData;
        } catch (\Throwable $th) {
            return $this->sendError($th->errorInfo[2]);
        }
    }

    public function partsPrice($parts_id)
    {

        try {

            return DB::table('spare_parts')
                ->select('price')
                ->where('id', $parts_id)->first();
        } catch (\Throwable $th) {
            return $this->sendError($th->errorInfo[2]);
        }
    }
}
