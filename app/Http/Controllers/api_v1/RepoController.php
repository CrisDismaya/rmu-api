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

class RepoController extends BaseController
{

   public function createRepo(Request $request){

		$validator = Validator::make($request->all(), [
			'customer_acumatica_id' => 'required',
			'brand_id' => 'required|numeric',
			'model_id' => 'required|numeric',
			'plate_number' => 'required|string',
			'model_engine' => 'required|string',
			'model_chassis' => 'required|string',
			'color_id' => 'required|numeric',
			'mv_file_number' => 'required|string',
			'type' => 'required|string',
			'classification' => 'required|string',
			'series' => 'required|string',
			'body' => 'required|string',
			'year_model' => 'required|integer',
			'gross_vehicle_weight' => 'required|string',
			'original_srp' => 'required|numeric',
			'date_sold' => 'required|date',
			'insurer' => 'required|string',
			'cert_cover_no' => 'required|string',
			'expiry_date' => 'required|date',
			'encumbered_to' => 'nullable|string',
			'leased_to' => 'nullable|string',
			'latest_or_number' => 'required|string',
			'date_last_registration' => 'required|date',
			'amount_paid' => 'required|numeric',
			'date_surrender' => 'required|date',
			'msuisva_form_no' => 'required|string',
			'append_count' => 'required',
			'module_id' => 'required',
			'image_*' => 'nullable',
			'image_id_*' => 'nullable',
			'image_name_*' => 'nullable',
		]);

		if ($validator->fails()) {
			return $this->sendError('Validation Error.', $validator->errors()); 
		}

		$checker = DB::table('repo_details as rep')
			->select('model_engine', 'model_chassis', 'is_sold')
			->leftJoin('recieve_unit_details as rud', 'rep.id', 'rud.repo_id')
			->where('rud.is_sold', '=', 'Y')
			->where('rep.model_engine', '=', $request->model_engine)
			->where('rep.model_chassis', '=', $request->model_chassis)
			->get()->count();

		if($checker > 0){
			return $this->sendError([], 'The existing Unit is not been sold'); 
		}

		$format = [
			'branch_id' => Auth::user()->branch,
			'customer_acumatica_id' => $request->customer_acumatica_id,
			'brand_id' => $request->brand_id,
			'model_id' => $request->model_id,
			'plate_number' => $request->plate_number,
			'model_engine' => $request->model_engine,
			'model_chassis' => $request->model_chassis,
			'color_id' => $request->color_id,
			'mv_file_number' => $request->mv_file_number,
			'type' => $request->type,
			'classification' => $request->classification,
			'series' => $request->series,
			'body' => $request->body,
			'year_model' => $request->year_model,
			'gross_vehicle_weight' => $request->gross_vehicle_weight,
			'original_srp' => $request->original_srp,
			'date_sold' => $request->date_sold,
			'insurer' => $request->insurer,
			'cert_cover_no' => $request->cert_cover_no,
			'expiry_date' => $request->expiry_date,
			'encumbered_to' => $request->encumbered_to,
			'leased_to' => $request->leased_to,
			'latest_or_number' => $request->latest_or_number,
			'date_last_registration' => $request->date_last_registration,
			'amount_paid' => $request->amount_paid,
			'date_sold' => $request->date_sold,
			'date_surrender' => $request->date_surrender,
			'msuisva_form_no' => $request->msuisva_form_no
		];

		$repo = repo::create($format);
		$latestInsertedId = $repo->id;

		$path = 'image/unit_received/'. strtoupper($request->model_engine .'-'. $request->model_chassis);
		$directory = public_path($path);
		if(!File::isDirectory($directory)){
			File::makeDirectory($directory, 0777, true, true);
		}

		for ($i = 1; $i <= $request->append_count; $i++) { 
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
		
		return $this->sendResponse([], 'REPO Ddetails added successfully.');
	}

	public function repo(){
		return DB::select("SELECT 
				rep.*, brd.brandname, mdl.model_name, cus.acumatica_id,
				CONCAT(cus.firstname, ' ', cus.lastname) AS customer_name
			FROM repo_details AS rep
			INNER JOIN customer_profile AS cus ON rep.customer_acumatica_id = cus.id
			LEFT JOIN brands AS brd ON rep.brand_id = brd.id
			LEFT JOIN unit_models AS mdl ON rep.model_id = mdl.id
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

	public function repoDetailsPerId($id, $moduleid){
		$received_units = repo::with([ 'customer_details', 'brand_details', 'model_details', 'color_details', 
			'picture_details' => function($query) use ($moduleid) {
				$query->where('module_id', '=', $moduleid);
		  }
		])
			->where('id', $id)->first();

		return $received_units;
	}

	public function list_of_files(){
		$files = DB::table('files')
			->where('status', '=', '1')
			->get();

		return $files;
	}

	public function repoDeleteFiles($deleted_id){
		$filename = FilesUploaded::Where('id', $deleted_id)->first();
		FilesUploaded::where('id', $filename->id)->update([
			'is_deleted' => '1'
		]);
		return $filename->files_name;
	}

	public function updateRepo(Request $request, $id){
		$validator = Validator::make($request->all(), [
			'customer_acumatica_id' => 'required',
			'brand_id' => 'required|numeric',
			'model_id' => 'required|numeric',
			'plate_number' => 'required|string',
			'model_engine' => 'required|string',
			'model_chassis' => 'required|string',
			'color_id' => 'required|numeric',
			'mv_file_number' => 'required|string',
			'type' => 'required|string',
			'classification' => 'required|string',
			'series' => 'required|string',
			'body' => 'required|string',
			'year_model' => 'required|integer',
			'gross_vehicle_weight' => 'required|string',
			'original_srp' => 'required|numeric',
			'date_sold' => 'required|date',
			'insurer' => 'required|string',
			'cert_cover_no' => 'required|string',
			'expiry_date' => 'required|date',
			'encumbered_to' => 'nullable|string',
			'leased_to' => 'nullable|string',
			'latest_or_number' => 'required|string',
			'date_last_registration' => 'required|date',
			'amount_paid' => 'required|numeric',
			'date_surrender' => 'required|date',
			'msuisva_form_no' => 'required|string',
			'append_count' => 'required',
			'module_id' => 'required',
			'image_*' => 'required',
			'image_id_*' => 'required',
			'image_name_*' => 'required',
		]);

		if ($validator->fails()) {
			return $this->sendError('Validation Error.', $validator->errors()); 
		}

		$format = [
			'customer_acumatica_id' => $request->customer_acumatica_id,
			'brand_id' => $request->brand_id,
			'model_id' => $request->model_id,
			'plate_number' => $request->plate_number,
			'model_engine' => $request->model_engine,
			'model_chassis' => $request->model_chassis,
			'color_id' => $request->color_id,
			'mv_file_number' => $request->mv_file_number,
			'type' => $request->type,
			'classification' => $request->classification,
			'series' => $request->series,
			'body' => $request->body,
			'year_model' => $request->year_model,
			'gross_vehicle_weight' => $request->gross_vehicle_weight,
			'original_srp' => $request->original_srp,
			'date_sold' => $request->date_sold,
			'insurer' => $request->insurer,
			'cert_cover_no' => $request->cert_cover_no,
			'expiry_date' => $request->expiry_date,
			'encumbered_to' => $request->encumbered_to,
			'leased_to' => $request->leased_to,
			'latest_or_number' => $request->latest_or_number,
			'date_last_registration' => $request->date_last_registration,
			'amount_paid' => $request->amount_paid,
			'date_sold' => $request->date_sold,
			'date_surrender' => $request->date_surrender,
			'msuisva_form_no' => $request->msuisva_form_no
		];

		repo::where('id', '=', $id)->update($format);

		$path = 'image/unit_received/'. strtoupper($request->model_engine .'-'. $request->model_chassis);
		$directory = public_path($path);
		if(!File::isDirectory($directory)){
			File::makeDirectory($directory, 0777, true, true);
		}

		for ($i = 1; $i <= $request->append_count; $i++) { 
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
		return $this->sendResponse([], 'REPO Ddetails update successfully.');
	}
}
