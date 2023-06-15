<?php

namespace App\Http\Controllers\api_v1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\api_v1\BaseController as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\repo;
use App\Models\FilesUploaded;
use App\Models\receive_unit;
use App\Models\unit_spare_parts;

class ReceiveUnitController extends BaseController
{

	public function createReceiveUnit(Request $request){
		$validator = Validator::make($request->all(), [
			'repo_id' => 'required|numeric',
			'unit_price' => 'required',
			'unit_loan_amount' => 'required',
			'unit_total_payment' => 'required',
			'unit_principal_balance' => 'required',
			'module_id' => 'required|numeric',
			'image_*' => 'nullable',
			'image_id_*' => 'nullable',
			'image_name_*' => 'nullable',
			'images_count' => 'required',
			'spare_parts_id_*' => ($request->certify_no_missing_and_damaged_parts == 'true' ? 'required' : 'nullable'),
			'spare_parts_status_*' => ($request->certify_no_missing_and_damaged_parts == 'true' ? 'required' : 'nullable'),
			'spare_parts_price_*' => ($request->certify_no_missing_and_damaged_parts == 'true' ? 'required' : 'nullable'),
			'spare_parts_remarks_*' => ($request->certify_no_missing_and_damaged_parts == 'true' ? 'required' : 'nullable'),
			'spare_parts_count' => ($request->certify_no_missing_and_damaged_parts == 'true' ? 'required' : 'nullable'),
		]);
	
		if ($validator->fails()) {
			return $this->sendError('Validation Error.', $validator->errors()); 
		}

		// Get the filename using the combine data
		$repo = repo::where('id', '=', $request->repo_id)->first();
		$filename = strtoupper($repo->model_engine.'-'.$repo->model_chassis);
		
		// // wala na ko nakukuha id
		$path = 'image/unit_received/'. $filename;
		$directory = public_path($path);
		if(!File::isDirectory($directory)){
			File::makeDirectory($directory, 0777, true, true);
		}

		$receive = [
			'branch' => Auth::user()->branch,
			'repo_id' => $request->repo_id,
			'unit_price' => $request->unit_price,
			'loan_amount' => $request->unit_loan_amount,
			'total_payments' => $request->unit_total_payment,
			'principal_balance' => $request->unit_principal_balance,
			'is_certified_no_parts' => $request->certify_no_missing_and_damaged_parts,
		];

		$receive_unit = receive_unit::create($receive);
		$latestInsertedId = $receive_unit->id;

		$previous_files = count(json_decode($request->list_of_files, true));
		if($previous_files > 0){
			foreach (json_decode($request->list_of_files, true) as $value) {
				DB::insert("
					DECLARE @moduleid INT = ?, @receivedid INT = ?;
					INSERT INTO files_uploaded (module_id, reference_id, files_id, files_name, path, is_deleted, created_at, updated_at)
					SELECT @moduleid, @receivedid, files_id, files_name, path, 0, GETDATE(), GETDATE()
					FROM files_uploaded WHERE ID = ?",
					array($request->module_id, $latestInsertedId, $value)
				);
			}
		}

		for ($i = 1; $i <= $request->images_count; $i++) { 
			$image = $request->file("image_{$i}");
			if($image){
				$image_name = strtoupper(uniqid().'-'.$image->getClientOriginalName());
				$image->move($directory, $image_name);

				$image_format = [
					'reference_id' => $latestInsertedId,
					'module_id' => $request->module_id,
					'files_id' => $request->input("image_id_{$i}"),
					'files_name' => $request->input("image_name_{$i}"),
					'path' => $path.'/'.$image_name,
				];

				FilesUploaded::create($image_format);
			}
		}
		
		for ($i = 0; $i <= $request->spare_parts_count; $i++) {
			if($request->input("spare_parts_id_{$i}")){
				$spare_parts_format = [
					'recieve_id' => $latestInsertedId,
					'parts_id' => $request->input("spare_parts_id_{$i}"),
					'parts_status' => $request->input("spare_parts_status_{$i}"),
					'price' => $request->input("spare_parts_price_{$i}"),
					'parts_remarks' => $request->input("spare_parts_remarks_{$i}")
				];
				unit_spare_parts::create($spare_parts_format);
			}
		}

		return $this->sendResponse([], 'Received unit added successfully.');
	}

