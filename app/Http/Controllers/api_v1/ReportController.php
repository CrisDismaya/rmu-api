<?php

namespace App\Http\Controllers\api_v1;

use App\Http\Controllers\api_v1\BaseController as BaseController;
use Illuminate\Support\Facades\DB;
use PDF;

class ReportController extends BaseController
{
	//

	public function generateReport($formType, $recordId, $src)
	{

		$parameter = [
            'recid' => $recordId
        ];

        // muisva
        $stmt = DB::select(
            "SELECT
                UPPER('SUERTE MOTOPLAZA') AS company,
                FORMAT(GETDATE(), 'MMM dd, yyyy') AS today,
                UPPER(branch.name) AS branch,
                UPPER(
                    CONCAT(customer.firstname,
                        CASE
                            WHEN customer.middlename != '' THEN CONCAT(' ', customer.middlename, ' ')
                        ELSE ' ' END, customer.lastname
                    )
                ) AS exOwner_Borrower,
                UPPER(repo.loan_number) AS loan_number,
                FORMAT(repo.date_sold, 'MMM dd, yyyy') AS date_released,
                FORMAT(repo.date_surrender, 'MMM dd, yyyy') AS date_repossessed,
                UPPER(brand.brandname) AS brand,
                UPPER(model.model_name) AS model,
                UPPER(color.name) AS color,
                UPPER(repo.model_engine) AS engine_number,
                UPPER(repo.model_chassis) AS chassis_number,
                UPPER(color.name) AS color,
                repo.original_srp AS original_selling_price,
                received.loan_amount AS original_loan_amount,
                received.principal_balance AS outstanding_loan_balance,
                received.total_payments AS total_payments,
                CASE
                    WHEN repo.last_payment = '1900-01-01' THEN ''
                    ELSE FORMAT(repo.last_payment, 'MMM dd, yyyy')
                END AS last_date_payments,
                UPPER(repo.msuisva_form_no) AS muisva_number,
                'TRANS ASIATIC FINANCE INCORPORATED' AS originating_financing_store,
                UPPER(received.original_owner) AS original_owner,
                UPPER(TRIM(CONCAT(customer.address,' ',barangay.Title,', ',city.Title,', ',province.Title))) AS [address],
                UPPER(repo.mv_file_number) AS mv_file_number,
                repo.year_model AS year_model,
                UPPER(repo.plate_number) AS plate_number,
                UPPER(repo.classification) AS [classification],
                UPPER(repo.unit_documents) AS classification_document_tag,
                UPPER(repo.unit_description) AS classification_description,
                DATEDIFF(MONTH, (CONVERT(DATE, repo.date_sold)), repo.date_surrender) AS standard_matrix_month,
                ISNULL(parts.total_cost_parts, 0) AS total_cost_parts,
                CASE
                    WHEN appraise.approved_price IS NOT NULL THEN 'true'
                    ELSE 'false'
                END AS has_appraised,
                appraise.approved_price AS approved_appraised_price,
                FORMAT(appraise.date_approved, 'MMM dd, yyyy') AS appraise_date_approved,
                UPPER(sold.buyer) AS buyer,
                UPPER(sold.address) AS buyer_address,
                UPPER(sold.sold_approver) AS buyer_approver
            FROM repo_details repo
            INNER JOIN recieve_unit_details received ON repo.id = received.repo_id
            LEFT JOIN customer_profile customer ON repo.customer_acumatica_id = customer.id
            LEFT JOIN brands brand ON repo.brand_id = brand.id
            LEFT JOIN unit_models model ON repo.model_id = model.id
            LEFT JOIN unit_colors color ON repo.color_id = color.id
            LEFT JOIN province province ON customer.provinces = province.OrderNumber
            LEFT JOIN city city ON customer.cities = city.MappingId
            LEFT JOIN barangay barangay ON customer.barangays = barangay.OrderNumber
            LEFT JOIN branches branch ON repo.branch_id = branch.id
            LEFT JOIN locations [location] ON repo.[location] = [location].id
            LEFT JOIN (
                SELECT sub.received_unit_id, history.appraised_price AS approved_price, history.created_at AS date_approved
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
                    received.id AS recieve_id, SUM(parts.actual_price) AS total_cost_parts
                FROM recieve_unit_details received
                INNER JOIN recieve_unit_spare_parts parts ON received.id = parts.recieve_id
                LEFT JOIN (
                    SELECT
                        request.repo_id, settle.status
                    FROM request_refurbishes request
                    INNER JOIN refurbish_processes settle ON request.id = settle.refurbish_req_id
                ) refurbish ON received.repo_id = refurbish.repo_id
                WHERE refurbish.status = 1
                GROUP BY received.id
            ) parts ON received.id = parts.recieve_id
            LEFT JOIN (
                SELECT
                    sold.repo_id,
                    UPPER(
                        CONCAT(customer.firstname,
                            CASE
                                WHEN customer.middlename != '' THEN CONCAT(' ', customer.middlename, ' ')
                            ELSE ' ' END, customer.lastname
                        )
                    ) AS buyer,
                    UPPER(TRIM(CONCAT(customer.address,' ',barangay.Title,', ',city.Title,', ',province.Title))) AS [address],
                    UPPER(
                        CONCAT(users.firstname,
                            CASE
                                WHEN users.middlename != '' THEN CONCAT(' ', users.middlename, ' ')
                            ELSE ' ' END, users.lastname
                        )
                    ) AS sold_approver
                FROM sold_units sold
                LEFT JOIN customer_profile customer ON sold.new_customer = customer.id
                LEFT JOIN province province ON customer.provinces = province.OrderNumber
                LEFT JOIN city city ON customer.cities = city.MappingId
                LEFT JOIN barangay barangay ON customer.barangays = barangay.OrderNumber
                LEFT JOIN users users ON sold.maker = users.id
                WHERE sold.status = 1
            ) sold ON repo.id = sold.repo_id
            WHERE repo.id = :recid",
            $parameter
        );

        $parts = DB::select(
            "SELECT
                spares.[name] AS parts_name, parts.parts_status,
                CASE
                    WHEN parts.actual_price != 0 OR parts.actual_price != null THEN parts.actual_price
                    ELSE parts.price
                END AS parts_price,
                parts.refurb_decision
            FROM repo_details repo
            INNER JOIN recieve_unit_details received ON repo.id = received.repo_id
            INNER JOIN recieve_unit_spare_parts parts ON received.id = parts.recieve_id
            LEFT JOIN spare_parts spares ON parts.parts_id = spares.id
            WHERE parts.is_deleted = 0 AND (parts.refurb_decision = 'na' OR parts.refurb_decision IS NULL)
            AND repo.id = :recid",
            $parameter
        );

        // rdaf
        $rdaf_approver =  DB::select(
            "SELECT
                history.appraised_price,
                UPPER(
                    CONCAT(usrs.firstname,
                        CASE
                            WHEN usrs.middlename != '' THEN CONCAT(' ', usrs.middlename, ' ')
                        ELSE ' ' END, usrs.lastname
                    )
                ) AS fullname,
                FORMAT(history.date_approved, 'MMM dd, yyyy') AS date_approved,
                history.remarks
            FROM repo_details repo
            LEFT JOIN request_approvals appraise ON repo.id = appraise.repo_id
            LEFT JOIN appraisal_histories history ON appraise.id = history.appraisal_req_id
            LEFT JOIN users usrs ON history.approver = usrs.id
            WHERE repo.id = :recid AND appraise.status = 1
            ORDER BY history.created_at DESC",
            $parameter
        );

        // smurf
        $refurbish = DB::select(
            "SELECT
                spares.[name] AS parts_name, parts.parts_status,
                CASE
                    WHEN parts.actual_price != 0 OR parts.actual_price != null THEN parts.actual_price
                    ELSE parts.price
                END AS parts_price,
                parts.refurb_decision
            FROM repo_details repo
            INNER JOIN recieve_unit_details received ON repo.id = received.repo_id
            INNER JOIN recieve_unit_spare_parts parts ON received.id = parts.recieve_id
            LEFT JOIN spare_parts spares ON parts.parts_id = spares.id
            WHERE parts.is_deleted = 0 AND parts.refurb_decision = 'done'
            AND repo.id = :recid",
            $parameter
        );

        $smurf_approver =  DB::select(
            "SELECT
                UPPER(
                    CONCAT(usrs.firstname,
                        CASE
                            WHEN usrs.middlename != '' THEN CONCAT(' ', usrs.middlename, ' ')
                        ELSE ' ' END, usrs.lastname
                    )
                ) AS fullname,
                settle.remarks,
                FORMAT(settle.updated_at, 'MMM dd, yyyy') AS date_approved
            FROM request_refurbishes refurbish
            INNER JOIN (
                SELECT
                    MAX(req.id) AS latest_id, repo_id
                FROM request_refurbishes req
                GROUP BY repo_id
            ) latest_request ON refurbish.id = latest_request.latest_id
            INNER JOIN refurbish_processes settle ON refurbish.id = settle.refurbish_req_id
            LEFT JOIN users usrs ON settle.approver = usrs.id
            WHERE refurbish.repo_id = :recid",
            $parameter
        );

        // sold

        switch (strtoupper($formType)) {
            case 'MUISVA':
                $pdf_file = "muisva";
                $pdf_title = "Motorcycle Unit Insection and Immediate Sales Value Approval Form";
            break;

            case 'RDAF':
                $pdf_file = "rdaf";
                $pdf_title = "ROPA DISPOSAL APPROVAL FORM";
            break;

            case 'SMURF':
                $pdf_file = "smurf";
                $pdf_title = "SURRENDERED MOTORCYCLE UNIT REFURBISHMENT FORM";
            break;

            case 'SOLD':
                $pdf_file = "sold";
                $pdf_title = "DELIVERY RECEIPT";
            break;
        }

		$pdf = PDF::loadView(
            $pdf_file,
			array(
				'Title' =>  $pdf_title,
				'data' => array(
					'datas' => json_encode($stmt),
					'parts' => json_encode($parts),
					'refurbish' => json_encode($refurbish),
					'rdaf_approver' => json_encode($rdaf_approver),
					'smurf_approver' => json_encode($smurf_approver)
				)
			)
		)
        ->setPaper('legal', 'portrait');

		return $pdf->stream();
	}
}
