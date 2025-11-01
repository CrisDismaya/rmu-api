<?php

namespace App\Http\Controllers\api_v1;

use Illuminate\Http\Request;
use App\Http\Controllers\api_v1\BaseController as BaseController;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use App\Models\RequestApproval;
use App\Models\unit_aging;
use App\Models\receive_unit;
use App\Models\sold_unit;
use App\Models\repo;
use App\Models\user_role;
use App\Models\approval_matrix_setting AS ApprovalMatrixSetting;
use App\Http\Traits\helper;
use App\Http\Traits\acumaticaService;
use App\Http\Traits\ResuableQuery;
use Carbon\Carbon;
use App\Models\appraisal_history;
use Yajra\DataTables\Facades\DataTables;
use App\Http\Traits\TransactionNumberGenerator;
use App\Http\Traits\ApprovalSequence;

class RequestApprovalController extends BaseController
{
    //
    use helper, acumaticaService, ResuableQuery; //helper traits
    use TransactionNumberGenerator, ApprovalSequence;

    public function listReceivedUnit()
    {

        try {
            $cteQuery = $this->cteQuery();

            $stmt = DB::select("
                DECLARE @roleName Nvarchar(50) = :roleName, @branchId INT = :branchId;
                {$cteQuery}

                SELECT
                    received.*,
                    repo.model_engine,
                    repo.model_chassis,
                    repo.date_sold,
                    repo.original_srp,
                    branch.name as branchname,
                    brand.brandname,
                    model.model_name,
                    color.name as color,
                    CASE
                        WHEN appraise.approved_price IS NULL THEN (
                            CASE
                                WHEN DATEDIFF(MONTH, (CONVERT(DATE, repo.date_sold)), GETDATE()) >= 1 and DATEDIFF(MONTH, (CONVERT(DATE, repo.date_sold)), GETDATE()) <= 6
                                    THEN (repo.original_srp + ISNULL(parts.total_cost_parts, 0)) - (ISNULL(total_parts.total_parts_price, 0) + (repo.original_srp ) * .05)

                                WHEN DATEDIFF(MONTH, (CONVERT(DATE, repo.date_sold)), GETDATE()) >= 7 and DATEDIFF(MONTH, (CONVERT(DATE, repo.date_sold)), GETDATE()) <= 12
                                    THEN (repo.original_srp + ISNULL(parts.total_cost_parts, 0)) - (ISNULL(total_parts.total_parts_price, 0) + (repo.original_srp) * .10)

                                WHEN DATEDIFF(MONTH, (CONVERT(DATE, repo.date_sold)), GETDATE()) >= 13 and DATEDIFF(MONTH, (CONVERT(DATE, repo.date_sold)), GETDATE()) <= 24
                                    THEN (repo.original_srp + ISNULL(parts.total_cost_parts, 0)) - (ISNULL(total_parts.total_parts_price, 0) + (repo.original_srp) * .15)

                                WHEN DATEDIFF(MONTH, (CONVERT(DATE, repo.date_sold)), GETDATE()) >= 25
                                    THEN (repo.original_srp + ISNULL(parts.total_cost_parts, 0)) - (ISNULL(total_parts.total_parts_price, 0) + (repo.original_srp) * .20)
                            ELSE 0 END
                        )
                        ELSE appraise.approved_price + ISNULL((
                            SELECT
                                SUM(settled_total_cost) AS settled_total_cost
                            FROM transactions
                            WHERE repo_id = repo.id AND row_num > (
                                SELECT row_num
                                FROM transactions
                                WHERE repo_id = repo.id AND source_process = 'appraisal'
                            )), 0)
                    END AS current_price
                FROM repo_details repo
                INNER JOIN recieve_unit_details received ON repo.id = received.repo_id
                LEFT JOIN branches branch ON repo.branch_id = branch.id
                LEFT JOIN brands brand ON repo.brand_id = brand.id
                LEFT JOIN unit_models model ON repo.model_id = model.id
                LEFT JOIN unit_colors color ON repo.color_id = color.id
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
                ) files ON files.repo_id = repo.id
                LEFT JOIN (
                    SELECT
                        sub.approvalid, sub.recievedid, sta1.status AS approvalstatus,
                        CASE WHEN sta1.status = 1 THEN sta1.to_branch WHEN sta1.status = 2 THEN sta1.from_branch END AS current_branch,
                        stu1.is_received AS isreceived, stu1.is_use_old_files, rud1.repo_id as repoid, sub.unitid
                    FROM (
                        SELECT MAX(sta.id) AS approvalid, MAX(stu.recieved_unit_id) AS recievedid, MAX(stu.id) AS unitid
                        FROM stock_transfer_approval sta
                        INNER JOIN stock_transfer_unit stu ON sta.id = stu.stock_transfer_id
                        GROUP BY stu.recieved_unit_id
                    ) sub
                    INNER JOIN stock_transfer_approval sta1 ON sub.approvalid = sta1.id
                    INNER JOIN stock_transfer_unit stu1 ON sub.unitid = stu1.id AND sub.approvalid = stu1.stock_transfer_id AND sub.recievedid = stu1.recieved_unit_id
                    INNER JOIN recieve_unit_details rud1 ON sub.recievedid = rud1.id
                ) AS [transfer] ON repo.id = [transfer].repoid
                LEFT JOIN (
                    SELECT sub.received_unit_id, history.appraised_price AS approved_price
                    FROM (
                        SELECT
                            request.received_unit_id, MAX(history.appraisal_req_id) AS appraisal_req_id
                        FROM request_approvals request
                        LEFT JOIN appraisal_histories history ON request.id = history.appraisal_req_id
                        WHERE request.status = 1
                        GROUP BY request.received_unit_id
                    ) sub
                    LEFT JOIN appraisal_histories history ON sub.appraisal_req_id = history.id
                ) appraise ON appraise.received_unit_id = received.id
                LEFT JOIN (
                    SELECT
                        rud.repo_id, SUM(price) total_parts_price
                    FROM recieve_unit_details rud
                    LEFT JOIN recieve_unit_spare_parts rus ON rud.id = rus.recieve_id
                    WHERE rus.is_deleted = 0 AND rus.refurb_id IS NULL
                    GROUP BY rud.repo_id
                ) total_parts ON total_parts.repo_id = repo.id
                LEFT JOIN (
                    SELECT
                        rud.repo_id, SUM(price) total_cost_parts
                    FROM recieve_unit_details rud
                    LEFT JOIN recieve_unit_spare_parts rus ON rud.id = rus.recieve_id
                    WHERE rus.is_deleted = 0 and refurb_id IS NOT NULL
                    GROUP BY rud.repo_id
                ) AS parts ON parts.repo_id = repo.id
                WHERE received.is_sold = 'N' AND received.status != 4
                    AND ISNULL(files.total_upload_required_files, 0) >= (SELECT COUNT(*) FROM files WHERE isRequired = 1 AND status = 1)
                    AND NOT EXISTS (
                        SELECT 1
                        FROM request_approvals
                        WHERE request_approvals.repo_id = repo.id
                        AND request_approvals.branch = @branchId
                        AND request_approvals.status != 1
                    )
                    AND NOT EXISTS (
                        SELECT 1
                        FROM sold_units
                        WHERE sold_units.repo_id = repo.id
                        AND sold_units.branch = @branchId
                    )
                    AND NOT EXISTS (
                        SELECT 1
                        FROM stock_transfer_unit AS a
                        JOIN stock_transfer_approval AS b ON b.id = a.stock_transfer_id
                        WHERE a.recieved_unit_id = received.id
                        AND b.status = 0
                    )
                    AND NOT EXISTS (
                        SELECT 1
                        FROM request_refurbishes
                        WHERE request_refurbishes.repo_id = repo.id
                        AND request_refurbishes.status IN (0, 1, 3)
                        AND request_refurbishes.branch = @branchId
                    )
                    AND (
                        (@roleName = 'Warehouse Custodian' AND repo.branch_id = @branchId) OR
                        (@roleName != 'Warehouse Custodian')
                    )
                ORDER BY repo.created_at DESC
                ",
                [ 'roleName' => Auth::user()->userrole, 'branchId' => Auth::user()->branch ]
            );
            $datatables = Datatables::of($stmt);

            return $datatables->make(true);
        } catch (\Throwable $th) {
            return $this->sendError($th->errorInfo[2]);
        }
    }

    public function getAllReceivedUnit($moduleid)
    {
        try {
            $user = Auth::user();
            $id = $user->id;
            $role = $user->userrole;
            $branch = $user->branch;

            $users = ApprovalMatrixSetting::getApprovalMatrix($moduleid, $role);

            $userRow = collect($users)->first();

            if (! $userRow && $role != 'Warehouse Custodian') {
                return DataTables::of(collect())->make(true);
            }

            $approverRoleId = $userRow->approver_role_id ?? null;
            $approverUsersRaw = $userRow->approver_users ?? '[]';
            $decoded = json_decode($approverUsersRaw, true);
            $userIds = collect($decoded)->pluck('id')->all();
            $currentUserId = Auth::id();

            if (! in_array($currentUserId, $userIds) && $role != 'Warehouse Custodian') {
                return DataTables::of(collect())->make(true);
            }

            $approverIds = is_array($approverRoleId) ? $approverRoleId : [$approverRoleId];

            $cteQuery = $this->cteQuery();

            $stmt = DB::select("
                DECLARE @roleName NVARCHAR(100) = :roleName, @branchId INT = :branchId, @approverRoleId NVARCHAR(MAX) = :approverRoleId;
                {$cteQuery}

                SELECT
                    received.*,
                    repo.model_engine,
                    repo.model_chassis,
                    repo.date_sold,
                    branch.name as branchname,
                    brand.brandname,
                    model.model_name,
                    req_app.suggested_price,
                    req_app.approved_price,
                    CASE
                        WHEN req_app.status = '0' THEN 'PENDING'
                        WHEN req_app.status = '1' THEN 'APPROVED'
                        WHEN req_app.status = '2' THEN 'DISAPPROVED'
                    END status,
                    req_app.remarks,
                    holder.user_role_name as current_holder,
                    CONCAT(maker.firstname,maker.middlename,maker.lastname) as requestor,
                    color.name as color,
                    received.principal_balance,
                    CASE
                        WHEN appraise.approved_price IS NULL THEN (
                            CASE
                                WHEN DATEDIFF(MONTH, (CONVERT(DATE, repo.date_sold)), GETDATE()) >= 1 and DATEDIFF(MONTH, (CONVERT(DATE, repo.date_sold)), GETDATE()) <= 6
                                    THEN (repo.original_srp + ISNULL(parts.total_cost_parts, 0)) - (ISNULL(total_parts.total_parts_price, 0) + (repo.original_srp ) * .05)

                                WHEN DATEDIFF(MONTH, (CONVERT(DATE, repo.date_sold)), GETDATE()) >= 7 and DATEDIFF(MONTH, (CONVERT(DATE, repo.date_sold)), GETDATE()) <= 12
                                    THEN (repo.original_srp + ISNULL(parts.total_cost_parts, 0)) - (ISNULL(total_parts.total_parts_price, 0) + (repo.original_srp) * .10)

                                WHEN DATEDIFF(MONTH, (CONVERT(DATE, repo.date_sold)), GETDATE()) >= 13 and DATEDIFF(MONTH, (CONVERT(DATE, repo.date_sold)), GETDATE()) <= 24
                                    THEN (repo.original_srp + ISNULL(parts.total_cost_parts, 0)) - (ISNULL(total_parts.total_parts_price, 0) + (repo.original_srp) * .15)

                                WHEN DATEDIFF(MONTH, (CONVERT(DATE, repo.date_sold)), GETDATE()) >= 25
                                    THEN (repo.original_srp + ISNULL(parts.total_cost_parts, 0)) - (ISNULL(total_parts.total_parts_price, 0) + (repo.original_srp) * .20)
                            ELSE 0 END
                        )
                        ELSE appraise.approved_price + ISNULL((
                            SELECT
                                SUM(settled_total_cost) AS settled_total_cost
                            FROM transactions
                            WHERE repo_id = repo.id AND row_num > (
                                SELECT row_num
                                FROM transactions
                                WHERE repo_id = repo.id AND source_process = 'appraisal'
                            )), 0)
                    END AS current_price
                FROM repo_details repo
                INNER JOIN recieve_unit_details received ON repo.id = received.repo_id
                LEFT JOIN branches branch ON repo.branch_id = branch.id
                LEFT JOIN brands brand ON repo.brand_id = brand.id
                LEFT JOIN unit_models model ON repo.model_id = model.id
                LEFT JOIN unit_colors color ON repo.color_id = color.id
                LEFT JOIN request_approvals req_app ON received.id = req_app.received_unit_id AND repo.branch_id = req_app.branch
                LEFT JOIN user_role holder ON req_app.approver = holder.id
                LEFT JOIN users maker ON req_app.created_by = maker.id
                LEFT JOIN (
                    SELECT sub.received_unit_id, history.appraised_price AS approved_price
                    FROM (
                        SELECT
                            request.received_unit_id, MAX(history.appraisal_req_id) AS appraisal_req_id
                        FROM request_approvals request
                        LEFT JOIN appraisal_histories history ON request.id = history.appraisal_req_id
                        WHERE request.status = 1
                        GROUP BY request.received_unit_id
                    ) sub
                    LEFT JOIN appraisal_histories history ON sub.appraisal_req_id = history.id
                ) appraise ON appraise.received_unit_id = received.id
                LEFT JOIN (
                    SELECT
                        rud.repo_id, SUM(price) total_parts_price
                    FROM recieve_unit_details rud
                    LEFT JOIN recieve_unit_spare_parts rus ON rud.id = rus.recieve_id
                    WHERE rus.is_deleted = 0 and rus.refurb_id IS NULL
                    GROUP BY rud.repo_id
                ) total_parts ON total_parts.repo_id = repo.id
                LEFT JOIN (
                    SELECT
                        request.repo_id,
                        SUM(total_cost) AS total_cost_parts
                    FROM request_refurbishes request
                    LEFT JOIN refurbish_processes settle ON request.id = settle.refurbish_req_id
                    LEFT JOIN (
                        SELECT
                            refurb_id, SUM(actual_price) AS total_cost
                        FROM recieve_unit_spare_parts
                        WHERE refurb_id IS NOT NULL
                        GROUP BY refurb_id
                    ) parts ON request.id = parts.refurb_id
                    WHERE settle.status = 1
                    GROUP BY request.repo_id
                ) AS parts ON parts.repo_id = repo.id
                WHERE
                    (
                        (@roleName = 'Warehouse Custodian' AND repo.branch_id = @branchId) OR
                        (@roleName != 'Warehouse Custodian') AND req_app.approver IN (@approverRoleId)
                    )
                ORDER BY req_app.id DESC
                ",
                [ 'roleName' => $role, 'branchId' => $branch, 'approverRoleId' => $approverIds  ]
            );

            return Datatables::of($stmt)->make(true);

        } catch (\Throwable $th) {
            return $this->sendError($th->errorInfo[2]);
        }
    }

    public function SoldUnitMasterList()
    {

        try {

            $data = DB::table('repo_details as repo')
                ->join('branches as br', 'repo.branch_id', 'br.id')
                ->join('brands as brd', 'repo.brand_id', 'brd.id')
                ->join('unit_models as mdl', 'repo.model_id', 'mdl.id')
                ->join('unit_colors as color', 'repo.color_id', 'color.id')
                ->join('customer_profile as old_owner', 'repo.customer_acumatica_id', 'old_owner.id')
                ->join('sold_units as sold_unit', 'repo.id', 'sold_unit.repo_id')
                ->join('customer_profile as new_owner', 'sold_unit.new_customer', 'new_owner.id')
                ->select(
                    'sold_unit.id',
                    'repo.id as repo_id',
                    'repo.model_engine',
                    'repo.model_chassis',
                    'br.name as branchname',
                    'brd.brandname',
                    'mdl.model_name',
                    'sold_unit.new_customer',
                    'color.name as color',
                    'old_owner.firstname as o_firstname',
                    'old_owner.middlename as o_middlename',
                    'old_owner.lastname as o_lastname',
                    'new_owner.firstname',
                    'new_owner.middlename',
                    'new_owner.lastname',
                    'sold_unit.invoice_reference_no',
                    DB::raw("CASE WHEN sold_unit.sale_type = 'C' THEN 'CASH'
                    WHEN sold_unit.sale_type = 'I' THEN 'INSTALLMENT' END sale_type"),
                    'sold_unit.srp as approved_price',
                    'sold_unit.dp',
                    'sold_unit.monthly_amo',
                    'sold_unit.rebate',
                    'sold_unit.terms',
                    'sold_unit.sold_date',
                    'sold_unit.maker',
                    'sold_unit.approver',
                    'sold_unit.status'
                )
            ->where('sold_unit.status', '1');

            if (Auth::user()->userrole == 'Warehouse Custodian') {
                $data->where('repo.branch_id', Auth::user()->branch);
            }

            return Datatables::of($data)
                ->order(function ($q) {
                    $q->orderByDesc('sold_unit.created_at');
                })->make(true);

        } catch (\Throwable $th) {
            return $this->sendError($th->errorInfo[2]);
        }
    }

    public function getAllSoldUnits()
    {
        try {
            $soldUnits = DB::table('sold_units as sold')
                ->select(
                    'sold.id',
                    'repo.id as repo_id',
                    'sold.transaction_number',
                    'branch.name AS branch_name',
                    DB::raw("
                        LTRIM(RTRIM(
                            CONCAT(
                                new_owner.firstname, ' ',
                                ISNULL(new_owner.middlename + ' ', ''),
                                new_owner.lastname
                            )
                        )) AS new_owner
                    "),
                    DB::raw("
                        CASE
                            WHEN sold.sale_type = 'C' THEN 'CASH'
                            ELSE 'INSTALLMENT'
                        END AS sale_type
                    "),
                    'sold.sold_date',
                    'brand.brandname',
                    'model.model_name',
                    'repo.model_engine AS engine',
                    'repo.model_chassis AS chassis',
                    'sold.srp AS suggested_retail_price',
                    'sold.dp AS downpayment',
                    'sold.amount_finance AS computed_loan_amount',
                    'sold.interest_rate',
                    'sold.terms',
                    DB::raw("
                        (sold.amount_finance +
                        (sold.amount_finance * ((sold.interest_rate / 100) * (sold.terms / 12))))
                        AS principal_amount
                    "),
                    'sold.invoice_reference_no',
                    'sold.monthly_amo AS monthly_amortization',
                    DB::raw("NULL AS ntr_reference_no"),
                    DB::raw("
                        (sold.srp -
                        (sold.amount_finance +
                        (sold.amount_finance * (sold.interest_rate / 100) * (sold.terms / 12))))
                        AS gain_loss
                    ")
                )
                ->join('repo_details as repo', function ($join) {
                    $join->on('sold.repo_id', '=', 'repo.id')
                        ->on('sold.branch', '=', 'repo.branch_id');
                })
                ->join('recieve_unit_details as received', function ($join) {
                    $join->on('repo.id', '=', 'received.repo_id')
                        ->on('repo.branch_id', '=', 'received.branch');
                })
                ->leftJoin('brands as brand', 'repo.brand_id', '=', 'brand.id')
                ->leftJoin('unit_models as model', 'repo.model_id', '=', 'model.id')
                ->leftJoin('customer_profile as new_owner', 'sold.new_customer', '=', 'new_owner.id')
                ->leftJoin('branches as branch', 'repo.branch_id', '=', 'branch.id')
                ->where('sold.status', 1);

                if (Auth::user()->userrole == 'Warehouse Custodian') {
                    $soldUnits->where('repo.branch_id', Auth::user()->branch);
                }

                return Datatables::of($soldUnits)
                    ->order(function ($q) {
                        $q->orderByDesc('sold.created_at');
                    })->make(true);

            return $soldUnits;
        } catch (\Throwable $th) {
            return $this->sendError($th->getMessage());
        }
    }

    public function getListForApproval($moduleid)
    {

        try {
            $user = Auth::user();
            $id = $user->id;
            $role = $user->userrole;
            $branch = $user->branch;

            $users = ApprovalMatrixSetting::getApprovalMatrix($moduleid, $role);

            $userRow = collect($users)->first();

            if (! $userRow && $role != 'Warehouse Custodian') {
                return DataTables::of(collect())->make(true);
            }

            $approverRoleId = $userRow->approver_role_id ?? null;
            $approverUsersRaw = $userRow->approver_users ?? '[]';
            $decoded = json_decode($approverUsersRaw, true);
            $userIds = collect($decoded)->pluck('id')->all();
            $currentUserId = Auth::id();

            if (! in_array($currentUserId, $userIds) && $role != 'Warehouse Custodian') {
                return DataTables::of(collect())->make(true);
            }

            $approverIds = is_array($approverRoleId) ? $approverRoleId : [$approverRoleId];


            $data = DB::table('repo_details as repo')
                ->select(
                    'sold_unit.id',
                    'sold_unit.transaction_number',
                    'repo.id as repo_id',
                    'repo.model_engine',
                    'repo.model_chassis',
                    'br.name as branchname',
                    'brd.brandname',
                    'mdl.model_name',
                    'sold_unit.new_customer',
                    'color.name as color',
                    'old_owner.firstname as o_firstname',
                    'old_owner.middlename as o_middlename',
                    'old_owner.lastname as o_lastname',
                    'new_owner.firstname',
                    'new_owner.middlename',
                    'new_owner.lastname',
                    'sold_unit.invoice_reference_no',
                    'sold_unit.ExternalReference',
                    'sold_unit.AgentID',
                    DB::raw("CASE WHEN sold_unit.sale_type = 'C' THEN 'CASH'
                        WHEN sold_unit.sale_type = 'I' THEN 'INSTALLMENT' END sale_type"),
                    'sold_unit.srp as approved_price',
                    'sold_unit.dp',
                    'sold_unit.monthly_amo',
                    'sold_unit.amount_paid',
                    'sold_unit.rebate',
                    'sold_unit.terms',
                    'sold_unit.sold_date',
                    'sold_unit.maker',
                    'sold_unit.approver',
                    'sold_unit.status',
                    'sold_unit.remarks',
                    'sold_unit.rate',
                    'sold_unit.amount_finance',
                    'sold_unit.interest_rate',
                    'sold_unit.file_name',
                    'sold_unit.path',
                    'sold_unit.pt_receipt_no',
                    'sold_unit.pt_date',
                    'sold_unit.pt_bank',
                    'sold_unit.pt_amount',
                    'sold_unit.pt_uploads'
                )
                ->join('branches as br', 'repo.branch_id', 'br.id')
                ->join('brands as brd', 'repo.brand_id', 'brd.id')
                ->join('unit_models as mdl', 'repo.model_id', 'mdl.id')
                ->join('unit_colors as color', 'repo.color_id', 'color.id')
                ->join('customer_profile as old_owner', 'repo.customer_acumatica_id', 'old_owner.id')
                // ->join('sold_units as sold_unit', 'repo.id', 'sold_unit.repo_id')
                ->join('sold_units as sold_unit', function ($join) {
                    $join->on('repo.id', '=', 'sold_unit.repo_id');
                    $join->on('repo.branch_id', '=', 'sold_unit.branch');
                })
                ->join('customer_profile as new_owner', 'sold_unit.new_customer', 'new_owner.id');

            if ($role == 'Verifier' || $role == 'General Manager') {
                $data->where('sold_unit.status', '0')->whereIn('sold_unit.approver', $approverIds);
            } elseif ($role == 'Warehouse Custodian') {
                $data->whereIn('sold_unit.status', ['0', '2'])->where('sold_unit.branch', $branch);
            }

            return Datatables::of($data)
                ->order(function ($q) {
                    $q->orderByDesc('sold_unit.created_at');
                })
                ->make(true);

        } catch (\Throwable $th) {
            return $this->sendError($th->errorInfo[2]);
        }
    }

    public function listForSalesTagging(Request $request)
    {
        try {
            $cteQuery = $this->cteQuery();

            $stmt = DB::select("
                DECLARE @roleName Nvarchar(50) = :roleName, @branchId INT = :branchId;
                {$cteQuery}

                SELECT
                    repo.id AS repo_id,
                    repo.msuisva_form_no AS msuisva,
                    repo.model_engine,
                    repo.model_chassis,
                    repo.branch_id,
                    branch.name AS branchname,
                    brand.brandname,
                    model.model_name,
                    color.name AS color,
                    UPPER(
                        CONCAT(customer.firstname,
                            CASE
                                WHEN customer.middlename != '' THEN CONCAT(' ', customer.middlename, ' ')
                            ELSE ' ' END, customer.lastname
                        )
                    ) AS ex_owner,
                    customer.firstname AS o_firstname,
                    customer.middlename AS o_middlename,
                    customer.lastname AS o_lastname,
                    repo.created_at AS date_received,
                    repo.original_srp,
                    CASE
                        WHEN appraise.approved_price IS NULL THEN (
                            CASE
                                WHEN DATEDIFF(MONTH, (CONVERT(DATE, repo.date_sold)), GETDATE()) >= 1 and DATEDIFF(MONTH, (CONVERT(DATE, repo.date_sold)), GETDATE()) <= 6
                                    THEN (repo.original_srp) - (ISNULL(total_parts.total_parts_price, 0) + (repo.original_srp ) * .05)

                                WHEN DATEDIFF(MONTH, (CONVERT(DATE, repo.date_sold)), GETDATE()) >= 7 and DATEDIFF(MONTH, (CONVERT(DATE, repo.date_sold)), GETDATE()) <= 12
                                    THEN (repo.original_srp) - (ISNULL(total_parts.total_parts_price, 0) + (repo.original_srp) * .10)

                                WHEN DATEDIFF(MONTH, (CONVERT(DATE, repo.date_sold)), GETDATE()) >= 13 and DATEDIFF(MONTH, (CONVERT(DATE, repo.date_sold)), GETDATE()) <= 24
                                    THEN (repo.original_srp) - (ISNULL(total_parts.total_parts_price, 0) + (repo.original_srp) * .15)

                                WHEN DATEDIFF(MONTH, (CONVERT(DATE, repo.date_sold)), GETDATE()) >= 25
                                    THEN (repo.original_srp) - (ISNULL(total_parts.total_parts_price, 0) + (repo.original_srp) * .20)
                            ELSE 0 END
                        )
                        ELSE appraise.approved_price
                    END AS current_appraised,
                    DATEDIFF(DAY, CONVERT(DATE, repo.date_surrender), GETDATE()) AS aging,
                    received.is_sold,
                    files.total_upload_required_files
                FROM repo_details repo
                INNER JOIN recieve_unit_details received ON repo.id = received.repo_id
                LEFT JOIN customer_profile customer ON repo.customer_acumatica_id = customer.id
                LEFT JOIN brands brand ON repo.brand_id = brand.id
                LEFT JOIN unit_models model ON repo.model_id = model.id
                LEFT JOIN unit_colors color ON repo.color_id = color.id
                LEFT JOIN branches branch ON repo.branch_id = branch.id
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
                ) files ON repo.id = files.repo_id
                LEFT JOIN (
                    SELECT req.branch, req.repo_id, req.approved_price
                    FROM (
                        SELECT MAX(id) as latest_id, repo_id
                        FROM request_approvals
                        WHERE status = 1
                        GROUP BY repo_id
                    ) sub
                    INNER JOIN request_approvals req ON sub.latest_id = req.id
                ) appraise ON repo.id = appraise.repo_id
                LEFT JOIN (
                    SELECT rud.repo_id, SUM(price) total_parts_price
                    FROM recieve_unit_details rud
                    LEFT JOIN recieve_unit_spare_parts rus ON rud.id = rus.recieve_id
                    WHERE rus.is_deleted = 0 and (rus.refurb_decision IS NULL OR rus.refurb_decision = 'na')
                    GROUP BY rud.repo_id
                ) AS total_parts ON total_parts.repo_id = repo.id
                WHERE received.is_sold = 'N' AND received.status != 4
                    AND ISNULL(files.total_upload_required_files, 0) = (SELECT COUNT(*) FROM files WHERE isRequired = 1 AND status = 1)
                    AND (
                            (
                                @roleName = 'Warehouse Custodian'
                                    AND NOT EXISTS (
                                        SELECT repo_id FROM sold_units WHERE repo_id = repo.id AND branch = @branchId
                                    )
                                    AND NOT EXISTS (
                                        SELECT repo_id FROM request_refurbishes WHERE repo_id = repo.id AND status IN (0,1,3) AND branch = @branchId
                                    )
                                    AND NOT EXISTS (
                                        SELECT
                                            c.repo_id FROM stock_transfer_approval AS a
                                        INNER JOIN stock_transfer_unit AS b ON b.stock_transfer_id = a.id
                                        INNER JOIN recieve_unit_details AS c ON c.id = b.recieved_unit_id
                                        WHERE a.status = 0 AND c.repo_id = repo.id AND repo.branch_id = @branchId
                                    )
                                    AND repo.branch_id = @branchId
                            )
                            OR
                            (
                                @roleName != 'Warehouse Custodian'
                                    AND NOT EXISTS (
                                        SELECT repo_id FROM sold_units WHERE repo_id = repo.id
                                    )
                                    AND NOT EXISTS (
                                        SELECT repo_id FROM request_refurbishes WHERE repo_id = repo.id AND status IN (0, 1, 3)
                                    )
                                    AND NOT EXISTS (
                                        SELECT
                                            c.repo_id FROM stock_transfer_approval AS a
                                        INNER JOIN stock_transfer_unit AS b ON b.stock_transfer_id = a.id
                                        INNER JOIN recieve_unit_details AS c ON c.id = b.recieved_unit_id
                                        WHERE a.status = 0 AND c.repo_id = repo.id
                                    )
                            )
                        )
                ORDER BY repo.created_at DESC
                ",
                [ 'roleName' => Auth::user()->userrole, 'branchId' => Auth::user()->branch ]
            );

            return  Datatables::of($stmt)->make(true);

        } catch (\Throwable $th) {
            return $this->sendError($th->errorInfo[2]);
        }
    }

    public function UnitInventoryMasterList(Request $request)
    {
        try {
            $cteQuery = $this->cteQuery();

            $stmt = DB::select(
                "DECLARE @roleName Nvarchar(100) = :roleName, @branchId Int = :branchId, @requestBranchId Nvarchar(10) = :requestBranchId;

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
		            location.name AS location_name,
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
                    color.name AS color_name,
                    repo.original_srp AS selling_price,
                    CASE
                        WHEN sold.sold_date IS NOT NULL
                            THEN DATEDIFF(DAY, CONVERT(DATE, repo.date_surrender), CONVERT(DATE, sold.sold_date))
                        ELSE
                            DATEDIFF(DAY, CONVERT(DATE, repo.date_surrender), GETDATE())
                    END AS aging_days,
                    1 AS quantity,
                    1 AS available,
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
                    END AS current_status,
                    IIF(stock.stock_approval_id IS NOT NULL, 1, 0) AS is_stock_transferred,
                    IIF(appraisal.id IS NOT NULL, 1, 0) AS is_appraised,
                    IIF(refurbish.id IS NOT NULL, 1, 0) AS is_refurbished,
                    IIF(settlement.id IS NOT NULL, 1, 0) AS is_settled,
                    IIF(sold.id IS NOT NULL, 1, 0) AS is_sold


                FROM repo_details AS repo
                INNER JOIN recieve_unit_details AS received ON repo.id = received.repo_id AND repo.branch_id = received.branch
                LEFT JOIN branches AS branch ON repo.branch_id = branch.id
                LEFT JOIN customer_profile AS customer ON repo.customer_acumatica_id = customer.id
                LEFT JOIN brands AS brand ON repo.brand_id = brand.id
                LEFT JOIN unit_models AS model ON repo.model_id = model.id
                LEFT JOIN unit_colors AS color ON repo.color_id = color.id
	            LEFT JOIN locations AS location ON location.id = repo.location

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

                WHERE repo.branch_id = CASE
                    WHEN @roleName = 'Warehouse Custodian' THEN @branchId
                    WHEN @roleName != 'Warehouse Custodian' AND @requestBranchId != 0 THEN @requestBranchId
                    ELSE repo.branch_id  -- allow all branches
                END
                ORDER BY repo.id DESC",
                [ 'roleName' => Auth::user()->userrole, 'branchId' => Auth::user()->branch, 'requestBranchId' => $request->branchId ]
            );
            $datatables = Datatables::of($stmt);

            return $datatables->make(true);
        } catch (\Throwable $th) {
            return $this->sendError($th->errorInfo[2]);
        }
    }

    public function UnitHistory($repo_id)
    {

        try {

            return DB::select(
                "SELECT
                    UPPER(branch.name) AS branch,
                    UPPER(
                        CONCAT(customer.firstname,
                            CASE
                                WHEN customer.middlename != '' THEN CONCAT(' ', customer.middlename, ' ')
                            ELSE ' ' END, customer.lastname
                        )
                    ) AS exOwner,
                    UPPER(brand.brandname) AS brand,
                    UPPER(model.model_name) AS model,
                    UPPER(repo.model_engine) AS engine,
                    UPPER(repo.model_chassis) AS chassis,
                    FORMAT(repo.created_at, 'MMM dd, yyyy') AS date_inserted,
                    FORMAT(received.date_approved, 'MMM dd, yyyy') AS date_tagged,
                    FORMAT(appraise.date_approved, 'MMM dd, yyyy') AS date_appraised,
                    FORMAT(refurbish.updated_at, 'MMM dd, yyyy') AS date_refurbish,
                    FORMAT([transfer].updated_at, 'MMM dd, yyyy') AS date_transfer,
                    FORMAT(transfer_received.updated_at, 'MMM dd, yyyy') AS date_received
                FROM repo_details repo
                INNER JOIN recieve_unit_details received ON repo.id = received.repo_id
                LEFT JOIN branches branch ON repo.branch_id = branch.id
                LEFT JOIN customer_profile customer ON repo.customer_acumatica_id = customer.id
                LEFT JOIN brands brand ON repo.brand_id = brand.id
                LEFT JOIN unit_models model ON repo.model_id = model.id
                LEFT JOIN (
                    SELECT sub.repo_id, history.appraised_price AS approved_price, history.created_at AS date_approved
                    FROM (
                        SELECT
                            request.repo_id, MAX(history.appraisal_req_id) AS appraisal_req_id
                        FROM request_approvals request
                        LEFT JOIN appraisal_histories history ON request.id = history.appraisal_req_id
                        WHERE request.status = 1
                        GROUP BY request.repo_id
                    ) sub
                    LEFT JOIN appraisal_histories history ON sub.appraisal_req_id = history.id
                ) AS appraise ON repo.id = appraise.repo_id
                LEFT JOIN (
                    SELECT sub.repo_id, settle.updated_at
                    FROM (
                        SELECT
                            MAX(id) AS latest_id, repo_id
                        FROM request_refurbishes
                        WHERE status = 4
                        GROUP BY repo_id
                    ) sub
                    LEFT JOIN refurbish_processes settle ON sub.latest_id = settle.refurbish_req_id
                ) refurbish ON repo.id = refurbish.repo_id
                LEFT JOIN (
                    SELECT
                        rud1.repo_id, sta1.id, sta1.updated_at
                    FROM (
                        SELECT MAX(sta.id) AS approvalid, MAX(stu.recieved_unit_id) AS recievedid, MAX(stu.id) AS unitid
                        FROM stock_transfer_approval sta
                        INNER JOIN stock_transfer_unit stu ON sta.id = stu.stock_transfer_id
                        GROUP BY stu.recieved_unit_id
                    ) sub
                    INNER JOIN stock_transfer_approval sta1 ON sub.approvalid = sta1.id
                    INNER JOIN stock_transfer_unit stu1 ON sub.unitid = stu1.id AND sub.approvalid = stu1.stock_transfer_id AND sub.recievedid = stu1.recieved_unit_id
                    INNER JOIN recieve_unit_details rud1 ON sub.recievedid = rud1.id
                    WHERE sta1.status = 1
                ) [transfer] ON repo.id = [transfer].repo_id
                LEFT JOIN (
                    SELECT
                        unit.id, received.repo_id, received.updated_at
                    FROM stock_transfer_unit unit
                    INNER JOIN recieve_unit_details received ON unit.recieved_unit_id = received.id
                ) transfer_received ON repo.id = transfer_received.repo_id AND [transfer].id = transfer_received.id
                WHERE repo.id = :repo_id",
                [ 'repo_id' => $repo_id ]
            );

        } catch (\Throwable $th) {
            return $this->sendError($th->errorInfo[2]);
        }
    }

    public function appraisalHistory()
    {

        try {


            $received_units = DB::select(
                "SELECT
            repo.model_engine, repo.model_chassis,repo.branch_id
            ,branches.name as branchname,brands.brandname,model.model_name,color.name as color,
            history.old_price, history.appraised_price,history.date_approved,history.remarks,
            CONCAT(requestor.firstname,' ',requestor.lastname) as maker, CONCAT(approver.firstname,' ',approver.lastname) as approver
            from repo_details as repo
            inner join branches on branches.id = repo.branch_id
            inner join  brands on brands.id = repo.brand_id
            inner join unit_models as model on model.id = repo.model_id
            inner join unit_colors as color on color.id = repo.color_id
            inner join request_approvals as appraisal on appraisal.repo_id = repo.id
            inner join appraisal_histories  as history on history.appraisal_req_id = appraisal.id
            inner join users as requestor on requestor.id = appraisal.created_by
            inner join users as approver on approver.id = appraisal.approver
            order by history.created_at desc"
            );

            return $received_units;
        } catch (\Throwable $th) {
            return $this->sendError($th->errorInfo[2]);
        }
    }

    public function calculateSuggestedPrice($id, $firstdatesold)
    {

        try {

            $details = DB::table('recieve_unit_details AS rud')
                ->select(
                    'repo.id AS repo_id', 'rud.id AS receive_id', 'repo.original_srp',
                    DB::raw('ISNULL(total_parts.total_parts_price, 0) AS total_parts_price'),
                    DB::raw('ISNULL(parts.total_cost_parts, 0) AS total_cost_parts'),
                    DB::raw('CASE
                            WHEN DATEDIFF(MONTH, (CONVERT(DATE, repo.date_sold)), GETDATE()) >= 1 and DATEDIFF(MONTH, (CONVERT(DATE, repo.date_sold)), GETDATE()) <= 6
                                THEN repo.original_srp * .05

                            WHEN DATEDIFF(MONTH, (CONVERT(DATE, repo.date_sold)), GETDATE()) >= 7 and DATEDIFF(MONTH, (CONVERT(DATE, repo.date_sold)), GETDATE()) <= 12
                                THEN repo.original_srp * .10

                            WHEN DATEDIFF(MONTH, (CONVERT(DATE, repo.date_sold)), GETDATE()) >= 13 and DATEDIFF(MONTH, (CONVERT(DATE, repo.date_sold)), GETDATE()) <= 24
                                THEN repo.original_srp * .15

                            WHEN DATEDIFF(MONTH, (CONVERT(DATE, repo.date_sold)), GETDATE()) >= 25
                                THEN repo.original_srp * .20
                        ELSE 0 END AS depreciation_cost'
                    ),
                    DB::raw('
                        CASE
                            WHEN DATEDIFF(MONTH, (CONVERT(DATE, repo.date_sold)), GETDATE()) >= 1 and DATEDIFF(MONTH, (CONVERT(DATE, repo.date_sold)), GETDATE()) <= 6
                                THEN (repo.original_srp) - (ISNULL(total_parts.total_parts_price, 0) + (repo.original_srp ) * .05)

                            WHEN DATEDIFF(MONTH, (CONVERT(DATE, repo.date_sold)), GETDATE()) >= 7 and DATEDIFF(MONTH, (CONVERT(DATE, repo.date_sold)), GETDATE()) <= 12
                                THEN (repo.original_srp) - (ISNULL(total_parts.total_parts_price, 0) + (repo.original_srp) * .10)

                            WHEN DATEDIFF(MONTH, (CONVERT(DATE, repo.date_sold)), GETDATE()) >= 13 and DATEDIFF(MONTH, (CONVERT(DATE, repo.date_sold)), GETDATE()) <= 24
                                THEN (repo.original_srp) - (ISNULL(total_parts.total_parts_price, 0) + (repo.original_srp) * .15)

                            WHEN DATEDIFF(MONTH, (CONVERT(DATE, repo.date_sold)), GETDATE()) >= 25
                                THEN (repo.original_srp) - (ISNULL(total_parts.total_parts_price, 0) + (repo.original_srp) * .20)
                        ELSE 0 END AS standard_matrix_value'
                    ),
                    DB::raw('DATEDIFF(DAY, (CONVERT(DATE, repo.date_sold)), GETDATE()) AS date_age'),
                )
                ->join('repo_details AS repo', 'rud.repo_id', 'repo.id')
                ->leftJoin(
                    DB::raw("(
                        SELECT rud.repo_id, SUM(price) total_parts_price
                        FROM recieve_unit_details rud
                        LEFT JOIN recieve_unit_spare_parts rus ON rud.id = rus.recieve_id
                        WHERE rus.is_deleted = 0 and (rus.refurb_decision IS NULL OR rus.refurb_decision = 'na')
                        GROUP BY rud.repo_id
                    ) total_parts"),
                    "total_parts.repo_id", "=", "repo.id"
                )
                ->leftJoin(
                    DB::raw("(
                        SELECT rud.repo_id, SUM(price) total_cost_parts
                        FROM recieve_unit_details rud
                        LEFT JOIN recieve_unit_spare_parts rus ON rud.id = rus.recieve_id
                        WHERE rus.is_deleted = 0 and refurb_decision = 'done'
                        GROUP BY rud.repo_id
                    ) AS parts"),
                    "parts.repo_id", "=", "repo.id"
                )
                ->where('rud.id', $id)
            ->first();


            $has_matrix_setup = unit_aging::count();

            if ($has_matrix_setup == 0) {
                return  $this->sendError('Validation Error.', 'No depriciation matrix. Please contact your system administrator!');
            }

            return [
                'days' => $details->date_age,
                'depreciation' => $details->depreciation_cost,
                'emdp' => $details->total_parts_price,
                't_mdp' => ($details->depreciation_cost + $details->total_parts_price),
                'sp' => $details->standard_matrix_value
            ];
            // old

            // $received_units = receive_unit::with(['spare_parts_details', 'repo_details'])
            //     ->where('id', $id)->first();

            // $tmdp = 0;

            // for ($i = 0; $i < count($received_units->spare_parts_details); $i++) {
            //     $data = $received_units->spare_parts_details[$i];
            //     if($data->refurb_decision != 'done'){
			// 		$tmdp += $data->price;
			// 	}
            // }

            // $refurbish = request_refurbish::with(['missingParts'])
            //     ->where('repo_id', $id)->where('status', 3)->first();
            // $refurb = 0;

            // if ($refurbish) {
            //     for ($i = 0; $i < count($refurbish->missingParts); $i++) {
            //         $data = $refurbish->missingParts[$i];
            //         $refurb += $data->price;
            //     }
            // }

            // $start = Carbon::parse($firstdatesold);
            // $end = Carbon::parse(Carbon::now());

            // $unit_age = $end->diffInDays($start);

            // $has_matrix_setup = unit_aging::count();

            // if ($has_matrix_setup == 0) {
            //     return  $this->sendError('Validation Error.', 'No depriciation matrix. Please contact your system administrator!');
            // }

            // switch ($unit_age) {
            //     case ($unit_age <= 180):
            //         $unit_criteria = unit_aging::where('days', '>=', $unit_age)->where('days', '<=', 180)->first();
            //         break;
            //     case ($unit_age <= 360):
            //         $unit_criteria = unit_aging::where('days', '>=', $unit_age)->where('days', '<=', 360)->first();
            //         break;
            //     case ($unit_age <= 720):
            //         $unit_criteria = unit_aging::where('days', '>=', $unit_age)->where('days', '<=', 720)->first();
            //         break;
            //     case ($unit_age >= 721):
            //         $unit_criteria = unit_aging::latest('days')->first();
            //         break;
            // }

            // //start of totaling the suggested repo price

            // $depreciation = ($received_units->principal_balance) * ('0.' . ($unit_criteria->Depreceiation_Cost < 10 ? '0' . $unit_criteria->Depreceiation_Cost : $unit_criteria->Depreceiation_Cost));
            // $md_max_limiter = ($received_units->principal_balance) * ('0.' . ($unit_criteria->Estimated_Cost_of_MD_Parts < 10 ? '0' . $unit_criteria->Estimated_Cost_of_MD_Parts : $unit_criteria->Estimated_Cost_of_MD_Parts));
            // $total_md = $tmdp < $md_max_limiter ? $tmdp : $md_max_limiter;
            // $immidiate_sales_value =  ($received_units->principal_balance) * ('0.' . ($unit_criteria->Immediate_Sales_Value < 10 ? '0' . $unit_criteria->Immediate_Sales_Value : $unit_criteria->Immediate_Sales_Value));

            // // $suggested_price = ($received_units->principal_balance) - ($depreciation + ($refurb > 0 ? 0 : $total_md));
            // $suggested_price = ($received_units->principal_balance) - (($refurb > 0 ? 0 : $total_md) > $depreciation ? $depreciation : $total_md);

            // //end of computation

            // return [
            //     'days' => $details->date_age,
            //     'depreciation' => $depreciation,
            //     'emdp' => $refurb > 0 ? 0 : $md_max_limiter,
            //     't_mdp' => $refurb > 0 ? 0 : $tmdp,
            //     'sp' => $refurb > 0 ? ($suggested_price + $refurb) : $suggested_price
            // ];
        } catch (\Throwable $th) {
            return $this->sendError($th->errorInfo[2]);
        }
    }

    public function requestRepoPriceApproval(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'received_unit_id'           => 'required',
            'repo_id'                    => 'required',
            'branch'                     => 'required',
            'unit_age_days'              => 'required',
            'depreciation_cost'          => 'required',
            'estimated_missing_dmg_parts'=> 'required',
            'total_missing_dmg_parts'    => 'required',
            'suggested_price'            => 'required',
            'approved_price'             => 'required',
            'module_id'                  => 'required',
            'remarks'                    => 'nullable',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }

        return DB::transaction(function () use ($request) {
            $userId = Auth::id();

            // Check existing request
            $existing = RequestApproval::where('received_unit_id', $request->received_unit_id)
                ->whereIn('status', [0, 2]) // pending or disapproved
                ->orderByDesc('id')
                ->first();

            if ($existing && $existing->status == 0) {
                return $this->sendError('There is already a pending approval for this unit.');
            }

            $firstApproverId = $this->assignFirstApprover((int) $request->module_id);

            if (!$firstApproverId) {
                throw new \Exception("No approver found for this module.");
            }

            if ($existing && $existing->status == 2) {
                // Reuse disapproved → reset to pending
                $existing->update([
                    'approved_price' => $request->approved_price,
                    'remarks'        => null,
                    'status'         => 0,
                    'created_by'     => $userId,
                ]);

                receive_unit::where('id', $request->received_unit_id)
                    ->update(['status' => 0, 'approver' => $firstApproverId]);

            } else {
                $input =  $request->all();

                $create = RequestApproval::create([
                    'received_unit_id' => $input['received_unit_id'],
                    'repo_id' => $input['repo_id'],
                    'branch' => $input['branch'],
                    'unit_age_days' => $input['unit_age_days'],
                    'depreciation_cost' => $input['depreciation_cost'],
                    'estimated_missing_dmg_parts' => $input['estimated_missing_dmg_parts'],
                    'total_missing_dmg_parts' => $input['total_missing_dmg_parts'],
                    'suggested_price' => $input['suggested_price'],
                    'approved_price' => $input['approved_price'],
                    'approver' => $firstApproverId,
                    'status' => '0',
                    'remarks' => $input['remarks'],
                    'created_by' => Auth::id(),
                ]);

                $transactionNo = $this->generateTransactionNumber('rdaf', $create->created_at);
                $create->update(['rdaf_transaction_number' => $transactionNo]);
            }

            return $this->sendResponse([], 'Request for price appraisal successfully saved!');
        });
    }

    public function submitRequestDecision(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'data_id'   => 'required|integer',
                'module_id' => 'required|integer',
                'remarks'   => 'required|string',
                'status'    => 'required', // 1=approve, 2=disapprove
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors());
            }

            $userId   = Auth::id();
            $roleId   = user_role::where('user_role_name', Auth::user()->userrole)->value('id');
            $moduleId = $request->module_id;
            $recordId = $request->data_id;

            $data = RequestApproval::where('received_unit_id', $recordId)
                ->orderByDesc('id')
                ->first();

            // check if already approved
            $check = $this->checkIfApproved($moduleId, $data->id, $roleId);
            if ($check['status']) {
                $approverName = $check['name'] ?? 'Unknown Approver';
                return $this->sendError(
                    "This request has already been approved by {$approverName}.",
                    ['approver' => $check['approver']]
                );
            }

            if (!$data) {
                return $this->sendError('Request not found.');
            }

            DB::beginTransaction();

            $currentApprover = $this->getCurrentApprover($moduleId, $roleId);
            $nextApproverId = null;

            if ($request->status == 1) {
                $this->logApproval($moduleId, $data->id, $userId, $roleId, $currentApprover->level, 'A');

                $nextApprover = $this->getNextApprover($moduleId, $currentApprover->level);

                if ($nextApprover) {
                    $nextApproverId = $nextApprover->approverId;
                } else {
                    receive_unit::where('id', $recordId)->update(['status' => 1]);

                    RequestApproval::where('id', $data->id)->update(['status' => 1]);

                    appraisal_history::create([
                        'appraisal_req_id' => $data->id,
                        'branch'           => Auth::user()->branch,
                        'old_price'        => $request->old_price,
                        'appraised_price'  => $request->approved_price,
                        'date_approved'    => Carbon::now(),
                        'remarks'          => $request->remarks,
                        'approver'         => $userId,
                    ]);
                }
            }

            if ($request->status == 2) {
                $this->logApproval($moduleId, $data->id, $userId, $roleId, $currentApprover->level, 'D');

                receive_unit::where('id', $recordId)
                    ->update(['status' => 2]);

                RequestApproval::where('id', $data->id)->update([
                    'status'   => 2,
                    'approver' => $roleId
                ]);
            }

            RequestApproval::where('id', $data->id)->update([
                'approver'      => $nextApproverId ?? $roleId,
                'date_approved' => $request->status == 1 ? now() : null,
                'remarks'       => $request->remarks,
                'edited_price'  => $request->edit_price ? $data->approved_price : null,
            ]);

            DB::commit();

            return $this->sendResponse([],
                $request->status == 1
                    ? 'Price appraisal successfully approved!'
                    : 'Price appraisal successfully disapproved!'
            );

        } catch (\Throwable $th) {
            DB::rollBack();
			$this->rollBaclDecision($request->module_id, $data->id, $roleId);
			return $this->sendError($th->getMessage());
        }
    }

    public function tagUnitSale(Request $request)
    {

        try {

            $rec_id = null;

            $commonRules = [
                'sold_type'     => 'required',
                'invoice'       => 'required',
                'new_owner'     => 'required',
                'sold_date'     => 'required',
                'srp'           => 'required',

                'pt_receipt_no' => 'required',
                'pt_date'       => 'required',
                'pt_bank'       => 'required',
                'pt_amount'     => 'required',
                'pt_collection_receipt' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
                'pt_notice_to_release'  => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
                'pt_downpayment'        => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            ];

            if ($request->sold_type === 'I') {
                $conditionalRules = [
                    'dp'             => 'required',
                    'monthly'        => 'required',
                    'rebate'         => 'required',
                    'terms'          => 'required',
                    'rate'           => 'required',
                    'interest_rate'  => 'required',
                    'amount_finance' => 'required',
                ];
            } else {
                $conditionalRules = [
                    'amount_paid'    => 'required',
                ];
            }

            $validator = Validator::make($request->all(), array_merge($commonRules, $conditionalRules));

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors());
            }

            $input =  $request->all();
            // $input['status'] = '0';


            $check = sold_unit::where('repo_id', $request->repo_id)->count();

            DB::beginTransaction();


            if ($check > 0) {

                return $this->sendError('Validation Error.', 'Repo is already subject for sales tagging.! Please wait for approval');
            } else {
                //create
                $create = new sold_unit;
                $create->repo_id = $input['repo_id'];
                $create->branch = Auth::user()->branch;
                $create->new_customer = $input['new_owner'];
                $create->invoice_reference_no = $input['invoice'];
                $create->ExternalReference = $input['ExternalReference'];
                $create->AgentID = $input['AgentID'];
                $create->sale_type = $input['sold_type'];
                $create->srp = $input['srp'];
                $create->dp = $input['dp'];
                $create->amount_paid = $input['sold_type'] == 'I' ? $input['dp'] : $input['amount_paid'];
                $create->monthly_amo = $input['monthly'];
                $create->rebate = $input['rebate'];
                $create->terms = $input['terms'];
                $create->sold_date = $input['sold_date'];
                $create->amount_finance = $input['amount_finance'];
                $create->interest_rate = $input['interest_rate'];
                $create->rate = $input['rate'];
                $create->maker = Auth::user()->id;
                $create->approver = '';
                $create->remarks = '';
                $create->pt_receipt_no = $input['pt_receipt_no'];
                $create->pt_date = $input['pt_date'];
                $create->pt_bank = $input['pt_bank'];
                $create->pt_amount = $input['pt_amount'];

                receive_unit::where('repo_id', $input['repo_id'])->update(['sold_type' => $input['sold_type'] ]);



                //check for RNR uploading
                if ($request->rate != '0.03') {
                    $folder_path = 'image/rnr';
                    $directory = public_path($folder_path);
                    if (!File::isDirectory($directory)) {
                        File::makeDirectory($directory, 0777, true, true);
                    }

                    if ($request->rnr != 'null') {
                        $image1 = $request->file("rnr");
                        if ($image1) {
                            $image_name1 = strtoupper(uniqid() . '-' . $image1->getClientOriginalName());
                            $image1->move($directory, $image_name1);
                            // array_push($file_list,[
                            //     'filename' => $image_name1,
                            //     'path' => $folder_path.'/'.$image_name1
                            // ])

                            $create->file_name = $image_name1;
                            $create->path = $folder_path . '/' . $image_name1;
                        }
                    }
                }

                $repo = repo::where('id', $input['repo_id'])->first();

                if ($repo) {
                    $folderName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $repo->model_engine . '-' . $repo->model_chassis);
                    $folder_path = 'image/sales_tagged/' . strtoupper($folderName);
                    $directory = public_path($folder_path);

                    if (!File::isDirectory($directory)) {
                        File::makeDirectory($directory, 0777, true, true);
                    }

                    $image_fields = [
                        'pt_collection_receipt' => 'Collection Receipt',
                        'pt_notice_to_release'  => 'Notice to Release',
                        'pt_downpayment'        => 'Downpayment',
                    ];

                    $images_data = [];
                    $id = 0;

                    foreach ($image_fields as $field => $label) {
                        if ($request->hasFile($field)) {
                            $image = $request->file($field);
                            $label_clean = str_replace(' ', '_', strtolower($label));
                            $image_name = strtoupper(uniqid() . '_' . $label_clean . '.' . $image->getClientOriginalExtension());
                            $image->move($directory, $image_name);

                            $images_data[] = [
                                'id' => $id++,
                                'name' => $label,
                                'directory' => $folder_path . '/' . $image_name,
                            ];
                        }
                    }

                    $create->pt_uploads = json_encode($images_data);
                }

                $create->save();
                $rec_id = $create->id;

                $transactionNo = $this->generateTransactionNumber('sales', $create->created_at);
                $create->transaction_number = $transactionNo;

                $firstApproverId = $this->assignFirstApprover((int) $request->module_id);
                if (!$firstApproverId) {
                    throw new \Exception("No approver found for this module.");
                }
                $create->update([ 'approver' => $firstApproverId ]);
                $create->save();
            }

            DB::commit();

            return $this->sendResponse([], 'Tagging success. Please wait for the approval!');
        } catch (\Throwable $th) {
            return $this->sendError($th->errorInfo[2]);
        }

        //  $update = receive_unit::where('id',$request->received_id)->update(['sold_type' => $request->sold_type, 'is_sold' => 'Y']);

    }

    public function submitTagUnitDecision(Request $request)
    {

        try {

            $validator = Validator::make($request->all(), [
                'id' => 'required',
                'status' => 'required',
                'remarks' => 'required',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors());
            }

            $userId   = Auth::id();
            $roleId   = user_role::where('user_role_name', Auth::user()->userrole)->value('id');
            $moduleId = $request->module_id;
            $recordId = $request->id;

            // ✅ check if already approved
            $check = $this->checkIfApproved($moduleId, $recordId, $roleId);
            if ($check['status']) {
                $approverName = $check['name'] ?? 'Unknown Approver';
                return $this->sendError(
                    "This request has already been approved by {$approverName}.",
                    ['approver' => $check['approver']]
                );
            }

            DB::beginTransaction();

            $currentApprover = $this->getCurrentApprover($moduleId, $roleId);
            $nextApproverId = null;

            // ✅ Approval flow
            if ($request->status == 1) {
                $this->logApproval($moduleId, $recordId, $userId, $roleId, $currentApprover->level, 'A');

                $nextApprover = $this->getNextApprover($moduleId, $currentApprover->level);

                if ($nextApprover) {
                    $nextApproverId = $nextApprover->approverId;
                } else {
                    // no more approvers, mark record as fully approved
                    receive_unit::where('repo_id', $request->repo_id)
                        ->update(['is_sold' => 'Y']);

                    sold_unit::where('id', $request->id)->update([
                        'status' => $request->status,
                        'transaction_number_inventory_out' => $this->generateTransactionNumber('inventory_out', now()),
                        'inventory_out_at' => now(),
                    ]);
                }
            }

            if ($request->status == 2) {
                $this->logApproval($moduleId, $recordId, $userId, $roleId, $currentApprover->level, 'D');

                sold_unit::where('id', $request->id)
                    ->update(['status' => $request->status]);
            }

            sold_unit::where('id', $request->id)
                ->update([
                    'approver' => $nextApproverId ?? $roleId,
                    'remarks' => $request->remarks
                ]);

            DB::commit();

            $msg = $request->status == 1
                ? 'Request for approval successfully approved!'
                : 'Request for approval successfully disapproved!';
            return $this->sendResponse([], $msg);
        } catch (\Throwable $th) {
            $this->rollBaclDecision($request->module_id, $request->id, Auth::user()->id);
            return $this->sendError($th->errorInfo[2]);
        }
    }

    public function cancelSalesTag(Request $request)
    {

        try {
            $validator = Validator::make($request->all(), ['id' => 'required',]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors());
            }

            $remove = DB::table('sold_units')->where('id', $request->id)->delete();


            return $this->sendResponse([], 'success');
        } catch (\Throwable $th) {
            return $this->sendError($th->errorInfo[2]);
        }
    }

    public function updateSaleTagging(Request $request)
    {

        try {

            if ($request->sold_type == 'I') {
                $validator = Validator::make($request->all(), [
                    'sold_type' => 'required',
                    'dp' => 'required',
                    'invoice' => 'required',
                    'monthly' => 'required',
                    'new_owner' => 'required',
                    'rebate' => 'required',
                    'sold_date' => 'required',
                    'srp' => 'required',
                    'terms' => 'required',
                    'rate' => 'required',
                    'interest_rate' => 'required',
                    'amount_finance' => 'required',

                ]);
            } else {
                $validator = Validator::make($request->all(), [
                    'sold_type' => 'required',
                    'invoice' => 'required',
                    'new_owner' => 'required',
                    'sold_date' => 'required',
                    'srp' => 'required',
                    'amount_paid' => 'required',
                ]);
            }



            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors());
            }

            $arr = [
                'sale_type' => $request->sold_type,
                'dp' => $request->dp,
                'amount_paid' => $request->sold_type == 'I' ? $request->dp : $request->amount_paid,
                'invoice_reference_no' => $request->invoice,
                'ExternalReference' => $request->ExternalReference,
                'AgentID' => $request->AgentID,
                'monthly_amo' => $request->monthly,
                'new_customer' => $request->new_owner,
                'rebate' => $request->rebate,
                'sold_date' => $request->sold_date,
                'srp' => $request->srp,
                'terms' => $request->terms,
                'status' => '0',
                'amount_finance' => $request->amount_finance,
                'interest_rate' => $request->interest_rate,
                'rate' => $request->rate,
            ];

            if ($request->rate != '0.03') {
                $folder_path = 'image/rnr';
                $directory = public_path($folder_path);
                if (!File::isDirectory($directory)) {
                    File::makeDirectory($directory, 0777, true, true);
                }

                if ($request->rnr != 'null') {
                    $image1 = $request->file("rnr");
                    if ($image1) {
                        $image_name1 = strtoupper(uniqid() . '-' . $image1->getClientOriginalName());
                        $image1->move($directory, $image_name1);
                        // array_push($file_list,[
                        //     'filename' => $image_name1,
                        //     'path' => $folder_path.'/'.$image_name1
                        // ])

                        $arr['file_name'] = $image_name1;
                        $arr['path'] = $folder_path . '/' . $image_name1;
                    }
                }
            }

            $updateRequest = sold_unit::where('id', $request->id)
                ->update($arr);

            return $this->sendResponse([], 'Request Successfully updated!');
        } catch (\Throwable $th) {
            return $this->sendError($th->errorInfo[2]);
        }
    }

    public function appraisalActivityLog($requestid)
    {

        try {

            $data = DB::table('appraisal_histories as b')
                ->join('users as c', 'c.id', 'b.approver')
                ->select('b.remarks', 'b.date_disapproved', DB::raw('CONCAT(c.firstname,c.middlename,c.lastname) as approver'))
                ->get();

            return $data;
        } catch (\Throwable $th) {
            return $this->sendError($th->errorInfo[2]);
        }
    }

    public function agentList()
    {
        try {

            return $this->getSalesAgentList();
        } catch (\Throwable $th) {
            return $this->sendError($th->errorInfo[2]);
        }
    }

    public function appraisedUnitList(Request $request)
    {
        try {
            $statusFilter = $request->input('status');

            $stmt = DB::table('repo_details as repo')
                ->join('branches as br', 'repo.branch_id', 'br.id')
                ->join('brands as brd', 'repo.brand_id', 'brd.id')
                ->join('unit_models as mdl', 'repo.model_id', 'mdl.id')
                ->join('unit_colors as color', 'repo.color_id', 'color.id')
                ->join('customer_profile as old_owner', 'repo.customer_acumatica_id', 'old_owner.id')
                ->join('request_approvals as appraised', 'repo.id', 'appraised.repo_id')
                ->select(
                    'appraised.id',
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
                    'appraised.date_approved',
                    'appraised.approved_price',
                    DB::raw("
                        CASE appraised.status
                            WHEN 0 THEN 'PENDING'
                            WHEN 1 THEN 'APPROVED'
                            ELSE 'DISAPPROVED'
                        END as status
                    "),
                    'appraised.created_at'
                );

            if (Auth::user()->userrole == 'Warehouse Custodian') {
                $stmt->where('repo.branch_id', Auth::user()->branch);
            }

            if ($statusFilter !== null && $statusFilter !== 'all') {
                $stmt->where('appraised.status', (int) $statusFilter);
            }

            return Datatables::of($stmt)
                ->order(function ($q) {
                    $q->orderByDesc('appraised.created_at');
                })
                ->make(true);

        } catch (\Throwable $th) {
            return $this->sendError($th->errorInfo[2]);
        }
    }
}
