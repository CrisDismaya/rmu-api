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

    public function createBrand(Request $request){
        $validator = Validator::make($request->all(), [
            'code' => 'required',
            'brandname' => 'required',
        ]);
   
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }

        $check = brand::where('brandname',$request->brandname)->count();

     

        if($check > 0){
            return $this->sendError('Validation Error.', 'Brand already added!');
        }

        $brand = brand::create($request->all());
     
        return $this->sendResponse([], 'Brand added successfully.');
    }

    public function brands(){
        return brand::all();
    }

    public function updateBrand(Request $request, $id){
        $validator = Validator::make($request->all(), [
            'code' => 'required',
            'brandname' => 'required',
        ]);
   
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }

        $brand = brand::where('id',$id)->update($request->all());
        return $this->sendResponse([], 'Brand updated successfully.');
    }
}
