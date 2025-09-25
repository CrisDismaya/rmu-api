<?php

namespace App\Http\Controllers\api_v1;

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
use App\Http\Traits\helper;
use App\Http\Traits\ResuableQuery;
use Illuminate\Support\Carbon;
use Yajra\DataTables\Facades\DataTables;
use App\Http\Traits\TransactionNumberGenerator;
use Illuminate\Support\Facades\Log;

class RepoController extends BaseController
{

	use helper, ResuableQuery, TransactionNumberGenerator;

	public function createRepo(Request $request)
	{
		try{
			$validator = Validator::make($request->all(), [
				'repo_id' => 'required',
				'customer_acumatica_id' => 'required',
				'brand_id' => 'required',
				'model_id' => 'required',
				'model_engine' => 'required',
				'model_chassis' => 'required',
				'color_id' => 'required',
				'plate_number' => 'nullable',
				'mv_file_number' => 'nullable',
				'year_model' => 'required',
				'orcr_status' => 'required',
				'original_owner' => 'required',
				'original_owner_id' => 'required',
				'unit_documents' => 'required',
				'date_sold' => 'required',
				'date_surrender' => 'required',
				'original_srp' => 'required',
				'unit_loan_amount' => 'required',
				'unit_principal_balance' => 'required',
				'unit_total_payment' => 'required',
				'last_payment' => 'nullable',
				'loan_number' => 'required',
				'odo_meter' => 'required',
				'location' => 'required',
				'times_repossessed' => 'required',
				'repossessed_exowner' => ($request->times_repossessed > 1 ? 'required' : 'nullable'),
				'apprehension' => 'required',
				'apprehension_description' => ($request->apprehension == 'yes' ? 'required' : 'nullable'),
				'apprehension_summary' => ($request->apprehension == 'yes' ? 'required' : 'nullable'),

				'certify_no_missing_and_damaged_parts' => 'required',
				'append_count' => 'required',
				'module_id' => 'required',

				'image_fetch_id_*' => 'nullable',
				'image_*' => 'nullable',
				'image_id_*' => 'nullable',
				'image_name_*' => 'nullable',

				'spare_parts_id_*' => ($request->certify_no_missing_and_damaged_parts == 'true' ? 'required' : 'nullable'),
				'spare_parts_status_*' => ($request->certify_no_missing_and_damaged_parts == 'true' ? 'required' : 'nullable'),
				'spare_parts_price_*' => ($request->certify_no_missing_and_damaged_parts == 'true' ? 'required' : 'nullable'),
				'spare_parts_proof_*' => ($request->certify_no_missing_and_damaged_parts == 'true' ? 'required' : 'nullable'),
				'spare_parts_remarks_*' => ($request->certify_no_missing_and_damaged_parts == 'true' ? 'required' : 'nullable'),
				'spare_parts_count' => ($request->certify_no_missing_and_damaged_parts == 'true' ? 'required' : 'nullable'),
			]);

			if ($validator->fails()) {
				return $this->sendError('Validation Error.', $validator->errors());
			}

			$checker = 	DB::table('repo_details as rep')
				->join('recieve_unit_details as rud', 'rep.id', '=', 'rud.repo_id')
				->whereRaw('UPPER(rep.model_engine) = UPPER(?)', [$request->model_engine])
				->whereRaw('UPPER(rep.model_chassis) = UPPER(?)', [$request->model_chassis])
				->groupBy('rud.is_sold')
				->select(DB::raw('count(rep.id) as isExist'), 'rud.is_sold')
				->first();

			if (!empty($checker) && $checker->isExist > 0 && $checker->is_sold == 'N') {
				return $this->sendError([], 'Unit already exists');
			}
			else {
				DB::beginTransaction();

				$repo = repo::create([
					'branch_id' => Auth::user()->branch,
					'customer_acumatica_id' => $request->customer_acumatica_id,
					'brand_id' => $request->brand_id,
					'model_id' => $request->model_id,
					'model_engine' => $request->model_engine,
					'model_chassis' => $request->model_chassis,
					'color_id' => $request->color_id,
					'plate_number' => $request->plate_number,
					'mv_file_number' => $request->mv_file_number,
					'year_model' => $request->year_model,
					'orcr_status' => $request->orcr_status,
					'unit_documents' => $request->unit_documents,
					'date_sold' => $request->date_sold,
					'date_surrender' => $request->date_surrender,
					'original_srp' => $request->original_srp,
                    'last_payment' => $request->last_payment,
					'loan_number' => $request->loan_number,
					'odo_meter' => $request->odo_meter,
					'location' => $request->location,
					'times_repossessed' => $request->times_repossessed,
					'repossessed_exowner' => $request->repossessed_exowner,
                    'apprehension' => $request->apprehension,
                    'apprehension_description' => $request->apprehension_description,
                    'apprehension_summary' => $request->apprehension_summary,
				]);
				$latestInsertedId = $repo->id;

				$repo->msuisva_form_no = date("Y")."-".str_pad($latestInsertedId, (strlen($latestInsertedId) > 5 ? strlen($latestInsertedId) + 1 : 5), '0', STR_PAD_LEFT);
                $repo->transaction_number_inventory_in = $this->generateTransactionNumber('inventory_in', null, $repo->craeted_at);
                $repo->save();

				$path = 'image/unit_received/' . strtoupper($request->model_engine . '-' . $request->model_chassis);
				$directory = public_path($path);
				if (!File::isDirectory($directory)) {
					File::makeDirectory($directory, 0777, true, true);
				}

				for ($i = 1; $i <= $request->append_count; $i++) {
					$image = $request->file("image_{$i}");
					if ($image) {
						$image_name = strtoupper(uniqid()) . '_' . strtolower(str_replace(' ', '_', str_replace('* ', '', $request->input("image_name_{$i}")))) . '.' . $image->getClientOriginalExtension();
						$image->move($directory, $image_name);

						$image_format = [
							'module_id' => $request->module_id,
							'branch_id' => Auth::user()->branch,
							'reference_id' => $latestInsertedId,
							'files_id' => intval($request->input("image_id_{$i}")),
							'files_name' => str_replace('* ', '', $request->input("image_name_{$i}")),
							'path' => $path . '/' . $image_name,
						];


						FilesUploaded::create($image_format);
					}
				}

				$receive_unit = receive_unit::create([
					'branch' => Auth::user()->branch,
					'repo_id' => $latestInsertedId,
					'unit_price' => $request->original_srp,
					'loan_amount' => $request->unit_loan_amount,
					'total_payments' => $request->unit_total_payment,
					'principal_balance' => $request->unit_principal_balance,
					'is_certified_no_parts' => $request->certify_no_missing_and_damaged_parts,
					'original_owner' => $request->original_owner,
					'original_owner_id' => $request->original_owner_id,
				]);
				$receive_latestInsertedId = $receive_unit->id;

                $isCertified = filter_var($receive_unit->is_certified_no_parts, FILTER_VALIDATE_BOOLEAN);
                if(! $isCertified) {
                    $md_path  = $path . '/missing_and_damages';
                    $md_directory  = public_path($md_path);

                    if (!File::isDirectory($md_directory)) {
                        File::makeDirectory($md_directory, 0777, true, true);
                    }

                    for ($i = 1; $i <= $request->spare_parts_count; $i++) {
                        $filePath = null;

                        if ($request->hasFile("spare_parts_proof_{$i}")) {
                            $image = $request->file("spare_parts_proof_{$i}");
                            $image_name = strtoupper(uniqid()) . '.' . $image->getClientOriginalExtension();
                            $image->move($md_directory, $image_name);
                            $filePath = $md_path . '/' . $image_name;
                        }

                        if ($request->input("spare_parts_id_{$i}")) {
                            $spare_parts_format = [
                                'recieve_id' => $receive_latestInsertedId,
                                'parts_id' => $request->input("spare_parts_id_{$i}"),
                                'parts_status' => $request->input("spare_parts_status_{$i}"),
                                'price' => $request->input("spare_parts_price_{$i}"),
                                'dir_image' => $filePath,
                                'parts_remarks' => $request->input("spare_parts_remarks_{$i}")
                            ];
                            unit_spare_parts::create($spare_parts_format);
                        }
                    }
                }

				$module = DB::table('system_menu')->where('file_path', '=', 'repo_tagging_approval.php')->first();
				$matrix =  $this->ApprovalMatrixActivityLog($module->id, $receive_latestInsertedId);
				if ($matrix['status'] == 'error') {
					return $matrix;
				} else {
					receive_unit::where('id', $receive_latestInsertedId)->update(['approver' => $matrix['message'], 'date_approved' => null]);
				}

				DB::commit();
				return $this->sendResponse([], 'REPO Ddetails added successfully.');
			}
		}
		catch (\Throwable $th) {
			return $this->sendError($th->errorInfo[2]);
		}
	}

