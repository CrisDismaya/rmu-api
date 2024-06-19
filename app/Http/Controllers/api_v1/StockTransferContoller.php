<?php

namespace App\Http\Controllers\api_v1;

use App\Http\Controllers\api_v1\BaseController as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\branch;
use App\Models\repo;
use App\Models\receive_unit;
use App\Models\FilesUploaded;
use App\Models\stock_transfer;
use App\Models\stock_transfer_units;
use App\Models\approval_matrix_setting;
use App\Http\Traits\helper;
use Yajra\Datatables\Datatables;

class StockTransferContoller extends BaseController
{
	//
	use helper;

	public function branchesList()
	{
		try {
			return DB::select(
				"SELECT DISTINCT
					brh.id,
					brh.name
				FROM branches AS brh
				INNER JOIN users AS usr ON brh.id = usr.branch
				WHERE brh.status = 1
				AND usr.userrole = 'Warehouse Custodian'
				AND brh.id NOT IN (:branchid)",
				['branchid' => Auth::user()->branch]
			);
		} catch (\Throwable $th) {
			return $this->sendError($th->errorInfo[2]);
		}
	}

	public function ModelList()
	{
		try {
			return DB::select(
				"SELECT
						rud.id, brd.brandname, rep.model_engine, rep.model_chassis, clr.name AS color_name,
						rep.plate_number, UPPER(mdl.model_name) AS model_name
					FROM repo_details rep
					INNER JOIN recieve_unit_details rud ON rep.id = rud.repo_id AND rep.branch_id = rud.branch
					LEFT JOIN brands AS brd ON rep.brand_id = brd.id
					LEFT JOIN unit_models AS mdl ON rep.model_id = mdl.id
					LEFT JOIN unit_colors AS clr ON rep.color_id = clr.id
					LEFT JOIN (
						SELECT
							sub.approvalid, sub.recievedid, sta1.status AS approvalstatus,
							CASE WHEN sta1.status = 1 THEN sta1.to_branch WHEN sta1.status = 2 THEN sta1.from_branch END AS current_branch
						FROM (
							SELECT MAX(sta.id) AS approvalid, MAX(stu.recieved_unit_id) AS recievedid
							FROM stock_transfer_approval sta
							INNER JOIN stock_transfer_unit stu ON sta.id = stu.stock_transfer_id
							GROUP BY stu.recieved_unit_id
						) sub
						INNER JOIN stock_transfer_approval sta1 ON sub.approvalid = sta1.id
					) app ON rud.id = app.recievedid
					LEFT JOIN sold_units sld ON rep.id = sld.repo_id AND rep.branch_id = sld.branch
					LEFT JOIN request_refurbishes ref ON rep.id = ref.repo_id AND rep.branch_id = ref.branch
					LEFT JOIN (
						SELECT
							repo.id AS repo_id, COUNT(upload.id) AS total_upload_required_files
						FROM repo_details repo
						LEFT JOIN files_uploaded upload ON repo.id = upload.reference_id AND repo.branch_id = upload.branch_id
						INNER JOIN (
							SELECT * FROM files WHERE isRequired = 1 AND status = 1
						) files ON upload.files_id = files.id
						WHERE upload.is_deleted = 0
						GROUP BY repo.id, upload.branch_id
					) files ON files.repo_id = rep.id
					WHERE rud.status NOT IN (4) AND UPPER(rud.is_sold) = 'N' AND rep.branch_id = ?
					AND (app.current_branch IS NULL OR app.current_branch = rep.branch_id)
					AND (app.approvalstatus IS NULL OR app.approvalstatus IN (1, 2))
					AND (sld.id IS NULL OR sld.status IN (2))
					AND (ref.id IS NULL OR ref.status IN (2, 3, 4))
					AND ISNULL(files.total_upload_required_files, 0) = (SELECT COUNT(*) FROM files WHERE isRequired = 1 AND status = 1)
				",
				array(Auth::user()->branch)
			);
		} catch (\Throwable $th) {
			return $this->sendError($th->errorInfo[2]);
		}
	}

