<?php

namespace App\Http\Controllers\api_v1;

use Illuminate\Http\Request;
use App\Http\Controllers\api_v1\BaseController as BaseController;
use Illuminate\Support\Facades\Auth;
use App\Models\unit_aging;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class AgingController extends BaseController
{
    //

    public function mapAging(Request $request){

        $validator = Validator::make($request->all(), [
            'days' => 'required',
            'Depreceiation_Cost' => 'required',
            'Estimated_Cost_of_MD_Parts' => 'required',
            'Max_Depreciation_from_Original_SP' => 'required',
            'Immediate_Sales_Value' => 'required',
        ]);
   
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }

        $check = unit_aging::where('days',$request->days)->count();

        if($check > 0){
            return $this->sendError('Validation Error.', 'Aging day already added!');
        }

        $aging = unit_aging::create($request->all());
     
        return $this->sendResponse([], 'Aging added successfully.');

    }

    public function getAging(){
        return unit_aging::all();
    }

    public function updateAging(Request $request,$id){

        $validator = Validator::make($request->all(), [
            'days' => 'required',
            'Depreceiation_Cost' => 'required',
            'Estimated_Cost_of_MD_Parts' => 'required',
            'Max_Depreciation_from_Original_SP' => 'required',
            'Immediate_Sales_Value' => 'required',
        ]);
   
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }

        $aging = unit_aging::where('id',$id)->update($request->all());
        return $this->sendResponse([], 'Aging updated successfully.');
        
    }
    
}
