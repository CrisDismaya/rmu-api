<?php

namespace App\Http\Controllers\api_v1;

use App\Http\Controllers\api_v1\BaseController as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use App\Models\request_refurbish;
use App\Models\refurbish_detail;
use App\Models\refurbishProcess;
use Illuminate\Http\Request;
use App\Http\Traits\helper;
use Carbon\Carbon;
use App\Models\approval_matrix_setting;

class RequestRefurbishController extends BaseController
{
	//
	use helper;
	public function listOfForRefurbish()
	{

		try {

			$list_id = array();

			$get_all_repo =  request_refurbish::select('repo_id')->whereIn('status', ['0', '3'])->get();

			foreach ($get_all_repo as $repo) {
				array_push($list_id, $repo->repo_id);
			}

			$received_units = DB::table('recieve_unit_details AS rud')
				->join('repo_details as repo', 'repo.id', 'rud.repo_id')
				->join('branches as br', 'repo.branch_id', 'br.id')
				->join('brands as brd', 'repo.brand_id', 'brd.id')
				->join('unit_models as mdl', 'repo.model_id', 'mdl.id')
				->join('unit_colors as color', 'repo.color_id', 'color.id')
				->leftJoin(
					DB::raw("(
							SELECT
								repo.id AS repo_id, COUNT(upload.id) AS total_upload_required_files
							FROM repo_details repo
							LEFT JOIN files_uploaded upload ON repo.id = upload.reference_id AND repo.branch_id = upload.branch_id
							INNER JOIN (
								SELECT * FROM files WHERE isRequired = 1 AND status = 1
							) files ON upload.files_id = files.id
							WHERE upload.is_deleted = 0
							GROUP BY repo.id, upload.branch_id
						) files"),
					"files.repo_id",
					"=",
					"repo.id"
				)
				->select(
					'rud.id as receive_id',
					'repo.id as repo_id',
					'repo.model_engine',
					'repo.model_chassis',
					'repo.date_sold',
					'br.name as branchname',
					'brd.brandname',
					'mdl.model_name',
					'color.name as color'
				)
				->where('rud.status', '!=', '4')
				->where('rud.is_sold', '=', 'N')
				->whereRaw('ISNULL(files.total_upload_required_files, 0) = (SELECT COUNT(*) FROM files WHERE isRequired = 1 AND status = 1)')
				->whereNotExists(function ($query) {
					$query->select(DB::raw(1))
						->from('sold_units')
						->whereRaw('sold_units.repo_id = repo.id')
						->whereRaw('sold_units.status = 0')
						->whereRaw('sold_units.branch =' . Auth::user()->branch);
				})
				->whereNotExists(function ($query) {
					$query->select(DB::raw(1))
						->from('request_approvals')
						->whereRaw('request_approvals.repo_id = repo.id')
						->whereRaw('request_approvals.status = 0')
						->whereRaw('request_approvals.branch =' . Auth::user()->branch);
				})
				->whereNotExists(function ($query) {
					$query->select(DB::raw(1))
						->from('stock_transfer_unit as a')
						->join('stock_transfer_approval as b', 'b.id', 'a.stock_transfer_id')
						->whereRaw('a.recieved_unit_id = rud.id')
						->whereRaw('b.status = 0');
				});
			if (Auth::user()->userrole != 'Warehouse Custodian') {
				$received_units = $received_units->whereNotIn('repo.id', $list_id);
			} else {
				$received_units = $received_units
					->whereNotIn('repo.id', $list_id)
					->where('repo.branch_id', Auth::user()->branch);
			}

			$received_units = $received_units->get();

			$data = ['data' => $received_units, 'role' =>  'Maker'];
			return $data;
		} catch (\Throwable $th) {
			return $this->sendError($th->errorInfo[2]);
		}
	}

	public function getMissingDamageParts($received_id)
	{

		try {

			return DB::table('recieve_unit_spare_parts as a')
				->join('spare_parts as b', 'b.id', 'a.parts_id')
				->select('b.*', 'a.price', 'a.id as received_ids')
				->where('recieve_id', $received_id)
				->where('is_deleted', '=', '0')
				->get();
		} catch (\Throwable $th) {
			return $this->sendError($th->errorInfo[2]);
		}
	}