	public function getAllForApprovals($moduleid)
	{
		try {
			$list = DB::table('stock_transfer_approval as sta')
				->leftJoin('users as usr', 'sta.created_by', '=', 'usr.id')
				->select(
					'sta.id',
					'sta.reference_code',
					DB::raw("(SELECT NAME FROM branches WHERE id = sta.from_branch) AS from_branch"),
					DB::raw("(SELECT NAME FROM branches WHERE id = sta.to_branch) AS to_branch"),
					'sta.reference_code',
					'sta.approver AS approver_id',
					DB::raw("(SELECT CONCAT(firstname,' ',lastname) FROM users WHERE id = sta.approver) AS approver_name"),
					DB::raw("CONCAT(usr.firstname,' ',usr.lastname) AS created_by"),
					DB::raw("(SELECT COUNT(recieved_unit_id) FROM stock_transfer_unit WHERE stock_transfer_id = sta.id) AS transfer_units_count"),
					DB::raw("CASE WHEN sta.remarks IS NULL THEN '' ELSE sta.remarks END AS remarks"),
					'usr.userrole',
					'sta.status AS status_id',
					DB::raw("CASE WHEN sta.status = '0' THEN 'Pending' WHEN sta.status = '1' THEN 'Approved' WHEN sta.status = '2' THEN 'Disapproved' ELSE '' END AS approval_status")
				);

			if (Auth::user()->userrole == 'Verifier' || Auth::user()->userrole == 'General Manager') {
				$stmt = $list->where('sta.approver', Auth::user()->id)->orderBy('sta.id', 'desc')->get();
			}
            else if (Auth::user()->userrole == 'Warehouse Custodian') {
				$stmt = $list->where('sta.from_branch', Auth::user()->branch)->orderBy('sta.id', 'desc')->get();
			}
            else {
				$stmt = $list->orderBy('sta.id', 'desc')->get();
			}

            $datatables = Datatables::of($stmt);
            return $datatables->make(true);

		} catch (\Throwable $th) {
			return $this->sendError($th->errorInfo[2]);
		}
	}

	function getTransferUnits($id)
	{
		try {
			return DB::select(
				"SELECT
					rud.id, brd.brandname, rep.model_engine, rep.model_chassis, clr.name AS color_name,
					rep.plate_number, UPPER(mdl.model_name) AS model_name,
					rep.date_sold, lst.date_surrender, DATEDIFF(day, rep.date_sold, lst.date_surrender) AS aging_unit_days,
					rep.id AS repo_id
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
				array($id)
			);
		} catch (\Throwable $th) {
			return $this->sendError($th->errorInfo[2]);
		}
	}

	public function createStockTransfer(Request $request)
	{
		try {
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

			DB::beginTransaction();

			$stock = stock_transfer::create($stock_format);
			$rec_id = $stock->id;

			stock_transfer::where('id', $rec_id)->update(['reference_code' => DB::raw("CONCAT('ST', RIGHT('000000' + CAST($rec_id AS VARCHAR), 6))")]);

			$arr_of_units = json_decode($request->list_of_transfer, true);

			foreach ($arr_of_units as $val) {
				$units_format = [
					'stock_transfer_id' => $rec_id,
					'recieved_unit_id' => $val
				];
				stock_transfer_units::create($units_format);
			}

			$matrix =  $this->ApprovalMatrixActivityLog($request->module_id, $rec_id);

			if ($matrix['status'] == 'error') {
				return $matrix;
			} else {
				//update the first holder of the transaction
				stock_transfer::where('id', $rec_id)->update(['approver' => $matrix['message']]);
			}
			DB::commit();

			return $this->sendResponse([], 'Stock Transfer Successfully Saved');
		} catch (\Throwable $th) {
			return $this->sendError($th->errorInfo[2]);
		}
	}

