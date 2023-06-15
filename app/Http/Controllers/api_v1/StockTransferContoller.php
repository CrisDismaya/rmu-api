<?php

namespace App\Http\Controllers\api_v1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\api_v1\BaseController as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\branch;
use App\Models\repo;
use App\Models\receive_unit;
use App\Models\FilesUploaded;
use App\Models\stock_transfer;
use App\Models\stock_transfer_units;
use App\Http\Traits\helper;

class StockTransferContoller extends BaseController
{
	//
	use helper;
	
	public function branchesList(){
		return branch::whereNotIn('id', [ Auth::user()->branch ])->get();
	}
	
	public function ModelList(){
		return DB::select("SELECT 
				rud.id, brd.brandname, rep.model_engine, rep.model_chassis, clr.name AS color_name, 
				rep.plate_number, UPPER(mdl.model_name) AS model_name
			FROM recieve_unit_details AS rud
			INNER JOIN repo_details AS rep ON rud.repo_id = rep.id
			LEFT JOIN brands AS brd ON rep.brand_id = brd.id
			LEFT JOIN unit_models AS mdl ON rep.model_id = mdl.id
			LEFT JOIN unit_colors AS clr ON rep.color_id = clr.id
			WHERE UPPER(rud.is_sold) = 'N' AND rud.status = '0'
			AND rud.branch = ?
			AND NOT EXISTS (
				SELECT sta.from_branch, stu.recieved_unit_id
				FROM stock_transfer_approval sta
				INNER JOIN stock_transfer_unit stu ON sta.id = stu.stock_transfer_id
				WHERE sta.from_branch = rud.branch AND stu.recieved_unit_id = rud.id
			)", array(Auth::user()->branch)
		);
	}

	public function getAllForApprovals(){
		$list = DB::table('stock_transfer_approval as sta')
			->leftJoin('users as usr', 'sta.created_by', '=', 'usr.id')
			->select(
				'sta.id', 'sta.reference_code',
				DB::raw("(SELECT NAME FROM branches WHERE id = sta.from_branch) AS from_branch"),
				DB::raw("(SELECT NAME FROM branches WHERE id = sta.to_branch) AS to_branch"),
				'sta.reference_code', 'sta.approver AS approver_id',
				DB::raw("(SELECT CONCAT(firstname,' ',lastname) FROM users WHERE id = sta.approver) AS approver_name"),
				DB::raw("CONCAT(usr.firstname,' ',usr.lastname) AS created_by"),
				DB::raw("(SELECT COUNT(recieved_unit_id) FROM stock_transfer_unit WHERE stock_transfer_id = sta.id) AS transfer_units_count"),
				DB::raw("CASE WHEN sta.remarks IS NULL THEN '' ELSE sta.remarks END AS remarks"),
				'usr.userrole', 'sta.status AS status_id',
				DB::raw("CASE WHEN sta.status = '0' THEN 'Pending' WHEN sta.status = '1' THEN 'Approved' WHEN sta.status = '2' THEN 'Disapproved' ELSE '' END AS approval_status")
			);

			if(Auth::user()->userrole == 'Verifier' || Auth::user()->userrole == 'General Manager'){
				$list = $list->where('sta.approver', Auth::user()->id)->orderBy('sta.id', 'desc')->get();
			}
			else if(Auth::user()->userrole == 'Warehouse Custodian'){
				$list = $list->where('sta.from_branch', Auth::user()->branch)->orderBy('sta.id', 'desc')->get();
			}
			else {
				$list = $list->orderBy('sta.id', 'desc')->get();
			}

		return response()->json([ 'data' => $list, 'userrole' => Auth::user()->userrole]);
	}

	function getTransferUnits($id){
		// return DB::select('CALL get_transfer_unit_details(?)', array($id));
		return DB::select("SELECT 
				rud.id, brd.brandname, rep.model_engine, rep.model_chassis, clr.name AS color_name, 
				rep.plate_number, UPPER(mdl.model_name) AS model_name,
				rep.date_sold, lst.date_surrender, DATEDIFF(day, rep.date_sold, lst.date_surrender) AS aging_unit_days
			FROM stock_transfer_approval sta 
			INNER JOIN stock_transfer_unit stu ON sta.id = stu.stock_transfer_id
			INNER JOIN recieve_unit_details rud ON stu.recieved_unit_id = rud.id
			INNER JOIN repo_details AS rep ON rud.repo_id = rep.id
			LEFT JOIN (
				SELECT 
					MAX(brand_id) AS brand_id, MAX(model_id) AS model_id, MAX(date_surrender) AS date_surrender
				FROM repo_details
				GROUP BY brand_id, model_id
			) lst ON rep.brand_id = lst.brand_id AND rep.model_id = lst.model_id
			LEFT JOIN brands AS brd ON rep.brand_id = brd.id
			LEFT JOIN unit_models AS mdl ON rep.model_id = mdl.id
			LEFT JOIN unit_colors AS clr ON rep.color_id = clr.id
			WHERE sta.id = ?",
			array( $id )
		);
	}

