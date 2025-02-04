<?php

namespace App\Http\Controllers\api_v1;

use Illuminate\Http\Request;
use App\Http\Controllers\api_v1\BaseController as BaseController;
use Illuminate\Support\Facades\Auth;
use App\Models\unit_model;
use App\Models\color_mapping;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ModelController extends BaseController
{
    //

    public function createModel(Request $request)
    {

        try {

            $validator = Validator::make($request->all(), [
                'brand_id' => 'required',
                'model_name' => 'required',
                'code' => 'required',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors());
            }

            $check = unit_model::where('model_name', $request->model_name)->where('brand_id', $request->brand_id)->count();

            if ($check > 0) {
                return $this->sendError('Validation Error.', 'Model already added!');
            }

            // $brand = unit_model::create($request->all());

            DB::beginTransaction();

            $brand = new unit_model;
            $brand->brand_id = $request->brand_id;
            $brand->model_name = $request->model_name;
            $brand->inventory_code = Str::startsWith(trim($request->code), 'RE-') ? trim($request->code) : 'RE-' . trim($request->code);
            $brand->save();

            // foreach ($request->colors as $color) {
            //     $map_color = new color_mapping;
            //     $map_color->color_id = $color['value'];
            //     $map_color->model_id = $brand->id;
            //     $map_color->save();
            // }

            DB::commit();

            return $this->sendResponse([], 'Model added successfully.');
        } catch (\Throwable $th) {
            return $this->sendError($th->errorInfo[2]);
        }
    }

    public function models()
    {

        try {

            $query = unit_model::with(['colors.colorName', 'brands'])->get();
            return $query;
        } catch (\Throwable $th) {
            return $this->sendError($th->errorInfo[2]);
        }
    }

    public function mapColors()
    {

        try {

            $query = DB::table('unit_colors as a')
                ->select('a.*')
                ->get();
            return $query;
        } catch (\Throwable $th) {
            return $this->sendError($th->errorInfo[2]);
        }
    }

    public function updateModel(Request $request, $id)
    {

        try {

            $validator = Validator::make($request->all(), [
                'brand_id' => 'required',
                'model_name' => 'required',
                'code' => 'required',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors());
            }

            DB::beginTransaction();

            $brand = unit_model::where('id', $id)->update([
                'brand_id' => $request->brand_id,
                'inventory_code' => Str::startsWith(trim($request->code), 'RE-') ? trim($request->code) : 'RE-' . trim($request->code),
                'model_name' => $request->model_name
            ]);

            // $remove_existing = color_mapping::where('model_id', $id)->delete();

            // foreach ($request->colors as $color) {
            //     $map_color = new color_mapping;
            //     $map_color->color_id = $color['value'];
            //     $map_color->model_id = $id;
            //     $map_color->save();
            // }

            DB::commit();


            return $this->sendResponse([], 'Model updated successfully.');
        } catch (\Throwable $th) {
            return $this->sendError($th->errorInfo[2]);
        }
    }

    public function modelPerBrand($brandIid)
    {

        try {

            return unit_model::where('brand_id', $brandIid)->get();
        } catch (\Throwable $th) {
            return $this->sendError($th->errorInfo[2]);
        }
    }
}
