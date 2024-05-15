<?php

namespace App\Http\Controllers\api_v1;

use Illuminate\Http\Request;
use App\Http\Controllers\api_v1\BaseController as BaseController;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use App\Models\RequestApproval;
use App\Models\unit_aging;
use App\Models\receive_unit;
use App\Models\sold_unit;
use App\Http\Traits\helper;
use App\Http\Traits\acumaticaService;
use App\Http\Traits\resuableQuery;
use Carbon\Carbon;
use App\Models\approval_matrix_setting;
use App\Models\request_refurbish;
use App\Models\refurbish_detail;
use App\Models\appraisal_history;

class RequestApprovalController extends BaseController
{
    //
    use helper, acumaticaService, resuableQuery; //helper traits

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
                                WHEN DATEDIFF(MONTH, (CONVERT(DATE, repo.date_sold)), repo.date_surrender) >= 1 and DATEDIFF(MONTH, (CONVERT(DATE, repo.date_sold)), repo.date_surrender) <= 6
                                    THEN (repo.original_srp + ISNULL(parts.total_cost_parts, 0)) - (ISNULL(total_parts.total_parts_price, 0) + (repo.original_srp ) * .05)

                                WHEN DATEDIFF(MONTH, (CONVERT(DATE, repo.date_sold)), repo.date_surrender) >= 7 and DATEDIFF(MONTH, (CONVERT(DATE, repo.date_sold)), repo.date_surrender) <= 12
                                    THEN (repo.original_srp + ISNULL(parts.total_cost_parts, 0)) - (ISNULL(total_parts.total_parts_price, 0) + (repo.original_srp) * .10)

                                WHEN DATEDIFF(MONTH, (CONVERT(DATE, repo.date_sold)), repo.date_surrender) >= 13 and DATEDIFF(MONTH, (CONVERT(DATE, repo.date_sold)), repo.date_surrender) <= 24
                                    THEN (repo.original_srp + ISNULL(parts.total_cost_parts, 0)) - (ISNULL(total_parts.total_parts_price, 0) + (repo.original_srp) * .15)