	public function createStockTransfer(Request $request){
		$validator = Validator::make($request->all(), [
			'module_id' => 'required',
			'transfer_to_branch' => 'required|numeric',
			'list_of_transfer' => 'required',
			'reason_for_transfer' => 'required'
		]);
	
		if ($validator->fails()) {
			return $this->sendError('Validation Error.', $validator->errors()); 
		}
		
		$stock_format = [
			'from_branch' => Auth::user()->branch,
			'to_branch' => $request->transfer_to_branch,
			'created_by' => Auth::user()->id,
			'reason_for_transfer' => $request->reason_for_transfer,
		];
		$stock = stock_transfer::create($stock_format);
		$rec_id = $stock->id;

		stock_transfer::where('id', $rec_id)->update(['reference_code' => DB::raw("CONCAT('ST', RIGHT('000000' + CAST($rec_id AS VARCHAR), 6))") ]);

		$arr_of_units = json_decode($request->list_of_transfer, true);
		
		foreach ($arr_of_units as $val) {
			$units_format = [
				'stock_transfer_id' => $rec_id,
		  		'recieved_unit_id' => $val
			];
			stock_transfer_units::create($units_format);
		}

		$matrix =  $this->ApprovalMatrixActivityLog($request->module_id, $rec_id);

		if($matrix['status'] == 'error'){
			return $matrix;
		}else{
			//update the first holder of the transaction
			stock_transfer::where('id', $rec_id)->update(['approver' => $matrix['message']]);
		}

		return $this->sendResponse([], 'Stock Transfer Successfully Saved');
	}

	public function submitApproverDecision(Request $request){
		$validator = Validator::make($request->all(), [
			'id' => 'required|numeric',
			'status' => 'required|numeric',
			'module_id' => 'required|numeric',
			'remarks' => 'nullable'
		]);
	
		if ($validator->fails()) {
			return $this->sendError('Validation Error.', $validator->errors()); 
		}

		// Get the next approver in approval matrix
		$sequence = $this->approverDecision($request->module_id, $request->id, Auth::user()->id);

		DB::beginTransaction();

		// If the return of $sequence is 0 it means is the last step
		if($sequence == 0){
			stock_transfer::where('id', $request->id)->update([ 'status' => $request->status ]);

			if($request->status == 1){
				
				stock_transfer::where('id', $request->id)->update([ 'status' => $request->status ]);
			}
	  	}
		else {
			// If not the final approver then check if status is disapproved else ignore update
			if($request->status == 2){
				stock_transfer::where('id', $request->id)->update([ 'status' => $request->status ]);
			}
	 	}

		stock_transfer::where('id', $request->id)
		->update([
			'approver' => $sequence == 0 ? Auth::user()->id : ($request->status == 2 ? Auth::user()->id : $sequence),
			'date_approved' => Carbon::now(),
			'remarks' => (($sequence == 0 || $request->status == 2) ? $request->remarks : null),
		]);

		DB::commit();

		$msg = $request->status == 1 ? 'Request for approval successfully approved!' : 'Request for approval successfully disapproved!';
		return $this->sendResponse([], $msg);
	}

	function getAllReceiveStockTransfer(){
		return DB::select("SELECT 
				sta.reference_code, brh.name AS branch_name, CONCAT(cus.firstname,' ',cus.lastname) AS customer_name,
				brd.brandname, mdl.model_name, UPPER(rep.model_engine) AS engine, UPPER(rep.model_chassis) AS chassis,
				rep.id as repo_id, stu.id AS stk_unit_id, rud.id AS receive_id, sta.id AS stk_prv_id,
				CASE WHEN stu.is_received = 0 AND stu.is_use_old_files = 0 THEN 'NO DECISION' ELSE 'WITH DECISION' END  AS received_status,
				stu.is_received, stu.is_use_old_files
			FROM stock_transfer_approval sta
			INNER JOIN stock_transfer_unit stu ON sta.id = stu.stock_transfer_id
			INNER JOIN recieve_unit_details rud ON stu.recieved_unit_id = rud.id
			INNER JOIN repo_details rep ON rud.repo_id = rep.id
			LEFT JOIN customer_profile cus ON rep.customer_acumatica_id = cus.id
			LEFT JOIN branches brh ON sta.from_branch = brh.id
			LEFT JOIN brands brd ON rep.brand_id = brd.id
			LEFT JOIN unit_models mdl ON rep.model_id = mdl.id
			WHERE sta.status = 1 AND stu.is_received = 0 AND sta.to_branch = ?",
			array( Auth::user()->branch )
		);
	}