	public function getPartsForRefurbish($received_id)
	{

		try {

			// $get_spare_missing = DB::table('refurbish_details as a')
			// 	->join('spare_parts as b', 'b.id', 'a.spare_parts')
			// 	->select('b.*', 'a.price', 'a.actual_price', 'a.id as record_id', 'a.status')
			// 	->where('a.refurbish_id', $received_id)->get();
			// return $get_spare_missing;

			return DB::table('recieve_unit_spare_parts as a')
				->join('spare_parts as b', 'b.id', 'a.parts_id')
				->select('b.*', 'a.price', 'a.id as record_id', 'a.actual_price', DB::raw("ISNULL(refurb_decision, '') AS status"))
				->where('recieve_id', $received_id)
				->where('is_deleted', '=', '0')
				->get();
		} catch (\Throwable $th) {
			return $this->sendError($th->errorInfo[2]);
		}
	}

	public function getRefurbishParts($repo_id)
	{
		try {
			return DB::table("recieve_unit_details as received")
				->join("recieve_unit_spare_parts as received_parts", "received.id", "received_parts.recieve_id")
				->leftjoin("spare_parts as parts", "received_parts.parts_id", "parts.id")
				->select("received_parts.id as received_ids", "received_parts.price", "parts.name", "parts.id")
				->where('received.repo_id', '=', $repo_id)
				->get();

			// $get_spare_missing = DB::table('refurbish_details as a')
			//     ->join('spare_parts as b', 'b.id', 'a.spare_parts')
			//     ->select('b.*', 'a.price')
			//     ->where('a.refurbish_id', $refurbishid)->get();

			// return $get_spare_missing;
		} catch (\Throwable $th) {
			return $this->sendError($th->errorInfo[2]);
		}
	}

	public function getUploadedDocuments($refurbishid)
	{

		try {

			$getDocuments = DB::table('refurbish_processes')->select('files_names')->where('refurbish_req_id', $refurbishid)->get();

			return $getDocuments;
		} catch (\Throwable $th) {
			return $this->sendError($th->errorInfo[2]);
		}
	}

	public function updateRefurbishProcess(Request $request, $id)
	{
		try {
			$file_list = array();
			$validator = Validator::make($request->all(), [
				'refurbish_id' => 'required|numeric',
				'spares' => 'required',
			]);

			if ($validator->fails()) {
				return $this->sendError('Validation Error.', $validator->errors());
			}

			$get_data = refurbishProcess::where('id', $id)->first(); // get record data first

			DB::beginTransaction();
			$arr = ['status' => '0'];
			if ($request->total_documents > 0) {

				$folder_path = 'image/Refurbish/' . strtoupper($request->repo_id . '-' . $request->model_engine . '-' . $request->model_chassis);
				$directory = public_path($folder_path);
				if (!File::isDirectory($directory)) {
					File::makeDirectory($directory, 0777, true, true);
				}

				$input = $request->all();

				for ($i = 0; $i < $request->total_documents; $i++) {

					$image = $request->file("related_documents_" . $i + 1);
					if ($image) {
						$image_name = strtoupper(uniqid() . '-' . $image->getClientOriginalName());
						$image->move($directory, $image_name);

						array_push($file_list, [
							'filename' => $image_name,
							'path' => $folder_path . '/' . $image_name
						]);
					}
				}

				$tmp_filelist = $get_data->files_names;
				$final_filelist = json_decode($tmp_filelist, true);

				for ($i = 0; $i < count($final_filelist); $i++) {
					array_push($file_list, [
						'filename' => $final_filelist[$i]['filename'],
						'path' => $final_filelist[$i]['path']
					]);
				}

				$arr = ['files_names' => json_encode($file_list), 'status' => '0'];
			}


			$get_data = refurbishProcess::where('id', $id)->update($arr);

			$spares = json_decode($request->spares, true);
			foreach ($spares as $parts) {
				// $update = refurbish_detail::where('id', $parts['parts_id'])->update(['status' => $parts['status']]);
				DB::table("recieve_unit_spare_parts")
					->where("id", '=', $parts['received_parts_id'])
					// ->where("parts_id", '=',  $parts['parts_id'])
					->update([
						'actual_price' => $parts['actual_price'],
						'refurb_decision' => $parts['status'],
					]);
			}

			$matrix =  $this->ApprovalMatrixActivityLog($request->module_id, $id);

			if ($matrix['status'] == 'error') {
				return $matrix;
			} else {
				//update the first holder of the transaction
				$save_holder = refurbishProcess::where('id', $id)->update(['approver' => $matrix['message']]);
			}

			DB::commit();

			return $this->sendResponse([], 'Request refurbish approval saved.');
		} catch (\Throwable $th) {
			return $this->sendError($th->errorInfo[2]);
		}
	}