	public function submitApproverDecision(Request $request)
	{
		try {
			$validator = Validator::make($request->all(), [
				'id' => 'required|numeric',
				'status' => 'required|numeric',
				'module_id' => 'required|numeric',
				'remarks' => 'nullable'
			]);

			if ($validator->fails()) {
				return $this->sendError('Validation Error.', $validator->errors());
			}
			DB::beginTransaction();

			$first_approver = 0;
			$sequence = 0;
			if ($request->status == 1) {
				$fetch_sequence = $this->approverDecision($request->module_id, $request->id, Auth::user()->id);
				if ($fetch_sequence == 0) {
					stock_transfer::where('id', $request->id)->update(['status' => $request->status]);
				}
				$sequence = $fetch_sequence;
			} else if ($request->status == 2) {
				$fetch_first_approver = $this->disapprovedDecision($request->module_id, $request->id, Auth::user()->id);
				stock_transfer::where('id', $request->id)
					->update(['status' => $request->status, 'approver' => $fetch_first_approver]);
				$first_approver = $fetch_first_approver;
			}

			stock_transfer::where('id', $request->id)
				->update([
					'approver' => $first_approver > 0 ? $first_approver : ($sequence == 0 ? Auth::user()->id : $sequence),
					'date_approved' => Carbon::now(),
					'remarks' => (($sequence == 0 || $request->status == 2) ? $request->remarks : null),
				]);

			if ($request->status == 2) {
				stock_transfer_units::where('stock_transfer_id', $request->id)->update(['is_received' => 9, 'is_use_old_files' => 9]);
			}

			DB::commit();

			$msg = $request->status == 1 ? 'Request for approval successfully approved!' : 'Request for approval successfully disapproved!';
			return $this->sendResponse([], $msg);
		} catch (\Throwable $th) {
			$this->rollBaclDecision($request->module_id, $request->id, Auth::user()->id);
			return $this->sendError($th->errorInfo[2]);
		}
	}

	function getAllReceiveStockTransfer()
	{
		try {
			return DB::select(
				"SELECT
					sta.reference_code, brh.name AS branch_name, CONCAT(cus.firstname,' ',cus.lastname) AS customer_name,
					brd.brandname, mdl.model_name, UPPER(rep.model_engine) AS engine, UPPER(rep.model_chassis) AS chassis,
					CASE WHEN stu.is_received = 0 AND stu.is_use_old_files = 0 THEN 'NO DECISION' ELSE 'WITH DECISION' END  AS received_status,
					rep.id as repo_id, sta.id AS stock_approval_id, stu.id AS stock_unit_id,
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
				array(Auth::user()->branch)
			);
		} catch (\Throwable $th) {
			return $this->sendError($th->errorInfo[2]);
		}
	}

