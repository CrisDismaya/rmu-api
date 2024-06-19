<?php

namespace App\Http\Controllers\api_v1;

use App\Http\Controllers\api_v1\BaseController as BaseController;
use Illuminate\Http\Request;
use App\Models\customer_profiling;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Yajra\Datatables\Datatables;

class CustomerProfileController extends BaseController
{
    //

    function createCustomerProfile(Request $request)
    {

        try {
            $validator = Validator::make($request->all(), [
                'acumatica_id' => 'nullable',
                'firstname' => 'required',
                'middlename' => 'nullable',
                'lastname' => 'required',
                'contact' => 'required',
                'address' => 'nullable',
                'province' => 'required',
                'city' => 'required',
                'barangay' => 'required',
                'zip_code' => 'nullable',
                'nationality' => 'required',
                'source_of_income' => 'required',
                'marital_status' => 'required',
                'date_birth' => 'required',
                'birth_place' => 'required',
                'primary_id' => 'required',
                'primary_id_no' => 'nullable',
                'alternative_id' => 'nullable',
                'alternative_id_no' => 'nullable',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors());
            }

            if ($request->acumatica_id != '') {
                $checker = customer_profiling::where('acumatica_id', $request->acumatica_id)->count();
                if ($checker > 0) {
                    return $this->sendError('Validation Error.', 'Customer already added!');
                }
            }

            $format = [
                'acumatica_id' => $request->acumatica_id,
                'firstname' => $request->firstname,
                'middlename' => $request->middlename,
                'lastname' => $request->lastname,
                'contact' => $request->contact,
                'address' => $request->address,
                'provinces' => $request->province,
                'cities' => $request->city,
                'barangays' => $request->barangay,
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
        } catch (\Throwable $th) {
            return $this->sendError($th->errorInfo[2]);
        }
    }

    function listOfCustomer(){
       $customers = customer_profiling::select(
            '*',
            DB::raw("CONCAT(firstname,' ',lastname) AS customer_name")
        )->get();
        return $customers;
    }

    function customerProfile()
    {
        try {
            $stmt = DB::select("SELECT TOP 10000 *,  UPPER(
                    CONCAT(firstname,
                        CASE
                            WHEN middlename != '' THEN CONCAT(' ', middlename, ' ')
                        ELSE ' ' END, lastname
                    )
                ) AS customer_name FROM customer_profile");
            $datatables = Datatables::of($stmt);

            return $datatables->make(true);


        } catch (\Throwable $th) {
            return $this->sendError($th->errorInfo[2]);
        }
    }

    function updateCustomerProfile(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'acumatica_id' => 'nullable',
                'firstname' => 'required',
                'middlename' => 'nullable',
                'lastname' => 'required',
                'contact' => 'required',
                'address' => 'nullable',
                'province' => 'required',
                'city' => 'required',
                'barangay' => 'required',
                'zip_code' => 'nullable',
                'nationality' => 'required',
                'source_of_income' => 'required',
                'marital_status' => 'required',
                'date_birth' => 'required',
                'birth_place' => 'required',
                'primary_id' => 'required',
                'primary_id_no' => 'nullable',
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
                'provinces' => $request->province,
                'cities' => $request->city,
                'barangays' => $request->barangay,
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
        } catch (\Throwable $th) {
            return $this->sendError($th->errorInfo[2]);
        }
    }

    public function customerProfilePerId($id)
    {
        try {
            return DB::table('customer_profile as cus')
                ->select('cus.*', DB::raw("UPPER(CONCAT(cus.address,' ',pro.Title,' ',cty.Title,' ',bgy.Title)) AS fulladdress"))
                ->leftJoin('province as pro', 'cus.provinces', '=', 'pro.OrderNumber')
                ->leftJoin('city as cty', 'cus.cities', '=', 'cty.MappingId')
                ->leftJoin('barangay as bgy', 'cus.barangays', '=', 'bgy.OrderNumber')
                ->where('cus.id', $id)
                ->first();
        } catch (\Throwable $th) {
            return $this->sendError($th->errorInfo[2]);
        }
    }

    public function provinceList()
    {
        try {
            return DB::table("province")->select('OrderNumber', 'Title')->orderBy('Title', 'ASC')->get();
        } catch (\Throwable $th) {
            return $this->sendError($th->errorInfo[2]);
        }
    }

    public function cityList($provinceId)
    {
        try {
            return DB::table("city")->select('MappingId', 'Title')->where('ParentId', $provinceId)->orderBy('Title', 'ASC')->get();
        } catch (\Throwable $th) {
            return $this->sendError($th->errorInfo[2]);
        }
    }

    public function brgyList($cityId)
    {
        try {
            return DB::table("barangay")->select('OrderNumber', 'Title')->where('ParentId', $cityId)->orderBy('Title', 'ASC')->get();
        } catch (\Throwable $th) {
            return $this->sendError($th->errorInfo[2]);
        }
    }

    public function source_of_income()
    {
        try {
            return DB::table("source_of_income")->select('source')->get();
        } catch (\Throwable $th) {
            return $this->sendError($th->errorInfo[2]);
        }
    }

    public function nationality()
    {
        try {
            return DB::table("nationality")->select('name')->get();
        } catch (\Throwable $th) {
            return $this->sendError($th->errorInfo[2]);
        }
    }
}