	public function receivedUnits(){
		return DB::select("SELECT
				rep.*, brd.brandname, mdl.model_name,
				ISNULL(rud.id, 0) AS recieved_id,
				CONCAT(cus.firstname,' ',cus.lastname) AS customer_name,
				UPPER(cus.acumatica_id) AS acumatica_id,
				CASE
					WHEN rud.id IS NULL THEN 'For upload details'
					WHEN tns.recieved_unit_id = rud.id THEN 'Goods in transit'
					WHEN rud.status = '0' AND UPPER(rud.is_sold) = 'N' THEN 'Subject for Reprice Approval'
					WHEN rud.status = '1' AND UPPER(rud.is_sold) = 'N' THEN 'For Sell'
					WHEN rud.status = '1' AND UPPER(rud.is_sold) = 'Y' THEN 'Sold'
					WHEN rud.status = '2' THEN 'Disapproved'
					ELSE ''
				END AS current_status, stu.id AS transfer_unit_id,
				(rep.original_srp - rep.amount_paid) as principal
			FROM repo_details rep
			LEFT JOIN customer_profile cus ON rep.customer_acumatica_id = cus.id
			LEFT JOIN brands brd ON rep.brand_id = brd.id
			LEFT JOIN unit_models mdl ON rep.model_id = mdl.id
			LEFT JOIN recieve_unit_details rud ON rep.id = rud.repo_id AND rep.branch_id = rud.branch
			LEFT JOIN stock_transfer_unit stu ON rep.transfer_branch_id = stu.id
			LEFT JOIN (
				SELECT stu.* 
				FROM stock_transfer_unit stu
				INNER JOIN recieve_unit_details rud ON stu.recieved_unit_id = rud.id
				WHERE stu.is_received = '0'
			) tns ON rud.id = tns.recieved_unit_id
			WHERE rep.branch_id = ?
			AND NOT EXISTS(
				SELECT rud.*
				FROM stock_transfer_approval sta
				INNER JOIN stock_transfer_unit stu ON sta.id = stu.stock_transfer_id
				INNER JOIN recieve_unit_details rud ON stu.recieved_unit_id = rud.id
				WHERE sta.status = 1 AND sta.from_branch = rep.branch_id AND rud.repo_id = rep.id
			)",
			array( Auth::user()->branch )
		);
	}

	public function receivedUnitsPerId(Request $request){
		$received = receive_unit::where('id', '=', $request->receivedid)->first();
		$spare_parts = unit_spare_parts::where('recieve_id', '=', $request->receivedid)->where('is_deleted', '=', '0')->get();

		$files = DB::select("DECLARE @moduleid INT = ?, @receivedid INT = ?, @transferid INT = ?;
				SELECT fls.*, 
					CASE WHEN fls.reference_id = stu.recieved_unit_id THEN 'preview' ELSE 'new' END AS file_identity
				FROM files_uploaded fls
				LEFT JOIN stock_transfer_unit stu ON fls.reference_id = stu.recieved_unit_id 
				WHERE fls.is_deleted = 0 AND fls.module_id = @moduleid AND (
					(@transferid IS NULL AND fls.reference_id = @receivedid) OR
					(@transferid IS NOT NULL AND fls.reference_id = stu.recieved_unit_id AND stu.is_use_old_files = '1' AND stu.id = @transferid) 
				)",
			array($request->moduleid, $request->receivedid, $request->transfer_unit_id)
		);

		$old_spare = DB::select("SELECT rus.* 
			FROM stock_transfer_unit stu
			INNER JOIN recieve_unit_spare_parts rus ON stu.recieved_unit_id = rus.recieve_id AND rus.is_deleted = 0 
			WHERE stu.id = ?",
			array($request->transfer_unit_id)
		);

		return $this->sendResponse([], [
			"request" => $request->all(),
			"received_details" => $received,
			"file_details" => $files,
			"spare_details" => $spare_parts,
			"old_spare_details" => $old_spare,
		]);
	}