	public function repo(Request $request)
    {
        try {

            $roleName = Auth::user()->userrole;
            $branchId = Auth::user()->branch;

            $cteQuery = $this->cteQuery();

            $sql = "
                DECLARE @roleName Nvarchar(100) = :roleName, @branchId Int = :branchId;
                {$cteQuery}

                SELECT
                    rep.*,
                    cus.acumatica_id,
                    CONCAT(cus.firstname, ' ', cus.lastname) AS customer_name,
                    branch.name AS branch_name,
                    brd.brandname,
                    mdl.model_name,
                    CASE
                        WHEN rud.status IN (1, 4) THEN 'Pending Repo Tagging Approval'
                        WHEN transfer.status = 0 THEN 'Pending Stock Transfer Approval'
                        WHEN appraisal.status = 0 THEN 'Pending Appraisal Approval'
                        WHEN re_refurb.status = 0 THEN 'Pending Refurbishment Approval'
                        WHEN re_refurb.status = 1 THEN 'Refurbishment In Progress'
                        WHEN re_refurb.status = 3 AND se_refurb.status IS NULL THEN 'Pending Settle Refurbishment'
                        WHEN re_refurb.status = 3 AND se_refurb.status = 0 THEN 'Settle Refurbishment In Progress'
                        WHEN sld.repo_id IS NOT NULL AND sld.status = 0 THEN 'Pending Selling Approval'
                        WHEN sld.repo_id IS NOT NULL AND sld.status = 1 THEN 'Sold'
                        WHEN (rud.status = 0 AND UPPER(rud.is_sold) = 'N')
                            OR appraisal.status = 1
                            OR (re_refurb.status = 4 AND se_refurb.status = 1) THEN 'Available'
                        ELSE ''
                    END AS current_status,
                    CONCAT(ISNULL(files.total_upload_required_files, 0), ' / ',
                        (SELECT COUNT(*) FROM files WHERE isRequired = 1 AND status = 1)) AS total_upload_files,
                    rud.loan_amount,
                    rud.principal_balance,
                    rud.total_payments,
                    ISNULL(missing_and_damaged_items.total_amount_of_missing_and_damages, 0) AS total_amount_of_missing_and_damages,
                    repoCompute.total_depreciation_cost,
                    repoCompute.settled_total_cost,

                    (
                        rep.original_srp -
                        (ISNULL(missing_and_damaged_items.total_amount_of_missing_and_damages, 0) + repoCompute.total_depreciation_cost) +
                        CASE
                            WHEN appraisal.repo_id IS NOT NULL THEN repoCompute.settled_total_cost
                            ELSE 0
                        END
                    ) AS smv_pricing

                FROM repo_details rep
                INNER JOIN recieve_unit_details rud ON rep.id = rud.repo_id
                LEFT JOIN branches branch ON rep.branch_id = branch.id
                LEFT JOIN customer_profile cus ON rep.customer_acumatica_id = cus.id
                LEFT JOIN brands brd ON rep.brand_id = brd.id
                LEFT JOIN unit_models mdl ON rep.model_id = mdl.id
                LEFT JOIN users usr ON usr.id = rud.approver

                LEFT JOIN (
                    SELECT
                        repo.id AS repo_id,
                        CASE
                            WHEN DATEDIFF(MONTH, CONVERT(DATE, repo.date_sold), GETDATE()) BETWEEN 1 AND 6 THEN repo.original_srp * 0.05
                            WHEN DATEDIFF(MONTH, CONVERT(DATE, repo.date_sold), GETDATE()) BETWEEN 7 AND 12 THEN repo.original_srp * 0.10
                            WHEN DATEDIFF(MONTH, CONVERT(DATE, repo.date_sold), GETDATE()) BETWEEN 13 AND 24 THEN repo.original_srp * 0.15
                            ELSE repo.original_srp * 0.20
                        END AS total_depreciation_cost,

                        ISNULL((
                            SELECT
                                SUM(settled_total_cost) AS settled_total_cost
                            FROM transactions
                            WHERE repo_id = repo.id AND row_num > (
                                SELECT row_num
                                FROM transactions
                                WHERE repo_id = repo.id AND source_process = 'appraisal'
                            )
                        ), 0) AS settled_total_cost
                    FROM repo_details repo
                ) AS repoCompute ON repoCompute.repo_id = rep.id

                LEFT JOIN (
                    SELECT repo.id AS repo_id, COUNT(upload.id) AS total_upload_required_files
                    FROM repo_details repo
                    LEFT JOIN files_uploaded upload
                        ON repo.id = upload.reference_id AND repo.branch_id = upload.branch_id
                    INNER JOIN (
                        SELECT * FROM files WHERE isRequired = 1 AND status = 1
                    ) files ON upload.files_id = files.id
                    WHERE upload.is_deleted = 0
                    GROUP BY repo.id, upload.branch_id
                ) files ON files.repo_id = rep.id

                LEFT JOIN (
                    SELECT sub.approvalid, sub.recievedid, sta1.status,
                        CASE
                            WHEN sta1.status = 1 THEN sta1.to_branch
                            WHEN sta1.status = 2 THEN sta1.from_branch
                        END AS current_branch,
                        stu1.is_received AS isreceived, stu1.is_use_old_files,
                        rud1.repo_id AS repoid, sub.unitid
                    FROM (
                        SELECT MAX(sta.id) AS approvalid,
                            MAX(stu.recieved_unit_id) AS recievedid,
                            MAX(stu.id) AS unitid
                        FROM stock_transfer_approval sta
                        INNER JOIN stock_transfer_unit stu ON sta.id = stu.stock_transfer_id
                        GROUP BY stu.recieved_unit_id
                    ) sub
                    INNER JOIN stock_transfer_approval sta1 ON sub.approvalid = sta1.id
                    INNER JOIN stock_transfer_unit stu1
                        ON sub.unitid = stu1.id
                    AND sub.approvalid = stu1.stock_transfer_id
                    AND sub.recievedid = stu1.recieved_unit_id
                    INNER JOIN recieve_unit_details rud1 ON sub.recievedid = rud1.id
                ) transfer ON rud.id = transfer.recievedid

                LEFT JOIN (
                    SELECT rec.latest_id, MAX(app.repo_id) AS repo_id, app.branch, app.status
                    FROM request_approvals app
                    INNER JOIN (
                        SELECT MAX(id) AS latest_id, repo_id
                        FROM request_approvals
                        GROUP BY repo_id
                    ) rec ON app.id = rec.latest_id
                    INNER JOIN request_approvals request ON rec.latest_id = request.id
                    GROUP BY rec.latest_id, app.branch, app.status
                ) appraisal ON rud.id = appraisal.repo_id AND rep.branch_id = appraisal.branch

                LEFT JOIN (
                    SELECT MAX(id) AS latest_id, repo_id, branch, status
                    FROM request_refurbishes
                    GROUP BY repo_id, branch, status
                ) re_refurb ON rep.id = re_refurb.repo_id AND rep.branch_id = re_refurb.branch

                LEFT JOIN refurbish_processes se_refurb
                    ON re_refurb.latest_id = se_refurb.refurbish_req_id

                LEFT JOIN sold_units sld
                    ON rep.id = sld.repo_id AND rep.branch_id = sld.branch

                LEFT JOIN (
                    SELECT recieve_id AS received_id, SUM(price) AS total_amount_of_missing_and_damages
                    FROM recieve_unit_spare_parts
                    WHERE is_deleted = 0
                    GROUP BY recieve_id
                ) missing_and_damaged_items ON rud.id = missing_and_damaged_items.received_id

                WHERE (sld.repo_id IS NULL OR sld.status != 1)
                AND (
                    @roleName = 'Warehouse Custodian' AND rep.branch_id = @branchId
                        AND (transfer.current_branch IS NULL OR CAST(transfer.current_branch AS INT) = CAST(rep.branch_id AS INT)) OR
                    @roleName != 'Warehouse Custodian'
                )
                ORDER BY rep.created_at DESC
            ";

            // for debugging purposes
            // $interpolatedSql = str_replace(
            //     [':roleName', ':branchId'],
            //     ["'" . addslashes($roleName) . "'", (int) $branchId],
            //     $sql
            // );
            // Log::info('Final SQL Query with Bindings:', ['query' => $interpolatedSql]);

            $repos = DB::select($sql, [
                'roleName' => $roleName,
                'branchId' => $branchId,
            ]);

            return DataTables::of($repos)->make(true);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

	public function repoDetailsPerId($id, $moduleid)
	{
		try {
			$repo = DB::table('repo_details as repo')
                ->selectRaw("
                    repo.*
                ")
                ->where('repo.id', '=', $id)->first();
			$customer = DB::table('customer_profile')->where('id', '=', $repo->customer_acumatica_id)->first();
			$brand = DB::table('brands')->where('id', '=', $repo->brand_id)->first();
			$model = DB::table('unit_models')->where('brand_id', '=', $repo->brand_id)->where('id', '=', $repo->model_id)->first();
			$color = DB::table('unit_colors')->where('id', '=', $repo->color_id)->first();
			$picture = DB::table('files_uploaded')->where('reference_id', '=', $repo->id)->where('module_id', '=', $moduleid)->where('is_deleted', '=', 0)->get();
			$received = DB::table('recieve_unit_details')->where('repo_id', '=', $repo->id)->first();
			$parts = DB::table('recieve_unit_spare_parts as rsp')
				->select('rsp.*', 'prt.name', DB::raw("CASE WHEN rsp.actual_price != 0 OR rsp.actual_price != null THEN rsp.actual_price ELSE rsp.price END AS latest_price"))
				->leftJoin('spare_parts as prt', 'rsp.parts_id', '=', 'prt.id')
				->where('rsp.recieve_id', '=', $received->id)->where('rsp.is_deleted', '=', 0)
				->where(function ($query) {
					$query->where('rsp.refurb_decision', '=', 'na')
						->orWhereNull('rsp.refurb_decision');
				})
				->get();

			$transfer = DB::table(function ($query) {
				$query->select(
					DB::raw('MAX(sta.id) AS approvalid'),
					DB::raw('MAX(stu.recieved_unit_id) AS recievedid'),
					DB::raw('MAX(stu.id) AS unitid')
				)
					->from('stock_transfer_approval as sta')
					->join('stock_transfer_unit as stu', 'sta.id', '=', 'stu.stock_transfer_id')
					->groupBy('stu.recieved_unit_id');
			}, 'sub')
				->select(
					'sub.approvalid',
					'sub.recievedid',
					'sta1.status AS approvalstatus',
					DB::raw('CASE WHEN sta1.status = 1 THEN sta1.to_branch WHEN sta1.status = 2 THEN sta1.from_branch END AS current_branch'),
					'stu1.is_received AS isreceived',
					'stu1.is_use_old_files',
					'rud1.repo_id as repoid',
					'sub.unitid'
				)
				->join('stock_transfer_approval as sta1', 'sub.approvalid', '=', 'sta1.id')
				->join('stock_transfer_unit as stu1', function ($join) {
					$join->on('sub.unitid', '=', 'stu1.id')
						->on('sub.approvalid', '=', 'stu1.stock_transfer_id')
						->on('sub.recievedid', '=', 'stu1.recieved_unit_id');
				})
				->join('recieve_unit_details as rud1', 'sub.recievedid', '=', 'rud1.id')
				->where('rud1.repo_id', '=', $repo->id)
				->first();

			if (strtolower(Auth::user()->userrole) == 'warehouse custodian' && $transfer == null) {
				$disabled = true;
			} else if (strtolower(Auth::user()->userrole) == 'warehouse custodian' && $transfer != null && $transfer->isreceived != "0") {
				$disabled = false;
			} else if (strtolower(Auth::user()->userrole) == 'verifier' && $transfer != null && $transfer->isreceived != "0") {
				$disabled = false;
			} else {
				$disabled = false;
			}

			$data = [
				'repo' => $repo,
				'customer_details' => $customer,
				'brand_details' => $brand,
				'model_details' => $model,
				'color_details' => $color,
				'picture_details' => $picture,
				'received_details' => $received,
				'parts_details' => $parts,
				'disabled' => $disabled,
			];

			return $data;
		} catch (\Throwable $th) {
			return $this->sendError($th->errorInfo[2]);
		}
	}

	public function list_of_files()
	{
		try {
			$statement = DB::table('files')->where('status', '=', '1');
			$files = $statement->get();
			$required = $statement->where('isRequired', '=', '1')->get();

			$response = ['required' => $required, 'files' => $files];
			return $response;
		} catch (\Throwable $th) {
			return $this->sendError($th->errorInfo[2]);
		}
	}

	public function list_of_location()
	{
		try {
			return DB::table('locations')->where('status', '=', '1')->get();
		} catch (\Throwable $th) {
			return $this->sendError($th->errorInfo[2]);
		}
	}

	public function repoDeleteFiles($deleted_id)
	{
		try {
			$filename = FilesUploaded::Where('id', $deleted_id)->first();
			FilesUploaded::where('id', $filename->id)->update([
				'is_deleted' => '1'
			]);
			return $filename->files_name;
		} catch (\Throwable $th) {
			return $this->sendError($th->errorInfo[2]);
		}
	}

	public function updateRepo(Request $request, $id)
	{
		try{
			$validator = Validator::make($request->all(), [
				'customer_acumatica_id' => 'required',
				'brand_id' => 'required',
				'model_id' => 'required',
				'model_engine' => 'required',
				'model_chassis' => 'required',
				'color_id' => 'required',
				'plate_number' => 'nullable',
				'mv_file_number' => 'nullable',
				'year_model' => 'required',
				'orcr_status' => 'required',
				'original_owner' => 'required',
				'original_owner_id' => 'required',
				'unit_documents' => 'required',
				'date_sold' => 'required',
				'date_surrender' => 'required',
				'original_srp' => 'required',
				'unit_loan_amount' => 'required',
				'unit_principal_balance' => 'required',
				'unit_total_payment' => 'required',
				'last_payment' => 'nullable',
				'loan_number' => 'required',
				'odo_meter' => 'required',
				'location' => 'required',
				'times_repossessed' => 'required',
				'repossessed_exowner' => ($request->times_repossessed > 1 ? 'required' : 'nullable'),
				'apprehension' => 'required',
				'apprehension_description' => ($request->apprehension == 'yes' ? 'required' : 'nullable'),
				'apprehension_summary' => ($request->apprehension == 'yes' ? 'required' : 'nullable'),

				'certify_no_missing_and_damaged_parts' => 'required',
				'append_count' => 'required',
				'module_id' => 'required',

				'image_fetch_id_*' => 'nullable',
				'image_*' => 'nullable',
				'image_id_*' => 'nullable',
				'image_name_*' => 'nullable',

				'spare_parts_id_*' => ($request->certify_no_missing_and_damaged_parts == 'true' ? 'required' : 'nullable'),
				'spare_parts_status_*' => ($request->certify_no_missing_and_damaged_parts == 'true' ? 'required' : 'nullable'),
				'spare_parts_price_*' => ($request->certify_no_missing_and_damaged_parts == 'true' ? 'required' : 'nullable'),
				'spare_parts_proof_*' => ($request->certify_no_missing_and_damaged_parts == 'true' ? 'required' : 'nullable'),
				'spare_parts_remarks_*' => ($request->certify_no_missing_and_damaged_parts == 'true' ? 'required' : 'nullable'),
				'spare_parts_count' => ($request->certify_no_missing_and_damaged_parts == 'true' ? 'required' : 'nullable'),
			]);

			if ($validator->fails()) {
				return $this->sendError('Validation Error.', $validator->errors());
			}

				$repo_format = [
					'customer_acumatica_id' => $request->customer_acumatica_id,
					'brand_id' => $request->brand_id,
					'model_id' => $request->model_id,
					'model_engine' => $request->model_engine,
					'model_chassis' => $request->model_chassis,
					'color_id' => $request->color_id,
					'plate_number' => $request->plate_number,
					'mv_file_number' => $request->mv_file_number,
					'year_model' => $request->year_model,
					'orcr_status' => $request->orcr_status,
					'unit_documents' => $request->unit_documents,
					'date_sold' => $request->date_sold,
					'date_surrender' => $request->date_surrender,
					'original_srp' => $request->original_srp,
                    'last_payment' => $request->last_payment,
					'loan_number' => $request->loan_number,
					'odo_meter' => $request->odo_meter,
					'location' => $request->location,
					'times_repossessed' => $request->times_repossessed,
					'repossessed_exowner' => $request->repossessed_exowner,
                    'apprehension' => $request->apprehension,
                    'apprehension_description' => $request->apprehension_description,
                    'apprehension_summary' => $request->apprehension_summary,
				];

				DB::beginTransaction();

				DB::table('repo_details')->where('id', $id)->update($repo_format);

				$path = 'image/unit_received/' . strtoupper($request->model_engine . '-' . $request->model_chassis);
				$directory = public_path($path);
				if (!File::isDirectory($directory)) {
					File::makeDirectory($directory, 0777, true, true);
				}

				$maxid = receive_unit::where('repo_id', '=', $id)->first();

				for ($i = 1; $i <= $request->append_count; $i++) {
					$image = $request->file("image_{$i}");
					if ($image) {
						$image_name = strtoupper(uniqid()) . '_' . strtolower(str_replace(' ', '_', str_replace('* ', '', $request->input("image_name_{$i}")))) . '.' . $image->getClientOriginalExtension();
						$image->move($directory, $image_name);

						$image_format = [
							'reference_id' => $id,
							'module_id' => $request->module_id,
							'branch_id' => $maxid->branch,
							'files_id' => $request->input("image_id_{$i}"),
							'files_name' => str_replace('* ', '', $request->input("image_name_{$i}")),
							'path' => $path . '/' . $image_name,
						];

						FilesUploaded::create($image_format);
					}
				}

                $receive_format = [
					'unit_price' => $request->original_srp,
					'loan_amount' => $request->unit_loan_amount,
					'total_payments' => $request->unit_total_payment,
					'principal_balance' => $request->unit_principal_balance,
					'is_certified_no_parts' => $request->certify_no_missing_and_damaged_parts,
					'original_owner' => $request->original_owner,
					'original_owner_id' => $request->original_owner_id,
				];

				$maxid->update($receive_format);

                $isCertified = filter_var($maxid->is_certified_no_parts, FILTER_VALIDATE_BOOLEAN);
                if(! $isCertified) {
                    $md_path  = $path . '/missing_and_damages';
                    $md_directory  = public_path($md_path);

                    if (!File::isDirectory($md_directory)) {
                        File::makeDirectory($md_directory, 0777, true, true);
                    }

                    for ($i = 1; $i <= $request->spare_parts_count; $i++) {
                        $filePath = null;

                        if ($request->hasFile("spare_parts_proof_{$i}")) {
                            $image = $request->file("spare_parts_proof_{$i}");
                            $image_name = strtoupper(uniqid()) . '.' . $image->getClientOriginalExtension();
                            $image->move($md_directory, $image_name);
                            $filePath = $md_path . '/' . $image_name;
                        }

                        if ($request->input("spare_parts_id_{$i}")) {
                            $spare_parts_format = [
                                'recieve_id' => $maxid->id,
                                'parts_id' => $request->input("spare_parts_id_{$i}"),
                                'parts_status' => $request->input("spare_parts_status_{$i}"),
                                'price' => $request->input("spare_parts_price_{$i}"),
                                'dir_image' => $filePath,
                                'parts_remarks' => $request->input("spare_parts_remarks_{$i}")
                            ];

                            if ($request->input("parts_unique_id_{$i}") == 0) {
                                unit_spare_parts::create($spare_parts_format);
                            } else {
                                unit_spare_parts::where('id', $request->input("parts_unique_id_{$i}"))->update($spare_parts_format);
                            }
                        }
                    }
                }
				DB::commit();
			// }
			return $this->sendResponse([], 'REPO Ddetails update successfully.');
		}
		catch (\Throwable $th) {
			return $this->sendError($th->errorInfo[2]);
		}
	}

	public function fetch_repo_approval(Request $request, $moduleid)
	{
		try {
            $cteQuery = $this->cteQuery();

            $role = DB::select("
                DECLARE @module INT = :module, @userId INT = :userId;
                {$cteQuery}

                SELECT
                    CASE (SELECT COUNT(approverId) FROM approvers WHERE module_id = @module AND approverId = @userId)
                        WHEN 1 THEN 'Approver'
                        ELSE 'Maker'
                    END AS roles
                ",
                [ 'module' => $moduleid, 'userId' => Auth::user()->id ]
            );

			$stmt = DB::table('repo_details as rep')
				->select(
					'rep.*',
					'bth.name AS branch_name',
					'cus.acumatica_id',
					DB::raw("CONCAT(cus.firstname, ' ', cus.lastname) AS customer_name"),
					'brd.brandname',
					'mdl.model_name',
					'rep.model_engine',
					'rep.model_chassis',
					DB::raw("CASE
						WHEN rud.status = '4' THEN 'Repo Tagging Approval'
						WHEN rud.status = '0' AND UPPER(rud.is_sold) = 'N' THEN 'Subject for Reprice Approval'
						WHEN rud.status = '1' AND UPPER(rud.is_sold) = 'N' THEN 'For Sell'
						WHEN rud.status = '1' AND UPPER(rud.is_sold) = 'Y' THEN 'Sold'
						WHEN rud.status = '2' THEN 'Disapproved'
						ELSE ''
					END AS current_status"),
					DB::raw("UPPER(CONCAT(usr.firstname,' ',usr.lastname)) AS approver_name"),
					DB::raw("CASE WHEN rud.status = 4 THEN 'Pending' ELSE 'Approved' END AS repo_status"),
				)
				->join('recieve_unit_details as rud', 'rep.id', '=', 'rud.repo_id')
				->leftJoin('customer_profile as cus', 'rep.customer_acumatica_id', '=', 'cus.id')
				->leftJoin('brands as brd', 'rep.brand_id', '=', 'brd.id')
				->leftJoin('unit_models as mdl', 'rep.model_id', '=', 'mdl.id')
				->leftJoin('branches as bth', 'rep.branch_id', '=', 'bth.id')
				->leftJoin('users as usr', 'usr.id', '=', 'rud.approver')
				->where('rud.status', '=', 4)
            ->where('rud.approver', Auth::user()->id);

            return DataTables::of($stmt)
                ->filter(function ($query) use ($request) {
                    if ($search = $request->get('search')['value']) {
                        $query->where(function ($q) use ($search) {
                            $q->orWhere('bth.name', 'like', "%{$search}%")
                            ->orWhere('cus.acumatica_id', 'like', "%{$search}%")
                            ->orWhere('rep.transaction_number_inventory_in', 'like', "%{$search}%")
                            ->orWhere(DB::raw("CONCAT(cus.firstname, ' ', cus.lastname)"), 'like', "%{$search}%")
                            ->orWhere('mdl.model_name', 'like', "%{$search}%")
                            ->orWhere('rep.model_engine', 'like', "%{$search}%")
                            ->orWhere('rep.model_chassis', 'like', "%{$search}%");
                        });
                    }
                })
                ->order(function ($q) {
                    $q->orderByDesc('rep.created_at');
                })
                ->make(true);

		} catch (\Throwable $th) {
			return $this->sendError($th->errorInfo[2]);
		}
	}

	public function repo_approver_decision(Request $request)
	{

		$repo = DB::table('repo_details')->where('id', '=', $request->recordid)->first();
		$maxid = DB::table('recieve_unit_details')->where('repo_id', '=', $repo->id)->where('branch', '=', $repo->branch_id)->max('id');
		$received = DB::table('recieve_unit_details')->where('id', '=', $maxid)->first();

		try {
			$validator = Validator::make($request->all(), [
				'moduleid' => 'required|numeric',
				'recordid' => 'required|numeric',
				'status' => 'required|numeric',
				'loanAmount' => 'required',
				'totalPayment' => 'required',
				'principalBalance' => 'required',
			]);

			if ($validator->fails()) {
				return $this->sendError('Validation Error.', $validator->errors());
			}

			// Get the next approver in approval matrix
			$sequence = $this->approverDecision($request->moduleid, $received->id, Auth::user()->id);

			DB::beginTransaction();

			receive_unit::where('id', $received->id)
				->update([
					'loan_amount' => $request->loanAmount,
					'total_payments' => $request->totalPayment,
					'principal_balance' => $request->principalBalance,
					'status' => $request->status,
					'approver' => $sequence == 0 ? Auth::user()->id : $sequence,
					'date_approved' => Carbon::now(),
				]);

			DB::commit();

			$msg = $request->status == 0 ? 'Repo Tagging Successfully Approved!' : 'Repo Tagging Successfully disapproved!';
			return $this->sendResponse([], $msg);
		} catch (\Throwable $th) {
			$this->rollBaclDecision($request->moduleid, $received->id, Auth::user()->id);
			return $this->sendError($th->errorInfo[2]);
		}
	}
}