	public function proceedRefurbish(Request $request)
	{

		try {
			$file_list = array();
			$validator = Validator::make($request->all(), [
				'refurbish_id' => 'required|numeric',
				'spares' => 'required',
			]);

			if ($validator->fails()) {
				return $this->sendError('Validation Error.', $validator->errors());
			}

			if ($request->total_documents == 0) {
				return $this->sendError('Validation Error.', 'Please upload some documents');
			}

			DB::beginTransaction();

			$folder_path = 'image/Refurbish/' . strtoupper($request->repo_id . '-' . $request->model_engine . '-' . $request->model_chassis);
			$directory = public_path($folder_path);
			if (!File::isDirectory($directory)) {
				File::makeDirectory($directory, 0777, true, true);
			}

			$input = $request->all();

			for ($i = 0; $i < $request->total_documents; $i++) {

				$image = $request->file("related_documents_" . $i + 1);
				if ($image) {
					$image_name = strtoupper(uniqid() . '-' . $image->getClientOriginalName());
					$image->move($directory, $image_name);

					array_push($file_list, [
						'filename' => $image_name,
						'path' => $folder_path . '/' . $image_name
					]);
				}
			}

			$refurbish = new refurbishProcess;
			$refurbish->refurbish_req_id = $request->refurbish_id;
			$refurbish->maker = Auth::user()->id;
			$refurbish->files_names = json_encode($file_list);
			$refurbish->re_class = $request->classification;
			$refurbish->save();

			$spares = json_decode($request->spares, true);
			// return $spares;
			foreach ($spares as $parts) {
				// $update = refurbish_detail::where('id', $parts['parts_id'])->update(['status' => $parts['status'], 'actual_price' => $parts['actual_price']]);
				DB::table("recieve_unit_spare_parts")
					->where("id", '=', $parts['received_parts_id'])
					// ->where("parts_id", '=',  $parts['parts_id'])
					->update([
						'actual_price' => (double) $parts['actual_price'],
						'refurb_decision' => $parts['status'],
					]);
			}

			$matrix =  $this->ApprovalMatrixActivityLog($request->module_id, $refurbish->id);

			if ($matrix['status'] == 'error') {
				return $matrix;
			} else {
				//update the first holder of the transaction
				$save_holder = refurbishProcess::where('id', $refurbish->id)->update(['approver' => $matrix['message']]);
				$update_head_table = request_refurbish::where('id', $request->refurbish_id)->update(['status' => '3']);
			}

			DB::commit();

			return $this->sendResponse($spares, 'Request refurbish approval save.');
		} catch (\Throwable $th) {
			return $this->sendError($th->errorInfo[2]);
		}
	}

	public function cancelRefurbish(Request $request)
	{

		try {

			DB::beginTransaction();

			$remove_parent = DB::table('request_refurbishes')->where('id', $request->id)->delete();
			$remove_details = DB::table('refurbish_details')->where('refurbish_id', $request->id)->delete();

			$ongoing_referbish = DB::table('refurbish_processes')->where('refurbish_req_id', $request->id)->count();

			if ($ongoing_referbish > 0) {
				$delete_process = DB::table('refurbish_processes')->where('refurbish_req_id', $request->id)->delete();
			}

			DB::commit();

			return $this->sendResponse([], 'Request successfully removed.');
		} catch (\Throwable $th) {
			return $this->sendError($th->errorInfo[2]);
		}
	}

