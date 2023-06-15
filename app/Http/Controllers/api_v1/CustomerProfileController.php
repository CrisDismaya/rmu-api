<?php

namespace App\Http\Controllers\api_v1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\api_v1\BaseController as BaseController;
use Illuminate\Http\Request;
use App\Models\customer_profiling;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class CustomerProfileController extends BaseController
{
    //

    function createCustomerProfile(Request $request){
        $validator = Validator::make($request->all(), [
            'acumatica_id' => 'required',
            'firstname' => 'required',
            'middlename' => 'nullable',
            'lastname' => 'required',
            'contact' => 'required',
            'address' => 'required',
            'province' => 'nullable',
            'city' => 'nullable',
            'barangay' => 'nullable',
            'zip_code' => 'nullable',
            'nationality' => 'required', 
            'source_of_income' => 'required', 
            'marital_status' => 'required', 
            'date_birth' => 'required', 
            'birth_place' => 'required', 
            'primary_id' => 'required', 
            'primary_id_no' => 'required', 
            'alternative_id' => 'nullable', 
            'alternative_id_no' => 'nullable', 
        ]);
    
        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors()); 
        }
        
        $checker = customer_profiling::where('acumatica_id', $request->acumatica_id)->count();
        if($checker > 0){
            return $this->sendError('Validation Error.', 'Brand already added!');
        }

        $format = [
            'acumatica_id' => $request->acumatica_id,
            'firstname' => $request->firstname,
            'middlename' => $request->middlename,
            'lastname' => $request->lastname,
            'contact' => $request->contact,
            'address' => $request->address,
            'provinces' => $request->provinces,
            'cities' => $request->cities,
            'barangays' => $request->barangays,
            'zip_code' => $request->zip_code,
            'nationality' => $request->nationality, 
            'source_of_income' => $request->source_of_income, 
            'marital_status' => $request->marital_status, 
            'date_birth' => $request->date_birth, 
            'birth_place' => $request->birth_place, 
            'primary_id' => $request->primary_id, 
            'primary_id_no' => $request->primary_id_no, 
            'alternative_id' => $request->alternative_id, 
            'alternative_id_no' => $request->alternative_id_no, 
        ];

        customer_profiling::create($format);
        return $this->sendResponse([], 'Customer Profile Successfully Added!');
    }

    function customerProfile(){
        $customers = customer_profiling::select('*', 
                DB::raw("CONCAT(firstname,' ',lastname) AS customer_name")
            )->get();
        return $customers;
    }

    function updateCustomerProfile(Request $request, $id){
        $validator = Validator::make($request->all(), [
            'acumatica_id' => 'required',
            'firstname' => 'required',
            'middlename' => 'nullable',
            'lastname' => 'required',
            'contact' => 'required',
            'address' => 'required',
            'province' => 'nullable',
            'city' => 'nullable',
            'barangay' => 'nullable',
            'zip_code' => 'nullable',
            'nationality' => 'required', 
            'source_of_income' => 'required', 
            'marital_status' => 'required', 
            'date_birth' => 'required', 
            'birth_place' => 'required', 
            'primary_id' => 'required', 
            'primary_id_no' => 'required', 
            'alternative_id' => 'nullable', 
            'alternative_id_no' => 'nullable', 
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors()); 
        }

        $format = [
            'acumatica_id' => $request->acumatica_id,
            'firstname' => $request->firstname,
            'middlename' => $request->middlename,
            'lastname' => $request->lastname,
            'contact' => $request->contact,
            'address' => $request->address,
            'provinces' => $request->provinces,
            'cities' => $request->cities,
            'barangays' => $request->barangays,
            'zip_code' => $request->zip_code,
            'nationality' =>  $request->nationality, 
            'source_of_income' =>  $request->source_of_income, 
            'marital_status' =>  $request->marital_status, 
            'date_birth' =>  $request->date_birth, 
            'birth_place' =>  $request->birth_place, 
            'primary_id' =>  $request->primary_id, 
            'primary_id_no' =>  $request->primary_id_no, 
            'alternative_id' =>  $request->alternative_id, 
            'alternative_id_no' =>  $request->alternative_id_no, 
        ];

        customer_profiling::where('id', $id)->update($format);
        return $this->sendResponse([], 'Model updated successfully.');
    }

    public function customerProfilePerId($id){
        return customer_profiling::where('id', '=', $id)->first();
    }
}
