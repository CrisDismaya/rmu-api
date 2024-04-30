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

		$param = ['recid' => $recordId];

		if ($formType == 'RDAF') {
			$query = DB::select(
				'SELECT
                    brh.name as branch,
                    CONCAT(cus.firstname, cus.middlename, cus.lastname) as customer,
                    cus.address,
                    cus.nationality,
                    cus.source_of_income,
                    cus.marital_status,
                    cus.date_birth,
                    cus.birth_place,
                    cus.primary_id,
                    cus.primary_id_no,
                    cus.alternative_id,
                    cus.alternative_id_no,
                    mdl.model_name as model,
                    brd.brandname as brand,
                    repo.model_engine as engine,
                    repo.model_chassis as chassis,
                    rud.total_payments as total_payment,
                    repo.original_srp as srp,
                    rud.loan_amount,
                    rud.principal_balance,
                    sld.monthly_amo,
                    sld.rebate,
                    sld.terms,
                    sld.dp,
                    sld.rate,
                    loc.name as financing_store,
                    repo.loan_number,
                    repo.odo_meter,
                    repo.date_surrender,
                    repo.date_sold,
                    appraise.approved_price,
                    repo.id as repo_id
                FROM recieve_unit_details rud
                INNER JOIN repo_details repo ON rud.repo_id = repo.id
                LEFT JOIN customer_profile cus ON repo.customer_acumatica_id = cus.id
                LEFT JOIN brands brd ON repo.brand_id = brd.id
                LEFT JOIN unit_models mdl ON repo.model_id = mdl.id
                LEFT JOIN users usr ON rud.approver = usr.id
                LEFT JOIN branches brh ON repo.branch_id = brh.id
                LEFT JOIN locations loc ON repo.location = loc.id
                LEFT JOIN (
                    SELECT MAX(id) as latest_id, branch, repo_id, approved_price
                    FROM request_approvals
                    WHERE status = 1
                    GROUP BY branch, repo_id, approved_price
                ) appraise ON appraise.repo_id = repo.id AND appraise.branch = repo.branch_id
                LEFT JOIN sold_units sld ON repo.id = sld.repo_id
                WHERE repo.id = :recid',
				$param
			);

            $parts = [];

			$history =  DB::select(
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
                    appraise.remarks
                FROM repo_details repo
                LEFT JOIN request_approvals appraise ON repo.id = appraise.repo_id
                LEFT JOIN appraisal_histories history ON appraise.id = history.appraisal_req_id
                LEFT JOIN users usrs ON history.approver = usrs.id
                WHERE repo.id = :recid AND appraise.status = 1
                ORDER BY history.created_at",
                $param
            );
		} else {
			$query = DB::select(
				"SELECT
							'SUERTE MOTOPLAZA' AS company_name,
							UPPER(rep.msuisva_form_no) AS muisva_no, brh.name AS dealer_store, 'TRANS ASIATIC FINANCE INCORPORATED' AS originating_financing_store,
							UPPER(CONCAT(cus.firstname,' ',cus.middlename,' ',cus.lastname)) AS latest_borrower_name,
							UPPER(bb.original_owner) AS original_owner,
							UPPER(CONCAT(cus.address,' ',brgy.Title,', ',cty.Title,', ',prv.Title)) AS [address],
							UPPER(rep.loan_number) AS folder_no,
							bb.loan_amount AS loan_amount,
							FORMAT(rep.date_sold, 'MMM dd, yyyy') AS date_granted,
							bb.total_payments AS total_payment,
							'' AS date_due,
                            -- rep.original_srp,
	                        appraise.approved_price AS approved_appraised_price,
							CASE WHEN appraise.approved_price IS NOT NULL THEN 'true' ELSE 'false' END AS has_appraised,
                            rep.original_srp AS original_srp,
                            ISNULL(total_cost_parts, 0) AS total_cost_parts,
							-- (rep.original_srp - bb.total_payments) AS principal_balance,
							bb.principal_balance AS principal_balance,
							FORMAT(rep.last_payment, 'MMM dd, yyyy') AS late_date_of_payment,
							FORMAT(rep.date_surrender, 'MMM dd, yyyy') AS repo_date,
							brd.brandname AS brand,
							mdl.model_name AS model,
							UPPER(rep.model_engine) AS engine_no,
							UPPER(rep.model_chassis) AS chassis_no,
							rep.mv_file_number AS mv_file_number,
							rep.year_model AS year_model,
							UPPER(rep.plate_number) AS plate_no,
							'' AS br_or_arv_no,
							UPPER(rep.classification) AS [classification],
							UPPER(rep.unit_documents) AS classification_document_tag,
							UPPER(rep.unit_description) AS classification_description,
                            DATEDIFF(MONTH, (CONVERT(DATE, rep.date_sold)), rep.date_surrender) AS standard_matrix_month
						FROM repo_details rep
						LEFT JOIN branches brh ON rep.branch_id = brh.id
						inner join recieve_unit_details as bb on bb.repo_id = rep.id
						LEFT JOIN customer_profile cus ON rep.customer_acumatica_id = cus.id
						LEFT JOIN brands brd ON rep.brand_id = brd.id
						LEFT JOIN unit_models mdl ON rep.model_id = mdl.id
						LEFT JOIN unit_colors clr ON rep.color_id = clr.id
						LEFT JOIN province prv ON cus.provinces = prv.OrderNumber
						LEFT JOIN city cty ON cus.cities = cty.MappingId
						LEFT JOIN barangay brgy ON cus.barangays = brgy.OrderNumber
                        LEFT JOIN (
                            SELECT MAX(id) as latest_id, branch, repo_id, approved_price
                            FROM request_approvals
                            WHERE status = 1
                            GROUP BY branch, repo_id, approved_price
                        ) appraise ON rep.id = appraise.repo_id and rep.branch_id = appraise.branch
                        LEFT JOIN (
                            SELECT recieve_id, SUM(actual_price) AS total_cost_parts
                            FROM recieve_unit_spare_parts
                            WHERE is_deleted = 0 and refurb_decision = 'done'
                            GROUP BY recieve_id
                        ) parts ON bb.id = parts.recieve_id
						WHERE rep.id = :recid",
				$param
			);

			$parts = DB::select(
				"SELECT
						spares.[name] AS parts_name, parts.parts_status, CASE WHEN parts.actual_price != 0 OR parts.actual_price != null THEN parts.actual_price ELSE parts.price END AS parts_price
					FROM repo_details repo
					INNER JOIN recieve_unit_details received ON repo.id = received.repo_id
					INNER JOIN recieve_unit_spare_parts parts ON received.id = parts.recieve_id
					LEFT JOIN spare_parts spares ON parts.parts_id = spares.id
					WHERE parts.is_deleted = 0 AND (parts.refurb_decision = 'na' OR parts.refurb_decision IS NULL)
					AND repo.id = :recid",
				$param
			);

            $history = [];
		}
		// dd($query);

		$pdf = PDF::loadView(
			($formType == 'RDAF' ? 'rdaf' : 'muisva'),
			array(
				'Title' =>  $formType,
				'data' => array(
					'datas' => json_encode($query),
					'parts' => json_encode($parts),
					'history' => json_encode($history),
					'report_src' => $src
				)
			)
		)
			->setPaper('legal', ($formType == 'RDAF' ? 'landscape' : 'portrait'));

		return $pdf->stream();
	}
}