	public function requestRefurbish(Request $request)
	{

		$file_list = array();
		$validator = Validator::make($request->all(), [
			'repo_id' => 'required|numeric',
			'spares' => 'required',
		]);

		if ($validator->fails()) {
			return $this->sendError('Validation Error.', $validator->errors());
		}

		if ($request->q1 == 'null' && $request->q2 == 'null' && $request->q3 == 'null') {
			return $this->sendError('Validation Error.', 'Please upload atleast 1 Qoutation!');
		}

		try {
			DB::beginTransaction();

			$folder_path = 'image/Qoutation';
			$directory = public_path($folder_path);
			if (!File::isDirectory($directory)) {
				File::makeDirectory($directory, 0777, true, true);
			}

			if ($request->q1 != 'null') {
				$image1 = $request->file("q1");
				if ($image1) {
					$image_name1 = strtoupper(uniqid() . '-' . $image1->getClientOriginalName());
					$image1->move($directory, $image_name1);

					array_push($file_list, [
						'filename' => $image_name1,
						'path' => $folder_path . '/' . $image_name1
					]);
				}
			}

			if ($request->q2 != 'null') {
				$image1 = $request->file("q2");
				if ($image1) {
					$image_name1 = strtoupper(uniqid() . '-' . $image1->getClientOriginalName());
					$image1->move($directory, $image_name1);

					array_push($file_list, [
						'filename' => $image_name1,
						'path' => $folder_path . '/' . $image_name1
					]);
				}
			}

			if ($request->q3 != 'null') {
				$image1 = $request->file("q3");
				if ($image1) {
					$image_name1 = strtoupper(uniqid() . '-' . $image1->getClientOriginalName());
					$image1->move($directory, $image_name1);

					array_push($file_list, [
						'filename' => $image_name1,
						'path' => $folder_path . '/' . $image_name1
					]);
				}
			}

			$refurbish = new request_refurbish;
			$refurbish->repo_id = $request->repo_id;
			$refurbish->branch = Auth::user()->branch;
			$refurbish->maker = Auth::user()->id;
			$refurbish->files_names = json_encode($file_list);
			$refurbish->save();

			$spares = json_decode($request->spares, true);
			foreach ($spares as $parts) {
				// $details = new refurbish_detail;
				// $details->refurbish_id = $refurbish->id;
				// $details->spare_parts = $parts['parts_id'];
				// $details->price = $parts['price'];
				// $details->save();

				DB::table("recieve_unit_spare_parts")
					->where("id", '=', $parts['received_parts_id'])
					->where("parts_id", '=',  $parts['parts_id'])
					->update([
						'price' => $parts['price']
					]);
			}

			$matrix =  $this->ApprovalMatrixActivityLog($request->module_id, $refurbish->id);

			if ($matrix['status'] == 'error') {
				return $matrix;
			} else {
				//update the first holder of the transaction
				$save_holder = request_refurbish::where('id', $refurbish->id)->update(['approver' => $matrix['message']]);
			}

			DB::commit();

			return $this->sendResponse([], 'Request successfully save.');
		} catch (\Throwable $th) {
			return $this->sendError($th->errorInfo[2]);
		}
	}