	function getAllFileUploaded(Request $request){
		$ref = DB::select("SELECT 
				rud.id AS received_id, rud.repo_id
			FROM stock_transfer_approval sta
			INNER JOIN stock_transfer_unit stu ON sta.id = stu.stock_transfer_id
			LEFT JOIN recieve_unit_details rud ON stu.recieved_unit_id = rud.id
			WHERE sta.id = ? AND stu.recieved_unit_id = ?",
			array( $request->stock_transfer_approval_id, $request->received_id )
		);
		
		$uploaded_files = DB::select("SELECT *
			FROM (
				SELECT path, files_name, 'repo' AS module
				FROM files_uploaded
				WHERE module_id = (
					SELECT id FROM system_menu WHERE file_path = '_repo-create.php'
				) AND is_deleted = 0 AND reference_id = ?
				UNION ALL
				SELECT path, files_name, 'received' AS module
				FROM files_uploaded
				WHERE module_id = (
					SELECT id FROM system_menu WHERE file_path = '_receiving-of-units.php'
				) AND is_deleted = 0 AND reference_id = ?
			) sub",
			array($ref[0]->repo_id, $ref[0]->received_id)
		);

		return $uploaded_files;
	}
	
	function receivedDesicion(Request $request){
		stock_transfer_units::where('id', '=', $request->stock_transfer_unit_id)
			->where('recieved_unit_id', '=', $request->receive_id)
			->update(['is_received' => '1', 'is_use_old_files' => $request->decision_id]);

		$repo = receive_unit::where('id', '=', $request->receive_id)->first();
		repo::where('id', '=', $repo->repo_id)->update([ 'branch_id' => Auth::user()->branch, 'transfer_branch_id' => $request->stock_transfer_unit_id ]);
		
		return $this->sendResponse([], 'Received Unit for Stock Transfer Successfully');
	}

	function getTransferredUnits(){
		return DB::select("WITH 
			receives AS (
				SELECT 
					ROW_NUMBER() OVER ( PARTITION BY rud.repo_id ORDER BY rud.repo_id ) AS row_num,
					rud.id AS origin_id
				FROM recieve_unit_details rud
				LEFT JOIN stock_transfer_unit stu ON rud.id = stu.recieved_unit_id AND stu.is_received = 1
			)
		
			SELECT reps.*, ruds.id AS origin,
				compareid_to = (SELECT origin_id FROM receives rev WHERE rev.row_num = revs.row_num - 1)
			FROM receives revs
			INNER JOIN recieve_unit_details ruds ON revs.origin_id = ruds.id
			LEFT JOIN (
				SELECT 
					rep.id AS reps_id, cus.acumatica_id, UPPER(CONCAT(cus.firstname,' ',cus.middlename,' ',cus.lastname)) AS customer_name,
					brd.brandname, UPPER(mdl.model_name) AS model_name, rep.model_engine, rep.model_chassis, clr.name AS color_name, rep.plate_number
				FROM repo_details rep
				LEFT JOIN customer_profile cus ON rep.customer_acumatica_id = cus.id
				LEFT JOIN brands brd ON rep.brand_id = brd.id
				LEFT JOIN unit_models mdl ON rep.model_id = mdl.id
				LEFT JOIN unit_colors clr ON rep.color_id = clr.id
			) reps ON ruds.repo_id = reps.reps_id
			INNER JOIN stock_transfer_unit stus ON ruds.id = stus.recieved_unit_id
			INNER JOIN stock_transfer_approval stas ON stus.stock_transfer_id = stas.id
			WHERE stus.is_received = 1 AND stas.to_branch = ?",
			array( Auth::user()->branch )
		);
	}

	function getComparisionSpareParts(Request $request){
		$new = $this->get_comparison_list($request->origin);
		$old = $this->get_comparison_list($request->compare_to);

		return $this->sendResponse([ 'new_list' => $new, 'old_list' => $old ], '');
	}

	function get_comparison_list($id){
		return DB::select("SELECT 
				rups.*, prts.inventory_code, prts.name
			FROM recieve_unit_details ruds
			INNER JOIN recieve_unit_spare_parts rups ON ruds.id = rups.recieve_id AND rups.is_deleted = 0
			LEFT JOIN spare_parts prts ON rups.parts_id = prts.id
			WHERE ruds.id = ?",
			array($id)
		);
	}
}
