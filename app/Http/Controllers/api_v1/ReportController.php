<?php

namespace App\Http\Controllers\api_v1;

use App\Http\Controllers\api_v1\BaseController as BaseController;
use Illuminate\Http\Request;
use PDF;
use DB;

class ReportController extends BaseController
{
	//

	public function generateReport($formType,$recordId){

		if($formType == 'RDAF'){
			$query = DB::select('SELECT h.name as branch,CONCAT(a.firstname,a.middlename,a.lastname) as customer,a.address,
				a.nationality,a.source_of_income,a.marital_status,a.date_birth,a.birth_place,a.primary_id,a.primary_id_no,
				a.alternative_id,a.alternative_id_no,
				d.model_name as model,f.brandname as brand,b.model_engine as engine,b.model_chassis as chassis,
				b.amount_paid as total_payment,b.original_srp as srp,bb.loan_amount,bb.principal_balance,
				c.approved_price,g.monthly_amo,g.rebate,g.terms,g.dp,c.date_approved,g.rate
				from customer_profile a
				inner join repo_details b on b.customer_acumatica_id = a.acumatica_id
				inner join recieve_unit_details as bb on bb.repo_id = b.id
				inner join request_approvals c on c.repo_id = b.id
				inner join unit_models d on d.id = b.model_id
				inner join unit_colors as e on e.id = b.color_id
				inner join brands as f on f.id = b.brand_id
				left join sold_units as g on g.repo_id = b.id
				inner join branches as h on h.id = b.branch_id where b.id ='.$recordId
			);
		}
		else {
			$query =DB::select("SELECT 
							'' AS company_name,
							UPPER(rep.msuisva_form_no) AS muisva_no, brh.name AS dealer_store, '' AS originating_financing_store, '' AS latest_borrower_name,
							CONCAT(cus.firstname,' ',cus.middlename,' ',cus.lastname) AS original_owner, 
							CONCAT(cus.address,' ',cus.barangays,' ',cus.cities,' ',cus.provinces) AS [address],
							'' AS folder_no,
							rep.original_srp AS loan_amount,
							'' AS date_granted,
							rep.amount_paid AS total_payment,
							'' AS date_due,
							(rep.original_srp - rep.amount_paid) AS principal_balance,
							'' AS late_date_of_payment,
							FORMAT(rep.created_at, 'MMM dd, yyyy') AS repo_date,
							brd.brandname AS brand,
							mdl.model_name AS model,
							UPPER(rep.model_engine) AS engine_no,
							UPPER(rep.model_chassis) AS chassis_no,
							rep.latest_or_number AS or_no,
							'' AS cre_no,
							UPPER(rep.plate_number) AS plate_no,
							'' AS br_or_arv_no,
							UPPER(rep.classification) AS [classification],
							'' AS classification_document_tag,
							'' AS classification_description
						FROM repo_details rep 
						LEFT JOIN branches brh ON rep.branch_id = brh.id
						LEFT JOIN customer_profile cus ON rep.customer_acumatica_id = cus.id
						LEFT JOIN brands brd ON rep.brand_id = brd.id
						LEFT JOIN unit_models mdl ON rep.model_id = mdl.id
						LEFT JOIN unit_colors clr ON rep.color_id = clr.id
						WHERE rep.id = ".$recordId
					);
		}
	   // dd($query);
	   
		$pdf = PDF::loadView($formType == 'RDAF' ? 'rdaf' : 'muisva', array('Title' =>  $formType, 'data'=> $query))
			->setPaper('legal', 'portrait');

		return $pdf->stream();
	}
}