	public function updateRefurbish(Request $request, $id)
	{

		$file_list = array();
		$validator = Validator::make($request->all(), [
			'repo_id' => 'required|numeric',
			'spares' => 'required',
		]);

		if ($validator->fails()) {
			return $this->sendError('Validation Error.', $validator->errors());
		}

		if ($request->q1 == 'null' && $request->q2 == 'null' && $request->q3 == 'null') {
			return $this->sendError('Validation Error.', 'Please upload atleast 1 Qoutation!');
		}

		try {



			DB::beginTransaction();

			$folder_path = 'image/Qoutation';
			$directory = public_path($folder_path);
			if (!File::isDirectory($directory)) {
				File::makeDirectory($directory, 0777, true, true);
			}

			if ($request->q1 != 'null') {
				$image1 = $request->file("q1");
				if ($image1) {
					$image_name1 = strtoupper(uniqid() . '-' . $image1->getClientOriginalName());
					$image1->move($directory, $image_name1);

					array_push($file_list, [
						'filename' => $image_name1,
						'path' => $folder_path . '/' . $image_name1
					]);
				}
			}

			if ($request->q2 != 'null') {
				$image1 = $request->file("q2");
				if ($image1) {
					$image_name1 = strtoupper(uniqid() . '-' . $image1->getClientOriginalName());
					$image1->move($directory, $image_name1);

					array_push($file_list, [
						'filename' => $image_name1,
						'path' => $folder_path . '/' . $image_name1
					]);
				}
			}

			if ($request->q3 != 'null') {
				$image1 = $request->file("q3");
				if ($image1) {
					$image_name1 = strtoupper(uniqid() . '-' . $image1->getClientOriginalName());
					$image1->move($directory, $image_name1);

					array_push($file_list, [
						'filename' => $image_name1,
						'path' => $folder_path . '/' . $image_name1
					]);
				}
			}

			$spares = json_decode($request->spares, true);
			foreach ($spares as $parts) {
				// $details = new refurbish_detail;
				// $details->refurbish_id = $refurbish->id;
				// $details->spare_parts = $parts['parts_id'];
				// $details->price = $parts['price'];
				// $details->save();

				DB::table("recieve_unit_spare_parts")
					->where("id", '=', $parts['received_parts_id'])
					->where("parts_id", '=',  $parts['parts_id'])
					->update([
						'price' => $parts['price']
					]);
			}

			// $refurbish = new request_refurbish;
			// $refurbish->repo_id = $request->repo_id;
			// $refurbish->branch = Auth::user()->branch;
			// $refurbish->maker = Auth::user()->id;
			// $refurbish->files_names = json_encode($file_list);
			// $refurbish->save();

			$update = request_refurbish::where('id', $id)->update(['files_names' => json_encode($file_list), 'status' => '0']);

			DB::commit();

			return $this->sendResponse([], 'Request successfully update.');
		} catch (\Throwable $th) {
			return $this->sendError($th->errorInfo[2]);
		}
	}

