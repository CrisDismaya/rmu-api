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
				'SELECT h.name as branch,CONCAT(nw.firstname,nw.middlename,nw.lastname) as customer,nw.address,
				nw.nationality,nw.source_of_income,nw.marital_status,nw.date_birth,nw.birth_place,nw.primary_id,nw.primary_id_no,
				nw.alternative_id,nw.alternative_id_no,
				d.model_name as model,f.brandname as brand,b.model_engine as engine,b.model_chassis as chassis,
				bb.total_payments as total_payment,b.original_srp as srp,bb.loan_amount,bb.principal_balance,
				c.approved_price,g.monthly_amo,g.rebate,g.terms,g.dp,c.date_approved,g.rate,gg.name as financing_store,
				b.loan_number,b.odo_meter,b.date_surrender,b.date_sold
				from  repo_details b
				inner join recieve_unit_details as bb on bb.repo_id = b.id
				left join (select a.* from request_approvals as a
				inner join (select max(id) id,repo_id from request_approvals group by repo_id) as b on b.id = a.id
				) as c on c.repo_id = b.id
				inner join unit_models d on d.id = b.model_id
				inner join unit_colors as e on e.id = b.color_id
				inner join brands as f on f.id = b.brand_id
				left join sold_units as g on g.repo_id = b.id
				left join customer_profile as nw on nw.id = g.new_customer
				inner join locations as gg on gg.id = b.location
				inner join branches as h on h.id = b.branch_id where b.id = :recid',
				$param
			);

			$parts = [];
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
                            rep.original_srp,
							-- (rep.original_srp - bb.total_payments) AS principal_balance,
							bb.principal_balance AS principal_balance,
							FORMAT(rep.last_payment, 'MMM dd, yyyy') AS late_date_of_payment,
							FORMAT(rep.date_surrender, 'MMM dd, yyyy') AS repo_date,
							brd.brandname AS brand,
							mdl.model_name AS model,
							UPPER(rep.model_engine) AS engine_no,
							UPPER(rep.model_chassis) AS chassis_no,
							'' AS or_no,
							'' AS cre_no,
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
		}
		// dd($query);

		$pdf = PDF::loadView(
			($formType == 'RDAF' ? 'rdaf' : 'muisva'),
			array(
				'Title' =>  $formType,
				'data' => array(
					'datas' => json_encode($query),
					'parts' => json_encode($parts),
					'report_src' => $src
				)
			)
		)
			->setPaper('legal', ($formType == 'RDAF' ? 'landscape' : 'portrait'));

		return $pdf->stream();
	}
}
