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

    public function createColor(Request $request){
        $validator = Validator::make($request->all(), [
            'name' => 'required',
        ]);
   
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }

        $check = unit_color::where('name', $request->name)->count();

        if($check > 0){
            return $this->sendError('Validation Error.', 'Color already added!');
        }

        $color = unit_color::create($request->all());
        return $this->sendResponse([], 'Color added successfully.');
    }

    public function colors(){
        return unit_color::all();
    }

    public function updateColor(Request $request, $id){
        $validator = Validator::make($request->all(), [
            'name' => 'required',
        ]);
   
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }

        $color = unit_color::where('id',$id)->update($request->all());
        return $this->sendResponse([], 'Color updated successfully.');
    }
}