	public function getListForApprovalRefurbish($moduleid)
	{

		try {

			$refurbish_request = DB::table('repo_details as repo')
				// ->join('request_refurbishes as refurbish','refurbish.repo_id','repo.id')
				->join('request_refurbishes as refurbish', function ($join) {
					$join->on('repo.id', '=', 'refurbish.repo_id');
					$join->on('repo.branch_id', '=', 'refurbish.branch');
				})
				->join('branches as br', 'repo.branch_id', 'br.id')
				->join('brands as brd', 'repo.brand_id', 'brd.id')
				->join('unit_models as mdl', 'repo.model_id', 'mdl.id')
				->join('unit_colors as color', 'repo.color_id', 'color.id')
				->join('users as holder', 'refurbish.approver', 'holder.id')
				->join('users as req', 'refurbish.maker', 'req.id')
				->select(
					'refurbish.id as refurbish_id',
					'refurbish.files_names as qoute',
					'repo.id as repo_id',
					'repo.model_engine',
					'repo.model_chassis',
					'repo.date_sold',
					'br.name as branchname',
					'brd.brandname',
					'mdl.model_name',
					'color.name as color',
					DB::raw("CASE WHEN refurbish.status = '0' THEN 'WAITING FOR APPROVAL'
					WHEN refurbish.status = '1' THEN 'APPROVED' WHEN refurbish.status = '2' THEN 'DISAPPROVED' END status
				"),
					'refurbish.remarks',
					DB::raw('CONCAT(holder.firstname,holder.middlename,holder.lastname) as current_holder'),
					DB::raw('CONCAT(req.firstname,req.middlename,req.lastname) as requestor')
				);
			// ->whereIn('refurbish.status',['0','2'])
			// ->where('refurbish.approver',Auth::user()->id)
			// ->get();

			$count = 0;
			$get_approvers = approval_matrix_setting::where('module_id', $moduleid)->get();
			foreach ($get_approvers as $approvers) {

				foreach ($approvers->signatories as $approver) {

					if (Auth::user()->id == $approver['user']) {
						$count++;
					}
				}
			}

			$role = '';

			if ($count > 0) {
				$role = 'Approver';
				$refurbish_request = $refurbish_request->where('refurbish.status', '0')->where('refurbish.approver', Auth::user()->id)->get();
			} else {
				$role = 'Maker';

				$check = request_refurbish::where('maker', Auth::user()->id)->count();

				if ($check > 0) {
					$refurbish_request = $refurbish_request->where('refurbish.maker', Auth::user()->id);
				}

				$refurbish_request = $refurbish_request->whereIn('refurbish.status', ['0', '2'])->get();
			}
			$data = ['data' => $refurbish_request, 'role' =>  $role];
			return $data;
		} catch (\Throwable $th) {
			return $this->sendError($th->errorInfo[2]);
		}
	}

	public function listForRefurbishProcess($moduleid)
	{

		try {

			$refurbish_request = DB::table('repo_details as repo')
				// ->join('request_refurbishes as refurbish','refurbish.repo_id','repo.id')
				->join('request_refurbishes as refurbish', function ($join) {
					$join->on('repo.id', '=', 'refurbish.repo_id');
					$join->on('repo.branch_id', '=', 'refurbish.branch');
				})
				->join('recieve_unit_details as receive', 'repo.id', 'receive.repo_id')
				->join('branches as br', 'repo.branch_id', 'br.id')
				->join('brands as brd', 'repo.brand_id', 'brd.id')
				->join('unit_models as mdl', 'repo.model_id', 'mdl.id')
				->join('unit_colors as color', 'repo.color_id', 'color.id')
				->leftjoin('refurbish_processes as process', 'process.refurbish_req_id', 'refurbish.id')
				->leftjoin('users as holder', 'process.approver', 'holder.id')
				->leftjoin('users as req', 'process.maker', 'req.id')
				->select(
					'process.id as processid',
					'refurbish.id as refurbish_id',
					'process.files_names as qoute',
					'repo.id as repo_id',
					'repo.model_engine',
					'repo.model_chassis',
					'repo.date_sold',
					'br.name as branchname',
					'brd.brandname',
					'mdl.model_name',
					'color.name as color',
					DB::raw("CASE WHEN process.status = '0' THEN 'WAITING FOR APPROVAL'
							WHEN process.status = '1' THEN 'APPROVED' WHEN process.status = '2' THEN 'DISAPPROVED' ELSE 'Subject For Refurbishing' END status
					"),
					'process.remarks',
					DB::raw('CONCAT(holder.firstname,holder.middlename,holder.lastname) as current_holder'),
					DB::raw('CONCAT(req.firstname,req.middlename,req.lastname) as requestor'),
					'process.re_class as classification',
					'receive.id as receive_id'
				);
			// ->whereIn('refurbish.status',['0','2'])
			// ->where('refurbish.approver',Auth::user()->id)
			// ->get();

			$count = 0;
			$get_approvers = approval_matrix_setting::where('module_id', $moduleid)->get();
			foreach ($get_approvers as $approvers) {

				foreach ($approvers->signatories as $approver) {

					if (Auth::user()->id == $approver['user']) {
						$count++;
					}
				}
			}

			$role = '';

			if ($count > 0) {
				$role = 'Approver';
				$refurbish_request = $refurbish_request->where('process.status', '0')->where('process.approver', Auth::user()->id)->get();
			} else {
				$role = 'Maker';

				// $check = request_refurbish::where('maker', Auth::user()->id)->count();

				// if($check > 0){
				//     $refurbish_request = $refurbish_request->where('refurbish.maker', Auth::user()->id);
				// }

				$refurbish_request = $refurbish_request->where('refurbish.status', '3')->where('refurbish.maker', Auth::user()->id)->get();
			}
			$data = ['data' => $refurbish_request, 'role' =>  $role];
			return $data;
		} catch (\Throwable $th) {
			return $this->sendError($th->errorInfo[2]);
		}
	}

	public function refurbishDecision(Request $request)
	{

		try {

			$validator = Validator::make($request->all(), [
				'data_id' => 'required',
				'remarks' => 'required',
				'status' => 'required',
			]);

			if ($validator->fails()) {
				return $this->sendError('Validation Error.', $validator->errors());
			}
			DB::beginTransaction();
			// get the transaction id
			$data = request_refurbish::where('id', $request->data_id)->first();

			$first_approver = 0;
			$sequence = 0;
			if ($request->status == 1) {
				$fetch_sequence = $this->approverDecision($request->module_id, $data->id, Auth::user()->id);
				if ($fetch_sequence == 0) {
					request_refurbish::where('id', $request->data_id)->update(['status' => '3']);
				}
				$sequence = $fetch_sequence;
			} else if ($request->status == 2) {
				$fetch_first_approver = $this->disapprovedDecision($request->module_id, $data->id, Auth::user()->id);
				request_refurbish::where('id', $request->data_id)
					->update(['status' => $request->status, 'approver' => $fetch_first_approver]);
				$first_approver = $fetch_first_approver;
			}

			$arr = [
				'approver' => $first_approver > 0 ? $first_approver : ($sequence == 0 ? Auth::user()->id : $sequence),
				'date_approved' => $request->status == 1 ? Carbon::now() : null,
				'remarks' => $request->remarks
			];

			//remove 1st set of spare parts
			$remove = refurbish_detail::where('refurbish_id', $request->data_id)->delete();

			$spares = json_decode($request->spares, true);
			foreach ($spares as $parts) {
				// $details = new refurbish_detail;
				// $details->refurbish_id = $request->data_id;
				// $details->spare_parts = $parts['parts_id'];
				// $details->price = $parts['price'];
				// $details->save();
				DB::table("recieve_unit_spare_parts")
					->where("id", '=', $parts['received_parts_id'])
					->where("parts_id", '=',  $parts['parts_id'])
					->update([
						'price' => $parts['price']
					]);
			}

			$updateRequest = request_refurbish::where('id', $request->data_id)
				->update($arr);

			DB::commit();
			$msg = $request->status == 1 ? 'Request for refurbish approval successfully approved!' : 'Request for refurbish approval successfully disapproved!';
			return $this->sendResponse([
				'sequence' => $sequence,
				'first_approver' => $first_approver
			], $msg);
		} catch (\Throwable $th) {
			$this->rollBaclDecision($request->module_id, $data->id, Auth::user()->id);
			return $this->sendError($th->errorInfo[2]);
		}
	}

	public function getRefurbishPartsTotalCost($repoid)
	{
		$data = DB::table('request_refurbishes as a')
			->join('refurbish_details as b', 'b.refurbish_id', 'a.id')
			->select('b.*')
			->where('a.repo_id', $repoid)
			->where('a.status', '3')->get();

		$total = 0;

		for ($i = 0; $i < count($data); $i++) {
			$total += $data[$i]->price;
		}

		return $total;
	}

	public function refurbishProcessDecision(Request $request)
	{
		try {

			$validator = Validator::make($request->all(), [
				'data_id' => 'required',
				'remarks' => 'required',
				'status' => 'required',
			]);

			if ($validator->fails()) {
				return $this->sendError('Validation Error.', $validator->errors());
			}

			DB::beginTransaction();

			// get the transaction id
			$data = refurbishProcess::where('id', $request->data_id)->first();

			//first insert to decision matrix log
			// $sequence = $this->approverDecision($request->module_id, $data->id, Auth::user()->id);

			// if ($sequence == 0) {
			// 	$update = refurbishProcess::where('id', $request->data_id)->update(['status' => $request->status]);
			// 	if ($request->status == 1) {
			// 		$update_head_table = request_refurbish::where('id', $data->refurbish_req_id)->update(['status' => '3']);
			// 		$get_all_parts = DB::table('refurbish_details')->where('refurbish_id', $data->refurbish_req_id)->get();

			// 		$total = 0;

			// 		for ($i = 0; $i < count($get_all_parts); $i++) {
			// 			$total += $get_all_parts[$i]->actual_price;
			// 		}

			// 		$get_refurb = request_refurbish::select('repo_id')->where('id', $data->refurbish_req_id)->first();
			// 		$get_repo = DB::table('recieve_unit_details')->where('repo_id', $get_refurb->repo_id)->first();
			// 		$update_total_price = DB::table('recieve_unit_details')->where('repo_id', $get_repo->id)->update(['principal_balance' => ($get_repo->principal_balance + $total)]);
			// 		$reclass = DB::table('repo_details')->where('id', $get_refurb->repo_id)->update(['classification' => $data->re_class]);
			// 		$finish = DB::table('request_refurbishes')->where('id', $data->refurbish_req_id)->update(['status' => '4']);
			// 	}
			// } else {
			// 	//if not the final approver then check if status is disapproved else ignore update
			// 	if ($request->status == 2) {
			// 		$update = refurbishProcess::where('id', $request->data_id)->update(['status' => $request->status]);
			// 	}
			// }

			$first_approver = 0;
			$sequence = 0;
			if ($request->status == 1) {
				$fetch_sequence = $this->approverDecision($request->module_id, $data->id, Auth::user()->id);
				if ($fetch_sequence == 0) {
					DB::table('refurbish_processes')->where('id', $request->data_id)->update(['status' => $request->status]);

					$total = DB::table('recieve_unit_details as unit')
						->select(
							'unit.repo_id',
							'unit.principal_balance',
							DB::raw('SUM(actual_price) as total_actual_price'),
							DB::raw('(unit.principal_balance + SUM(actual_price)) AS total_principal_balance')
						)
						->join('recieve_unit_spare_parts as spare', 'unit.id', '=', 'spare.recieve_id')
						->where('spare.is_deleted', '=', 0)
						->where('spare.refurb_decision', '=', 'done')
						->where('unit.repo_id', '=', $request->repo_id)
						->groupBy('unit.repo_id', 'unit.principal_balance')
						->first();

					// DB::table('recieve_unit_details')->where('repo_id', $request->repo_id)->update(['principal_balance' => $total->total_principal_balance]);
					DB::table('repo_details')->where('id', $request->repo_id)->update(['classification' => $data->re_class]);
					DB::table('request_refurbishes')->where('id', $data->refurbish_req_id)->update(['status' => '4']);
				}
				$sequence = $fetch_sequence;
			} else if ($request->status == 2) {
				$fetch_first_approver = $this->disapprovedDecision($request->module_id, $data->id, Auth::user()->id);
				refurbishProcess::where('id', $request->data_id)
					->update(['status' => $request->status, 'approver' => $fetch_first_approver]);
				$first_approver = $fetch_first_approver;
			}

			$arr = [
				'approver' => $first_approver > 0 ? $first_approver : ($sequence == 0 ? Auth::user()->id : $sequence),
				'updated_at' => Carbon::now(),
				'remarks' => $request->remarks
			];
			refurbishProcess::where('id', $request->data_id)->update($arr);

			DB::commit();
			$msg = $request->status == 1 ? 'Request for refurbish process successfully approved!' : 'Request for refurbish process successfully disapproved!';
			return $this->sendResponse([], $msg);
		} catch (\Throwable $th) {
			$this->rollBaclDecision($request->module_id, $data->id, Auth::user()->id);
			return $this->sendError($th->errorInfo[2]);
		}
	}

	public function refurbishUnitList()
	{
		try {

			$received_units = DB::table('repo_details as repo')
				->join('branches as br', 'repo.branch_id', 'br.id')
				->join('brands as brd', 'repo.brand_id', 'brd.id')
				->join('unit_models as mdl', 'repo.model_id', 'mdl.id')
				->join('unit_colors as color', 'repo.color_id', 'color.id')
				->join('customer_profile as old_owner', 'repo.customer_acumatica_id', 'old_owner.id')
				->join('request_refurbishes as refurbish', 'repo.id', 'refurbish.repo_id')
				->select(
					'refurbish.id',
					'repo.id as repo_id',
					'repo.model_engine',
					'repo.model_chassis',
					'br.name as branchname',
					'brd.brandname',
					'mdl.model_name',
					'color.name as color',
					'old_owner.firstname as o_firstname',
					'old_owner.middlename as o_middlename',
					'old_owner.lastname as o_lastname',
					DB::raw('CONVERT(DATE,refurbish.created_at) AS date_req'),
					DB::raw("CASE WHEN refurbish.status = '0' THEN 'PENDING'
			WHEN refurbish.status = '1' THEN 'APPROVED'
			WHEN refurbish.status = '3' THEN 'ON GOING REFURBISH'
			WHEN refurbish.status = '4' THEN 'DONE'
			ELSE 'DISAPPROVED' END status"),
				);

			if (Auth::user()->userrole == 'Warehouse Custodian') {
				$received_units = $received_units->where('refurbish.branch', Auth::user()->branch)->get();
			} else {
				$received_units = $received_units->get();
			}


			return $received_units;
		} catch (\Throwable $th) {
			return $this->sendError($th->errorInfo[2]);
		}
	}
}