                                WHEN DATEDIFF(MONTH, (CONVERT(DATE, repo.date_sold)), repo.date_surrender) >= 25
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
                    AND ISNULL(files.total_upload_required_files, 0) = (SELECT COUNT(*) FROM files WHERE isRequired = 1 AND status = 1)
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
                ",
                [ 'roleName' => Auth::user()->userrole, 'branchId' => Auth::user()->branch ]
            );

            return response()->json(
                [
                    'data' => $stmt,
                    'role' =>  'Maker'
                ]
            );
        } catch (\Throwable $th) {
            return $this->sendError($th->errorInfo[2]);
        }
    }

    public function getAllReceivedUnit($moduleid)
    {
        try {
            $cteQuery = $this->cteQuery();

            $stmt = DB::select("
                DECLARE @module INT = :module, @userId INT = :userId;
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
                    CONCAT(holder.firstname,holder.middlename,holder.lastname) as current_holder,
                    CONCAT(maker.firstname,maker.middlename,maker.lastname) as requestor,
                    color.name as color,
                    received.principal_balance,
                    CASE
                        WHEN appraise.approved_price IS NULL THEN (
                            CASE
                                WHEN DATEDIFF(MONTH, (CONVERT(DATE, repo.date_sold)), repo.date_surrender) >= 1 and DATEDIFF(MONTH, (CONVERT(DATE, repo.date_sold)), repo.date_surrender) <= 6
                                    THEN (repo.original_srp + ISNULL(parts.total_cost_parts, 0)) - (ISNULL(total_parts.total_parts_price, 0) + (repo.original_srp ) * .05)

                                WHEN DATEDIFF(MONTH, (CONVERT(DATE, repo.date_sold)), repo.date_surrender) >= 7 and DATEDIFF(MONTH, (CONVERT(DATE, repo.date_sold)), repo.date_surrender) <= 12
                                    THEN (repo.original_srp + ISNULL(parts.total_cost_parts, 0)) - (ISNULL(total_parts.total_parts_price, 0) + (repo.original_srp) * .10)

                                WHEN DATEDIFF(MONTH, (CONVERT(DATE, repo.date_sold)), repo.date_surrender) >= 13 and DATEDIFF(MONTH, (CONVERT(DATE, repo.date_sold)), repo.date_surrender) <= 24
                                    THEN (repo.original_srp + ISNULL(parts.total_cost_parts, 0)) - (ISNULL(total_parts.total_parts_price, 0) + (repo.original_srp) * .15)

                                WHEN DATEDIFF(MONTH, (CONVERT(DATE, repo.date_sold)), repo.date_surrender) >= 25
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
                LEFT JOIN users holder ON req_app.approver = holder.id
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
                        ( -- approver
                            (SELECT COUNT(approverId) FROM approvers WHERE module_id = @module AND approverId = @userId) = 1
                                AND req_app.status = 0 AND req_app.approver = @userId
                        )
                        OR
                        ( -- not approver possible warehouse custodian
                            (SELECT COUNT(approverId) FROM approvers WHERE module_id = @module AND approverId = @userId) != 1
                                AND req_app.status IN ('0', '2') AND req_app.created_by = @userId
                        )
                    )
                ",
                [ 'module' => $moduleid, 'userId' => Auth::user()->id ]
            );

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

            return response()->json(
                [
                    'data' => $stmt,
                    'role' =>  $role[0]->roles
                ]
            );
        } catch (\Throwable $th) {
            return $this->sendError($th->errorInfo[2]);
        }
    }

    public function SoldUnitMasterList()
    {

        try {

            $received_units = DB::table('repo_details as repo')
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
                )->where('sold_unit.status', '1');

            if (Auth::user()->userrole == 'Warehouse Custodian') {
                $received_units = $received_units->where('repo.branch_id', Auth::user()->branch)->get();
            } else {
                $received_units = $received_units->get();
            }


            return $received_units;
        } catch (\Throwable $th) {
            return $this->sendError($th->errorInfo[2]);
        }
    }

    public function getListForApproval($moduleid)
    {

        try {

            $received_units = DB::table('repo_details as repo')
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
                    'sold_unit.path'
                );

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
                $received_units = $received_units->where('sold_unit.status', '0')->where('sold_unit.approver', Auth::user()->id)->get();
            } else {
                $role = 'Maker';

                $received_units = $received_units->whereIn('sold_unit.status', ['0', '2'])->where('sold_unit.maker', Auth::user()->id)->get();
            }
            $data = ['data' => $received_units, 'role' =>  $role];
            return $data;


            return $received_units;
        } catch (\Throwable $th) {
            return $this->sendError($th->errorInfo[2]);
        }
    }

    public function listForSalesTagging()
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
                                WHEN DATEDIFF(MONTH, (CONVERT(DATE, repo.date_sold)), repo.date_surrender) >= 1 and DATEDIFF(MONTH, (CONVERT(DATE, repo.date_sold)), repo.date_surrender) <= 6
                                    THEN (repo.original_srp) - (ISNULL(total_parts.total_parts_price, 0) + (repo.original_srp ) * .05)

                                WHEN DATEDIFF(MONTH, (CONVERT(DATE, repo.date_sold)), repo.date_surrender) >= 7 and DATEDIFF(MONTH, (CONVERT(DATE, repo.date_sold)), repo.date_surrender) <= 12
                                    THEN (repo.original_srp) - (ISNULL(total_parts.total_parts_price, 0) + (repo.original_srp) * .10)

                                WHEN DATEDIFF(MONTH, (CONVERT(DATE, repo.date_sold)), repo.date_surrender) >= 13 and DATEDIFF(MONTH, (CONVERT(DATE, repo.date_sold)), repo.date_surrender) <= 24
                                    THEN (repo.original_srp) - (ISNULL(total_parts.total_parts_price, 0) + (repo.original_srp) * .15)

                                WHEN DATEDIFF(MONTH, (CONVERT(DATE, repo.date_sold)), repo.date_surrender) >= 25
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
                ",
                [ 'roleName' => Auth::user()->userrole, 'branchId' => Auth::user()->branch ]
            );


            return response()->json($stmt);
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
                {$cteQuery}

                SELECT distinct
                    repo.id AS repo_id,
                    repo.msuisva_form_no AS msuisva,
                    repo.model_engine,
                    repo.model_chassis,
                    repo.branch_id,
                    branches.name AS branchname,
                    brands.brandname,
                    model.model_name,
                    color.name AS color,
                    location.name AS location,
                    CONCAT(old_owner.firstname, ' ', old_owner.middlename, ' ', old_owner.lastname) AS ex_owner,
                    old_owner.firstname AS o_firstname,
                    old_owner.middlename AS o_middlename,
                    old_owner.lastname AS o_lastname,
                    repo.created_at AS date_received,
                    received.principal_balance AS original_srp,
                    DATEDIFF(MONTH, (CONVERT(DATE, repo.date_sold)), repo.date_surrender) AS standard_matrix_month,
                    CASE
                        WHEN appraise.approved_price IS NULL THEN (
                            CASE
                                WHEN DATEDIFF(MONTH, (CONVERT(DATE, repo.date_sold)), repo.date_surrender) >= 1 and DATEDIFF(MONTH, (CONVERT(DATE, repo.date_sold)), repo.date_surrender) <= 6
                                    THEN (repo.original_srp + ISNULL(parts.total_cost_parts, 0)) - (ISNULL(total_parts.total_parts_price, 0) + (repo.original_srp ) * .05)

                                WHEN DATEDIFF(MONTH, (CONVERT(DATE, repo.date_sold)), repo.date_surrender) >= 7 and DATEDIFF(MONTH, (CONVERT(DATE, repo.date_sold)), repo.date_surrender) <= 12
                                    THEN (repo.original_srp + ISNULL(parts.total_cost_parts, 0)) - (ISNULL(total_parts.total_parts_price, 0) + (repo.original_srp) * .10)

                                WHEN DATEDIFF(MONTH, (CONVERT(DATE, repo.date_sold)), repo.date_surrender) >= 13 and DATEDIFF(MONTH, (CONVERT(DATE, repo.date_sold)), repo.date_surrender) <= 24
                                    THEN (repo.original_srp + ISNULL(parts.total_cost_parts, 0)) - (ISNULL(total_parts.total_parts_price, 0) + (repo.original_srp) * .15)

                                WHEN DATEDIFF(MONTH, (CONVERT(DATE, repo.date_sold)), repo.date_surrender) >= 25
                                    THEN (repo.original_srp + ISNULL(parts.total_cost_parts, 0)) - (ISNULL(total_parts.total_parts_price, 0) + (repo.original_srp) * .20)
                            ELSE 0 END
                        )
                        ELSE appraise.approved_price + ISNULL((
                            SELECT SUM(settled_total_cost)
                            FROM transactions
                            WHERE repo_id = repo.id AND
                            row_num > (
                                SELECT row_num
                                FROM transactions
                                WHERE repo_id = repo.id AND source_process = 'appraisal'
                            )
                        ), 0)
                    END AS current_price,
                    req.approved_price,
                    DATEDIFF(DAY, (CONVERT(DATE, repo.date_surrender)), GETDATE()) AS aging, qty.quantity,
                    CASE
                        WHEN sold_count.quantity IS NULL AND transfer_count.quantity IS NULL THEN 1
                        ELSE 0
                    END AS availability,
                    CASE
                        WHEN sold_count.quantity IS NULL AND transfer_count.quantity IS NOT NULL THEN 'FOR TRANSFER'
                        WHEN sold_count.quantity IS NOT NULL AND transfer_count.quantity IS NULL THEN 'FOR SELLING'
                        WHEN refurbish.status = 1 THEN 'SUBJECT FOR REFURBISH'
                        WHEN refurbish.status = 3 THEN 'ONGOING REFURBISH'
                        ELSE 'AVAILABLE'
                    END AS status,
                    received.is_sold,
                    parts.total_cost_parts
                FROM repo_details AS repo
                INNER JOIN recieve_unit_details AS received ON received.repo_id = repo.id
                LEFT JOIN (
                    SELECT rud.repo_id, SUM(price) total_parts_price
                    FROM recieve_unit_details rud
                    LEFT JOIN recieve_unit_spare_parts rus ON rud.id = rus.recieve_id
                    WHERE rus.is_deleted = 0 and (rus.refurb_decision IS NULL OR rus.refurb_decision = 'na')
                    GROUP BY rud.repo_id
                ) AS total_parts ON total_parts.repo_id = repo.id
                LEFT JOIN (
                    SELECT a.repo_id, a.approved_price
                    FROM request_approvals AS a
                    INNER JOIN (
                        SELECT MAX(id) AS id, repo_id
                        FROM request_approvals
                        GROUP BY repo_id
                    ) AS max_request ON max_request.id = a.id
                ) AS req ON req.repo_id = repo.id
                INNER JOIN branches ON branches.id = repo.branch_id
                INNER JOIN brands ON brands.id = repo.brand_id
                INNER JOIN unit_models AS model ON model.id = repo.model_id
                INNER JOIN unit_colors AS color ON color.id = repo.color_id
                INNER JOIN locations AS location ON location.id = repo.location
                INNER JOIN customer_profile AS old_owner ON old_owner.id = repo.customer_acumatica_id
                LEFT JOIN (
                    SELECT id, COUNT(id) AS quantity
                    FROM repo_details
                    GROUP BY id
                ) AS qty ON qty.id = repo.id
                LEFT JOIN (
                    SELECT repo_id, COUNT(repo_id) AS quantity
                    FROM sold_units
                    WHERE status = 0
                    GROUP BY repo_id
                ) AS sold_count ON sold_count.repo_id = repo.id
                LEFT JOIN (
                    SELECT b.recieved_unit_id, COUNT(b.recieved_unit_id) AS quantity
                    FROM stock_transfer_approval a
                    INNER JOIN stock_transfer_unit b ON b.stock_transfer_id = a.id
                    WHERE a.status = 0
                    GROUP BY recieved_unit_id
                ) AS transfer_count ON transfer_count.recieved_unit_id = received.id
                LEFT JOIN (
                    SELECT repo_id, status
                    FROM request_refurbishes
                ) AS refurbish ON refurbish.repo_id = repo.id
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
                ) appraise ON received.id = appraise.received_unit_id
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
                ) parts ON repo.id = parts.repo_id
                WHERE received.is_sold = 'N' AND received.status != 4 AND repo.branch_id = ISNULL([transfer].current_branch, repo.branch_id)
                AND (
                    (@roleName = 'Warehouse Custodian' AND repo.branch_id = @branchId) OR
                    (@roleName != 'Warehouse Custodian' AND @requestBranchId != 0 AND repo.branch_id = @requestBranchId) OR
                    (@roleName != 'Warehouse Custodian' AND @requestBranchId = 0)
                )",
                [ 'roleName' => Auth::user()->userrole, 'branchId' => Auth::user()->branch, 'requestBranchId' => $request->branchId ]
            );

            return response()->json(
                [
                    'data' => $stmt,
                    'role' => Auth::user()->userrole
                ]
            );
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
                            WHEN DATEDIFF(MONTH, (CONVERT(DATE, repo.date_sold)), repo.date_surrender) >= 1 and DATEDIFF(MONTH, (CONVERT(DATE, repo.date_sold)), repo.date_surrender) <= 6
                                THEN repo.original_srp * .05

                            WHEN DATEDIFF(MONTH, (CONVERT(DATE, repo.date_sold)), repo.date_surrender) >= 7 and DATEDIFF(MONTH, (CONVERT(DATE, repo.date_sold)), repo.date_surrender) <= 12
                                THEN repo.original_srp * .10

                            WHEN DATEDIFF(MONTH, (CONVERT(DATE, repo.date_sold)), repo.date_surrender) >= 13 and DATEDIFF(MONTH, (CONVERT(DATE, repo.date_sold)), repo.date_surrender) <= 24
                                THEN repo.original_srp * .15

                            WHEN DATEDIFF(MONTH, (CONVERT(DATE, repo.date_sold)), repo.date_surrender) >= 25
                                THEN repo.original_srp * .20
                        ELSE 0 END AS depreciation_cost'
                    ),
                    DB::raw('
                        CASE
                            WHEN DATEDIFF(MONTH, (CONVERT(DATE, repo.date_sold)), repo.date_surrender) >= 1 and DATEDIFF(MONTH, (CONVERT(DATE, repo.date_sold)), repo.date_surrender) <= 6
                                THEN (repo.original_srp) - (ISNULL(total_parts.total_parts_price, 0) + (repo.original_srp ) * .05)

                            WHEN DATEDIFF(MONTH, (CONVERT(DATE, repo.date_sold)), repo.date_surrender) >= 7 and DATEDIFF(MONTH, (CONVERT(DATE, repo.date_sold)), repo.date_surrender) <= 12
                                THEN (repo.original_srp) - (ISNULL(total_parts.total_parts_price, 0) + (repo.original_srp) * .10)

                            WHEN DATEDIFF(MONTH, (CONVERT(DATE, repo.date_sold)), repo.date_surrender) >= 13 and DATEDIFF(MONTH, (CONVERT(DATE, repo.date_sold)), repo.date_surrender) <= 24
                                THEN (repo.original_srp) - (ISNULL(total_parts.total_parts_price, 0) + (repo.original_srp) * .15)

                            WHEN DATEDIFF(MONTH, (CONVERT(DATE, repo.date_sold)), repo.date_surrender) >= 25
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

        try {

            $rec_id = null;

            $validator = Validator::make($request->all(), [
                'approved_price' => 'required',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors());
            }

            $input =  $request->all();
            $input['status'] = '0';
            $input['created_by'] = Auth::user()->id;

            $check = RequestApproval::where('received_unit_id', $request->received_unit_id)->whereIn('status', [0, 2])->orderBy('id', 'DESC')->first();

            DB::beginTransaction();

            if (!empty($check)) {
                if ($check->status == 0) {
                    return $this->sendError('There is pending approval');
                }

                $getrecord = RequestApproval::select('id')->where('received_unit_id', $request->received_unit_id)->first();

                $update = RequestApproval::where('id', $check->id)
                    ->update(['approved_price' => $request->approved_price, 'status' => '0']);
                $updatestatus = receive_unit::where('id', $request->received_unit_id)->update(['status' => '0']);
                $rec_id = $getrecord->id;
            } else {
                $create = RequestApproval::create($input);
                $rec_id = $create->id;
            }

            $matrix =  $this->ApprovalMatrixActivityLog($request->module_id, $rec_id);

            if ($matrix['status'] == 'error') {
                return $matrix;
            } else {
                //update the first holder of the transaction
                $save_holder = RequestApproval::where('id', $rec_id)->update(['approver' => $matrix['message']]);
            }

            DB::commit();

            return $this->sendResponse([], 'Request for approval successfully saved!');
        } catch (\Throwable $th) {
            return $this->sendError($th->errorInfo[2]);
        }
    }

    public function submitRequestDecision(Request $request)
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

            // get the transaction id
            $data = RequestApproval::where('received_unit_id', $request->data_id)->orderBy('id', 'DESC')->first();

            $first_approver = 0;
            $sequence = 0;
            if ($request->status == 1) {
                $fetch_sequence = $this->approverDecision($request->module_id, $data->id, Auth::user()->id);
                if ($fetch_sequence == 0) {
                    $get_unit = receive_unit::select('principal_balance')->where('id', $request->data_id)->first();

                    $appraisal_log = new appraisal_history;
                    $appraisal_log->appraisal_req_id = $data->id;
                    $appraisal_log->branch = Auth::user()->branch;
                    $appraisal_log->old_price = $request->old_price;
                    $appraisal_log->appraised_price = $request->approved_price;
                    $appraisal_log->date_approved = Carbon::now();
                    $appraisal_log->remarks = $request->remarks;
                    $appraisal_log->approver = Auth::user()->id;
                    $appraisal_log->save();

                    // receive_unit::where('id', $request->data_id)->update(['principal_balance' => $data->approved_price]);
                    receive_unit::where('id', $request->data_id)->update(['status' => $request->status]);
                    RequestApproval::where('id', $data->id)->update(['status' => $request->status]);
                }
                $sequence = $fetch_sequence;
            } else if ($request->status == 2) {
                $fetch_first_approver = $this->disapprovedDecision($request->module_id, $data->id, Auth::user()->id);
                receive_unit::where('id', $request->data_id)->update(['status' => $request->status]);
                RequestApproval::where('id', $data->id)->update(['status' => $request->status, 'approver' => $fetch_first_approver]);
                $first_approver = $fetch_first_approver;
            }

            $arr = [
                'approver' => $first_approver > 0 ? $first_approver : ($sequence == 0 ? Auth::user()->id : $sequence),
                'date_approved' => $request->status == 1 ? Carbon::now() : null,
                'remarks' => $request->remarks
            ];

            if ($request->edit_price) {
                $arr['edited_price'] = $data->approved_price;
            }


            $updateRequest = RequestApproval::where('received_unit_id', $request->data_id)
                ->update($arr);
            $msg = $request->status == 1 ? 'Request for approval successfully approved!' : 'Request for approval successfully disapproved!';
            return $this->sendResponse([], $msg);
        } catch (\Throwable $th) {
            $this->rollBaclDecision($request->module_id, $data->id, Auth::user()->id);
            return $this->sendError($th->errorInfo[2]);
        }
    }

    public function tagUnitSale(Request $request)
    {

        try {

            $rec_id = null;

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

                $create->save();
                $rec_id = $create->id;
            }

            $matrix =  $this->ApprovalMatrixActivityLog($request->module_id, $rec_id);

            if ($matrix['status'] == 'error') {
                return $matrix;
            } else {
                //update the first holder of the transaction
                $save_holder = sold_unit::where('id', $rec_id)->update(['approver' => $matrix['message']]);
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

            DB::beginTransaction();
            $first_approver = 0;
            $sequence = 0;
            if ($request->status == 1) {
                $fetch_sequence = $this->approverDecision($request->module_id, $request->id, Auth::user()->id);
                if ($fetch_sequence == 0) {
                    // $get_salesDetails = sold_unit::where('id', $request->id)->first();

                    // $boolean = $this->create_customer($request->id);
                    // if ($boolean) {
                        receive_unit::where('repo_id', $request->repo_id)->update(['is_sold' => 'Y']);
                        sold_unit::where('id', $request->id)->update(['status' => $request->status]);
                    // }
                }
                $sequence = $fetch_sequence;
            } else if ($request->status == 2) {
                $fetch_first_approver = $this->disapprovedDecision($request->module_id, $request->id, Auth::user()->id);
                sold_unit::where('id', $request->id)->update(['status' => $request->status, 'approver' => $fetch_first_approver]);
                $first_approver = $fetch_first_approver;
            }

            sold_unit::where('id', $request->id)
            ->update([
                'approver' => $first_approver > 0 ? $first_approver : ($sequence == 0 ? Auth::user()->id : $sequence),
                'remarks' => $request->remarks
            ]);

            DB::commit();

            $msg = $request->status == 1 ? 'Request for approval successfully approved!' : 'Request for approval successfully disapproved!';
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

    public function appraisedUnitList()
    {
        try {

            $received_units = DB::table('repo_details as repo')
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
                    DB::raw("CASE WHEN appraised.status = '0' THEN 'PENDING'
            WHEN appraised.status = '1' THEN 'APPROVED' ELSE 'DISAPPROVED' END status"),
                );

            if (Auth::user()->userrole == 'Warehouse Custodian') {
                $received_units = $received_units->where('repo.branch_id', Auth::user()->branch)->get();
            } else {
                $received_units = $received_units->get();
            }


            return $received_units;
        } catch (\Throwable $th) {
            return $this->sendError($th->errorInfo[2]);
        }
    }
}