	public function repoDeleteParts($deleted_id){
		// $filename = unit_spare_parts::Where('id', $deleted_id)->first();
		unit_spare_parts::where('id', $deleted_id)->update([
			'is_deleted' => '1'
		]);
		// return $filename->files_name;
		return [];
	}

	public function updateReceiveUnit(Request $request, $id){
		$validator = Validator::make($request->all(), [
			'repo_id' => 'required|numeric',
			'unit_price' => 'required',
			'unit_loan_amount' => 'required',
			'unit_total_payment' => 'required',
			'unit_principal_balance' => 'required',
			'module_id' => 'required|numeric',
			'image_*' => 'nullable',
			'image_id_*' => 'nullable',
			'image_name_*' => 'nullable',
			'images_count' => 'required',
			'spare_parts_id_*' => ($request->certify_no_missing_and_damaged_parts == 'true' ? 'required' : 'nullable'),
			'spare_parts_status_*' => ($request->certify_no_missing_and_damaged_parts == 'true' ? 'required' : 'nullable'),
			'spare_parts_price_*' => ($request->certify_no_missing_and_damaged_parts == 'true' ? 'required' : 'nullable'),
			'spare_parts_remarks_*' => ($request->certify_no_missing_and_damaged_parts == 'true' ? 'required' : 'nullable'),
			'spare_parts_count' => ($request->certify_no_missing_and_damaged_parts == 'true' ? 'required' : 'nullable'),
		]);

		if ($validator->fails()) {
			return $this->sendError('Validation Error.', $validator->errors()); 
		}

		// Get the filename using the combine data
		$repo = repo::where('id', '=', $request->repo_id)->first();
		$filename = strtoupper($repo->model_engine.'-'.$repo->model_chassis);
		
		// wala na ko nakukuha id
		$path = 'image/unit_received/'. $filename;
		$directory = public_path($path);
		if(!File::isDirectory($directory)){
			File::makeDirectory($directory, 0777, true, true);
		}

		$receive = [
			'unit_price' => $request->unit_price,
			'loan_amount' => $request->unit_loan_amount,
			'total_payments' => $request->unit_total_payment,
			'principal_balance' => $request->unit_principal_balance,
			'is_certified_no_parts' => $request->certify_no_missing_and_damaged_parts,
		];
		receive_unit::where('id', $id)->update($receive);

		$previous_files = count(json_decode($request->list_of_files, true));
		if($previous_files > 0){
			DB::table('files_uploaded')
				->where('module_id', $request->module_id)
				->where('reference_id', $request->id)
				->whereNotIn('id', json_decode($request->list_of_files))
				->update(['is_deleted' => '1']);
		}

		for ($i = 1; $i <= $request->images_count; $i++) { 
			$image = $request->file("image_{$i}");
			if($image){
				$image_name = strtoupper(uniqid().'-'.$image->getClientOriginalName());
				$image->move($directory, $image_name);

				$image_format = [
					'reference_id' => $id,
					'module_id' => $request->module_id,
					'files_id' => $request->input("image_id_{$i}"),
					'files_name' => $request->input("image_name_{$i}"),
					'path' => $path.'/'.$image_name,
				];

				FilesUploaded::create($image_format);
			}
		}
		
		for ($i = 0; $i <= $request->spare_parts_count; $i++) {
			if($request->input("spare_parts_id_{$i}")){
				$spare_parts_format = [
					'recieve_id' => $id,
					'parts_id' => $request->input("spare_parts_id_{$i}"),
					'parts_status' => $request->input("spare_parts_status_{$i}"),
					'price' => $request->input("spare_parts_price_{$i}"),
					'parts_remarks' => $request->input("spare_parts_remarks_{$i}")
				];

				if($request->input("parts_unique_id_{$i}") == 0){
					unit_spare_parts::create($spare_parts_format);
				}
				else{
					unit_spare_parts::where('id', $request->input("parts_unique_id_{$i}"))->update($spare_parts_format);
				}
			}
		}

		return $this->sendResponse([], 'Successfully Updated');
 	}
}
