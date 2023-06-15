<?php

namespace App\Http\Controllers\api_v1;

use Illuminate\Http\Request;
use App\Http\Controllers\api_v1\BaseController as BaseController;
use Illuminate\Support\Facades\Auth;
use App\Models\spare_parts;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class PartsController extends BaseController
{
    //

    public function createParts(Request $request){
        $validator = Validator::make($request->all(), [
            'model_id' => 'required',
            'name' => 'required',
            'price' => 'required',
            'inventory_code' => 'required',
        ]);
   
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }

        $check = spare_parts::where('name',$request->name)->where('model_id',$request->model_id)->count();

     

        if($check > 0){
            return $this->sendError('Validation Error.', 'Spare Parts already added!');
        }

        $brand = spare_parts::create($request->all());
     
        return $this->sendResponse([], 'Spare Parts added successfully.');
    }

    public function parts(){
        $query = DB::table('spare_parts as a')
                   ->join('unit_models as b','b.id','a.model_id')
                   ->select('a.*','b.model_name')
                   ->get();
        return $query;
    }

    public function updateParts(Request $request, $id){
        $validator = Validator::make($request->all(), [
            'model_id' => 'required',
            'name' => 'required',
            'price' => 'required',
        ]);
   
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }

        $brand = spare_parts::where('id',$id)->update($request->all());
        return $this->sendResponse([], 'Spare Parts updated successfully.');
    }

    public function partsPerModel($model_id){
        // return spare_parts::where('model_id', $model_id)->get();
        return DB::table('spare_parts')
                ->select('id as value', 'name as label')
                ->where('model_id', $model_id)->get();
    } 
    
    public function partsPrice($parts_id){
        // return spare_parts::where('model_id', $model_id)->get();
        return DB::table('spare_parts')
                ->select('price')
                ->where('id', $parts_id)->first();
    }

}
