<?php

namespace App\Http\Controllers\api_v1;

use App\Http\Controllers\api_v1\BaseController as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\repo;
use App\Models\user_role;
use App\Models\FilesUploaded;
use App\Models\receive_unit;
use App\Models\unit_spare_parts;
use App\Models\approval_matrix_setting AS ApprovalMatrixSetting;
use App\Http\Traits\helper;
use App\Http\Traits\ResuableQuery;
use Illuminate\Support\Carbon;
use Yajra\DataTables\Facades\DataTables;
use App\Http\Traits\TransactionNumberGenerator;
use App\Http\Traits\ApprovalSequence;
use Illuminate\Support\Facades\Log;

class RepoController extends BaseController
{

	use helper, ResuableQuery, TransactionNumberGenerator, ApprovalSequence;

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
                $repo->transaction_number_inventory_in = $this->generateTransactionNumber('inventory_in', $repo->craeted_at);
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
				$firstApproverRoleId =  $this->assignFirstApprover($module->id);
                if ($firstApproverRoleId !== null) {
                    receive_unit::where('id', $receive_latestInsertedId)
                        ->update([
                            'approver' => $firstApproverRoleId,
                            'date_approved' => null
                        ]);
                }

				DB::commit();
				return $this->sendResponse([], 'REPO Ddetails added successfully.');
			}
		}
		catch (\Throwable $th) {
			Log::error("Repo creation failed", [
                'exception' => get_class($th),
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);
            return $this->sendError($th->getMessage());
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
                    repo.id,
                    CASE
                        WHEN stock.transaction_number_inventory_in IS NOT NULL AND stock.is_received != 0 THEN stock.transaction_number_inventory_in
                        ELSE repo.transaction_number_inventory_in
                    END AS inventory_in,
                    CASE
                        WHEN stock.transaction_number_inventory_out IS NOT NULL THEN stock.transaction_number_inventory_out
                        ELSE sold.transaction_number_inventory_out
                    END AS inventory_out,
                    branch.name AS branch_name,
		            customer.acumatica_id,
                    LTRIM(RTRIM(
                        customer.firstname +
                        CASE
                            WHEN customer.middlename IS NULL OR customer.middlename = '' OR customer.middlename = '-'
                                THEN ' '
                            ELSE ' ' + customer.middlename + ' '
                        END +
                        customer.lastname
                    )) AS ex_owner,
                    brand.brandname AS brand_name,
                    model.model_name,
                    UPPER(repo.model_engine) AS model_engine,
                    UPPER(repo.model_chassis) AS model_chassis,
                    repo.year_model,
                    received.principal_balance,
                    received.loan_amount,
                    received.total_payments,
                    ISNULL(missing_and_damaged_items.total_amount_of_missing_and_damages, 0) AS total_amount_of_missing_and_damages,
                    computed.total_depreciation_cost,
                    (
                        repo.original_srp -
                        (ISNULL(missing_and_damaged_items.total_amount_of_missing_and_damages, 0) + computed.total_depreciation_cost) +
                        CASE
                            WHEN appraisal.repo_id IS NOT NULL THEN computed.settled_total_cost
                            ELSE 0
                        END
                    ) AS smv_pricing,
                    CONCAT(ISNULL(uploaded.total_upload_required_files, 0), ' / ',
                        (SELECT COUNT(*) FROM files WHERE isRequired = 1 AND status = 1)) AS total_upload_files,
                    CASE
                        -- 1. SOLD (highest priority)
                        WHEN sold.status = 1 THEN 'Sold'
                        WHEN sold.status = 2 THEN 'Available' -- sale canceled/disapproved

                        -- 2. SETTLEMENT
                        WHEN refurbish.approval_status = 3 AND settlement.approval_status = 0 THEN 'Pending for Settlement Approval'
                        WHEN refurbish.approval_status = 3 AND settlement.approval_status = 2 THEN 'Proceed to Settlement'
                        WHEN refurbish.approval_status = 4 AND settlement.approval_status = 1 THEN 'Available'

                        -- 3. REFURBISH
                        WHEN refurbish.approval_status = 0 THEN 'Pending for Refurbish Approval'
                        WHEN refurbish.approval_status IN (1, 2) THEN 'Available'

                        -- 4. APPRAISAL
                        WHEN appraisal.approval_status = 0 THEN 'Pending for Appraisal Approval'
                        WHEN appraisal.approval_status IN (1, 2) THEN 'Available'

                        -- 5. STOCK TRANSFER
                        WHEN stock.approval_status = 0 THEN 'Pending Stock Transfer Approval'
                        WHEN stock.approval_status = 1 AND stock.is_received = 0 THEN 'Pending for Received Transferred Unit'
                        WHEN stock.approval_status = 1 AND stock.is_received = 1 THEN 'Available'
                        WHEN stock.approval_status = 2 THEN 'Available'

                        -- 6. RECEIVED
                        WHEN received.status = 0 THEN 'Pending for Repo Tagging Approval'
                        WHEN received.status = 2 THEN 'Disapproved for Repo Tagging Approval'
                        WHEN received.status = 4 THEN 'For Repo Reviewing for Approval'
                        WHEN received.status = 1 THEN 'Available'

                        -- 7. DEFAULT
                        ELSE 'Available'
                    END AS current_status


                FROM repo_details AS repo
                INNER JOIN recieve_unit_details AS received ON repo.id = received.repo_id AND repo.branch_id = received.branch
                LEFT JOIN branches AS branch ON repo.branch_id = branch.id
                LEFT JOIN customer_profile AS customer ON repo.customer_acumatica_id = customer.id
                LEFT JOIN brands AS brand ON repo.brand_id = brand.id
                LEFT JOIN unit_models AS model ON repo.model_id = model.id

                LEFT JOIN (
                    SELECT
                        uploaded.reference_id AS repo_id, uploaded.branch_id, COUNT(files_id) AS total_upload_required_files
                    FROM files_uploaded AS uploaded
                    WHERE uploaded.module_id = 3 AND uploaded.is_deleted = 0
                    GROUP BY uploaded.reference_id, uploaded.branch_id
                ) uploaded ON repo.id = uploaded.repo_id AND repo.branch_id = uploaded.branch_id

                LEFT JOIN (
                    SELECT recieve_id AS received_id, SUM(price) AS total_amount_of_missing_and_damages
                    FROM recieve_unit_spare_parts
                    WHERE is_deleted = 0
                    GROUP BY recieve_id
                ) missing_and_damaged_items ON received.id = missing_and_damaged_items.received_id

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
                ) AS computed ON computed.repo_id = repo.id

                LEFT JOIN (
                    SELECT
                        sub.approvalid AS stock_approval_id,
                        sub.recievedid AS recieved_id,
                        approval.status AS approval_status,
                        CASE
                            WHEN approval.status = 1 THEN approval.to_branch
                            WHEN approval.status = 2 THEN approval.from_branch
                            ELSE approval.from_branch
                        END AS current_branch,
                        unit.is_received AS is_received,
                        unit.is_use_old_files,
                        sub.unitid AS stock_unit_id,
                        unit.transaction_number_inventory_in,
                        unit.transaction_number_inventory_out
                    FROM (
                        SELECT
                            MAX(approval.id) AS approvalid, MAX(unit.recieved_unit_id) AS recievedid, MAX(unit.id) AS unitid
                        FROM stock_transfer_approval AS approval
                        INNER JOIN stock_transfer_unit AS unit ON approval.id = unit.stock_transfer_id
                        GROUP BY unit.recieved_unit_id
                    ) sub
                    INNER JOIN stock_transfer_approval approval ON sub.approvalid = approval.id
                    INNER JOIN stock_transfer_unit unit ON sub.unitid = unit.id AND sub.approvalid = unit.stock_transfer_id AND sub.recievedid = unit.recieved_unit_id
                ) stock ON received.id = stock.recieved_id AND repo.branch_id = stock.current_branch

                LEFT JOIN (
                    SELECT
                        appraisal.id,
                        appraisal.repo_id,
                        appraisal.branch,
                        appraisal.status AS approval_status,
                        history.appraised_price AS appraised_approved_price
                    FROM (
                        SELECT
                            MAX(id) AS max_id, MAX(repo_id) AS max_repo_id, MAX(branch) AS max_branch_id
                        FROM request_approvals
                        GROUP BY repo_id
                    ) SUB
                    INNER JOIN request_approvals AS appraisal ON SUB.max_id = appraisal.id
                    LEFT JOIN appraisal_histories AS history ON SUB.max_id = history.appraisal_req_id
                ) appraisal ON repo.id = appraisal.repo_id AND repo.branch_id = appraisal.branch

                LEFT JOIN (
                    SELECT
                        refurbish.id,
                        refurbish.repo_id,
                        refurbish.branch AS branch_id,
                        refurbish.status AS approval_status
                    FROM (
                        SELECT
                            MAX(id) AS max_id, MAX(repo_id) AS max_repo_id, MAX(branch) AS max_branch_id
                        FROM request_refurbishes
                        GROUP BY repo_id
                    ) SUB
                    INNER JOIN request_refurbishes AS refurbish ON SUB.max_id = refurbish.id
                ) refurbish ON repo.id = refurbish.repo_id AND repo.branch_id = refurbish.branch_id

                LEFT JOIN (
                    SELECT
                        refurbish.id,
                        refurbish.repo_id,
                        refurbish.branch AS branch_id,
                        process.status AS approval_status
                    FROM (
                        SELECT
                            MAX(id) AS max_id, MAX(repo_id) AS max_repo_id, MAX(branch) AS max_branch_id
                        FROM request_refurbishes
                        GROUP BY repo_id
                    ) SUB
                    INNER JOIN request_refurbishes AS refurbish ON SUB.max_id = refurbish.id
                    INNER JOIN refurbish_processes AS process ON refurbish.id = process.refurbish_req_id
                ) settlement ON repo.id = settlement.repo_id AND repo.branch_id = settlement.branch_id

                LEFT JOIN sold_units AS sold ON repo.id = sold.repo_id AND repo.branch_id = sold.branch

                WHERE (sold.id IS NULL OR sold.status != 1)
                AND repo.branch_id = CASE
                    WHEN @roleName = 'Warehouse Custodian' THEN @branchId
                    ELSE repo.branch_id  -- allow all branches
                END
                ORDER BY repo.id DESC
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
					DB::raw('CASE WHEN sta1.status = 1 THEN sta1.to_branch WHEN sta1.status = 2 THEN sta1.from_branch ELSE sta1.from_branch END AS current_branch'),
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
				->where(DB::raw('CASE
                    WHEN sta1.status = 1 THEN sta1.to_branch
                    WHEN sta1.status = 2 THEN sta1.from_branch
                    ELSE sta1.from_branch
                END'), '=', $repo->branch_id)
				->first();

            Log::info('Transfer Details:', ['transfer' => $transfer]);

			if (strtolower(Auth::user()->userrole) == 'warehouse custodian' && $transfer == null) {
                Log::info('Warehouse Custodian - No Transfer Record Found. Disabling Edit.');
				$disabled = true;
			} else if (strtolower(Auth::user()->userrole) == 'warehouse custodian' && $transfer != null && $transfer->isreceived != "0") {
                Log::info('Warehouse Custodian - Transfer Received. Disabling Edit.');
				$disabled = false;
			} else if (strtolower(Auth::user()->userrole) == 'verifier' && $transfer != null && $transfer->isreceived != "0") {
                Log::info('Verifier - Transfer Received. Disabling Edit.');
				$disabled = false;
			} else {
                Log::info('Other Roles - Disabling Edit.');
				$disabled = true;
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
            $userrole = Auth::user()->userrole;

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
					DB::raw("role.user_role_name AS approver_name"),
					DB::raw("CASE WHEN rud.status = 4 THEN 'Pending' ELSE 'Approved' END AS repo_status"),
                    DB::raw("CASE WHEN DATEDIFF(DAY, date_surrender, GETDATE()) <= 7 THEN 1 ELSE 0 END AS is_allowed_redemption")
				)
				->join('recieve_unit_details as rud', 'rep.id', '=', 'rud.repo_id')
				->leftJoin('customer_profile as cus', 'rep.customer_acumatica_id', '=', 'cus.id')
				->leftJoin('brands as brd', 'rep.brand_id', '=', 'brd.id')
				->leftJoin('unit_models as mdl', 'rep.model_id', '=', 'mdl.id')
				->leftJoin('branches as bth', 'rep.branch_id', '=', 'bth.id')
				->leftJoin('user_role as role', 'role.id', '=', 'rud.approver')
				->where('rud.status', '=', 4)
                ->where('role.user_role_name', '=', $userrole);

            return DataTables::of($stmt)
                ->filter(function ($query) use ($request) {
                    $search = $request->get('search')['value'] ?? null;
                    if ($search) {
                        Log::info('[fetch_repo_approval] filter closure - applying search filter', ['search' => $search]);
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
            Log::error('[fetch_repo_approval] Exception', [
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);
            return $this->sendError($th->getMessage());
        }
    }

	public function repo_approver_decision(Request $request)
	{

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

            $repo = DB::table('repo_details')->where('id', '=', $request->recordid)->first();
            $maxid = DB::table('recieve_unit_details')->where('repo_id', '=', $repo->id)->where('branch', '=', $repo->branch_id)->max('id');
            $received = DB::table('recieve_unit_details')->where('id', '=', $maxid)->first();

            $userId   = Auth::id();
            $roleId   = user_role::where('user_role_name', Auth::user()->userrole)->value('id');
            $moduleId = $request->moduleid;
            $recordId = $received->id;

            $check = $this->checkIfApproved($moduleId, $recordId, $roleId);
            if ($check['status']) {
                $approverName = $check['name'] ?? 'Unknown Approver';
                return $this->sendError(
                    "This request has already been approved by {$approverName}.",
                    ['approver' => $check['approver']]
                );
            }

            if (!$received) {
                return $this->sendError('Request not found.');
            }

            DB::beginTransaction();

            $currentApprover = $this->getCurrentApprover($moduleId, $roleId);
            $nextApproverId = null;

            if ($request->status == 1) {
                $this->logApproval($moduleId, $recordId, $userId, $roleId, $currentApprover->level, 'A');

                $nextApprover = $this->getNextApprover($moduleId, $currentApprover->level);

                if ($nextApprover) {
                    $nextApproverId = $nextApprover->approverId;
                } else {
                    receive_unit::where('id', $recordId)
                        ->update([
                            'loan_amount' => $request->loanAmount,
                            'total_payments' => $request->totalPayment,
                            'principal_balance' => $request->principalBalance,
                        ]);
                }
            }

            receive_unit::where('id', $recordId)
                ->update([
                    'status' => $request->status,
                    'approver' => $nextApproverId ?? $roleId,
                    'date_approved' => Carbon::now(),
                ]);

            DB::commit();

            return $this->sendResponse([],
                $request->status == 0
                    ? 'Repo Tagging Successfully Approved!'
                    : 'Repo Tagging Successfully disapproved!'
            );
		} catch (\Throwable $th) {
            DB::rollBack();
			// $this->rollBaclDecision($request->moduleid, $received->id, Auth::user()->id);
            Log::error("Repo creation failed", [
                'exception' => get_class($th),
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);
			return $this->sendError($th->errorInfo[2]);
		}
	}
}