	function getAllFileUploaded(Request $request)
	{
		try {
			$response = [];
			$files_info = DB::select(
				"SELECT * FROM (
					SELECT
						MIN(id) AS min_id, MAX(id) AS max_id, branch_id, reference_id AS repo_id, CAST(created_at AS DATE) AS dates
					FROM files_uploaded
					GROUP BY branch_id, reference_id, CAST(created_at AS DATE)
				) AS sub
				WHERE sub.repo_id = :repoid
				ORDER BY min_id DESC",
				['repoid' => $request->repoid]
			);

			foreach ($files_info as $item) {
				$images = DB::select(
					"SELECT * FROM files_uploaded WHERE id >= :min_id AND id <= :max_id",
					['min_id' => $item->min_id, 'max_id' => $item->max_id]
				);
				array_push($response, $images);
			}
			return $response;
		} catch (\Throwable $th) {
			return $this->sendError($th->errorInfo[2]);
		}
	}

	function receivedDesicion(Request $request)
	{
		try {
			$repo = repo::where('id', '=', $request->repoid)->first();
			$receive = receive_unit::where('repo_id', '=', $repo->id)->where('branch', '=', $repo->branch_id)->first();
			$pictures = FilesUploaded::where('reference_id', '=', $repo->id)->where('is_deleted', '=', 0)->get();

			DB::beginTransaction();

			stock_transfer_units::where('id', '=', $request->unitid)->update(['is_received' => '1', 'is_use_old_files' => $request->decisionid]);
			repo::where('id', '=', $repo->id)->update(['branch_id' => Auth::user()->branch, 'transfer_branch_id' => $request->unitid]);
			receive_unit::where('id', '=', $receive->id)->update(['branch' => Auth::user()->branch, 'status' => '0']);

			// 1 = Use Previous Images / 2 = Upload New Images
			foreach ($pictures as $pics) {
				if ($request->decisionid == 1) {
					$format = [
						'module_id' => $pics['module_id'],
						'branch_id' => Auth::user()->branch,
						'reference_id' => $pics['reference_id'],
						'files_id' => $pics['files_id'],
						'files_name' => $pics['files_name'],
						'path' => $pics['path'],
					];
					FilesUploaded::create($format);
				}
				FilesUploaded::where('id', '=', $pics['id'])->update(['is_deleted' => 1]);
			}

			DB::commit();

			return $this->sendResponse([], 'Received Unit for Stock Transfer Successfully');
		} catch (\Throwable $th) {
			return $this->sendError($th->errorInfo[2]);
		}
	}

	function getTransferredUnits()
	{
		try {
		    $stmt = DB::select(
				"WITH
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
				array(Auth::user()->branch)
			);

            $datatables = Datatables::of($stmt);
            return $datatables->make(true);
		} catch (\Throwable $th) {
			return $this->sendError($th->errorInfo[2]);
		}
	}

	function getComparisionSpareParts(Request $request)
	{
		try {
			$new = $this->get_comparison_list($request->origin);
			$old = $this->get_comparison_list($request->compare_to);

			return $this->sendResponse(['new_list' => $new, 'old_list' => $old], '');
		} catch (\Throwable $th) {
			return $this->sendError($th->errorInfo[2]);
		}
	}

	function get_comparison_list($id)
	{
		try {
			return DB::select(
				"SELECT
					rups.*, prts.inventory_code, prts.name
				FROM recieve_unit_details ruds
				INNER JOIN recieve_unit_spare_parts rups ON ruds.id = rups.recieve_id AND rups.is_deleted = 0
				LEFT JOIN spare_parts prts ON rups.parts_id = prts.id
				WHERE ruds.id = ?",
				array($id)
			);
		} catch (\Throwable $th) {
			return $this->sendError($th->errorInfo[2]);
		}
	}

	function fetch_stock_transfer_approved()
	{
		try {
			$query = DB::table('stock_transfer_approval as sta')
				->join('stock_transfer_unit as stu', 'sta.id', '=', 'stu.stock_transfer_id')
				->leftJoin('recieve_unit_details as rud', 'stu.recieved_unit_id', '=', 'rud.id')
				->leftJoin('repo_details as rep', 'rud.repo_id', '=', 'rep.id')
				->leftJoin('brands as brd', 'rep.brand_id', '=', 'brd.id')
				->leftJoin('unit_models as mdl', 'rep.model_id', '=', 'mdl.id')
				->leftJoin('unit_colors as clr', 'rep.color_id', '=', 'clr.id')
				->select([
					'sta.reference_code', 'rep.id As repo_id',
					'brd.brandname',
					'mdl.model_name AS model',
					DB::raw('origin = (SELECT name FROM branches WHERE id = sta.from_branch)'),
					DB::raw('receiver = (SELECT name FROM branches WHERE id = sta.to_branch)'),
					DB::raw('UPPER(rep.model_engine) AS engine'),
					DB::raw('UPPER(rep.model_chassis) AS chassis'),
					DB::raw('UPPER(rep.plate_number) AS plate'),
					'clr.name as color',
					DB::raw('sta.date_approved'),
					DB::raw("CASE WHEN sta.status = '0' THEN 'Pending' WHEN sta.status = '1' THEN 'Approved' WHEN sta.status = '2' THEN 'Disapproved' ELSE '' END AS approval_status")
				]);

			if (Auth::user()->userrole == 'Warehouse Custodian') {
				$list = $query->where('sta.from_branch', '=', Auth::user()->branch)->get();
			} else {
				$list = $query->get();
			}

			return $list;
		} catch (\Throwable $th) {
			return $this->sendError($th->errorInfo[2]);
		}
	}
}
