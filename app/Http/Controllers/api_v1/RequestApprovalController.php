<?php

namespace App\Http\Controllers\api_v1;

use Illuminate\Http\Request;
use App\Http\Controllers\api_v1\BaseController as BaseController;
use Illuminate\Support\Facades\Validator;
use App\Models\RequestApproval;
use App\Models\unit_aging;
use App\Models\receive_unit;
use App\Models\sold_unit;
use App\Http\Traits\helper;
use Carbon\Carbon;
use App\Models\approval_matrix_setting;
use App\Models\request_refurbish;
use App\Models\refurbish_detail;
use App\Models\appraisal_history;
use DB;
use Auth;
use File;

class RequestApprovalController extends BaseController
{
    //

    use helper; //helper traits


    public function listReceivedUnit(){

        $list_id = array();

        $get_all_repo =  RequestApproval::select('repo_id')->get();

        foreach($get_all_repo as $repo){
            array_push($list_id,$repo->repo_id);
        }
       
        $received_units = DB::table('recieve_unit_details AS rud')
            ->join('repo_details as repo','repo.id','rud.repo_id')
            ->join('branches as br', 'rud.branch', 'br.id')
			->join('brands as brd', 'repo.brand_id', 'brd.id')
			->join('unit_models as mdl','repo.model_id' , 'mdl.id')
            ->join('unit_colors as color','repo.color_id' , 'color.id')
			->select('rud.*','repo.model_engine','repo.model_chassis','repo.date_sold',
            'br.name as branchname','brd.brandname', 'mdl.model_name','color.name as color')
            ->whereIn('rud.status',['0','2'])
            ->whereNotIn('rud.id',$list_id)->get();

            $data = ['data' => $received_units, 'role' =>  'Maker'];
            return $data;
    }

    public function getAllReceivedUnit($moduleid){
        $received_units = DB::table('recieve_unit_details AS rud')
            ->join('repo_details as repo','repo.id','rud.repo_id')
            ->join('branches as br', 'rud.branch', 'br.id')
			->join('brands as brd', 'repo.brand_id', 'brd.id')
			->join('unit_models as mdl','repo.model_id' , 'mdl.id')
            ->join('unit_colors as color','repo.color_id' , 'color.id')
            ->join('request_approvals as req_app', 'rud.id', 'req_app.received_unit_id')
            ->join('users as holder', 'req_app.approver', 'holder.id')
            ->join('users as maker', 'req_app.created_by', 'maker.id')
			->select('rud.*','repo.model_engine','repo.model_chassis','repo.date_sold','br.name as branchname','brd.brandname', 'mdl.model_name','req_app.suggested_price'
            ,'req_app.approved_price',DB::raw("CASE WHEN rud.status = '0' THEN 'PENDING'
                 WHEN rud.status = '1' THEN 'APPROVED' WHEN rud.status = '2' THEN 'DISAPPROVED' END status
            "),'req_app.remarks',DB::raw('CONCAT(holder.firstname,holder.middlename,holder.lastname) as current_holder')
            ,DB::raw('CONCAT(maker.firstname,maker.middlename,maker.lastname) as requestor'),'color.name as color'
			);
		
        $count = 0;
        $get_approvers = approval_matrix_setting::where('module_id',$moduleid)->get();
        foreach($get_approvers as $approvers){
               
            foreach($approvers->signatories as $approver){
              
               if(Auth::user()->id == $approver['user']){
                $count++;
               }
            }  
        }

        $role = '';
      
        if($count > 0){
            $role = 'Approver';
            $received_units = $received_units->where('rud.status','0')->where('req_app.approver',Auth::user()->id)->get();
        }else{
            $role = 'Maker';

            $check = RequestApproval::where('created_by', Auth::user()->id)->count();

            if($check > 0){
                $received_units = $received_units->where('req_app.created_by', Auth::user()->id);
            }
            
            $received_units = $received_units->whereIn('rud.status',['0','2'])->get();
        }
        $data = ['data' => $received_units, 'role' =>  $role];
        return $data;

    }

    public function SoldUnitMasterList(){
        $received_units = DB::table('repo_details as repo')
            ->join('branches as br', 'repo.branch_id', 'br.id')
            ->join('brands as brd', 'repo.brand_id', 'brd.id')
            ->join('unit_models as mdl','repo.model_id' , 'mdl.id')
            ->join('unit_colors as color','repo.color_id' , 'color.id')
            ->join('customer_profile as old_owner','repo.customer_acumatica_id' , 'old_owner.acumatica_id')
            ->join('sold_units as sold_unit', 'repo.id', 'sold_unit.repo_id')
            ->join('customer_profile as new_owner','sold_unit.new_customer' , 'new_owner.id')
			->select('sold_unit.id','repo.id as repo_id','repo.model_engine','repo.model_chassis','br.name as branchname'
            ,'brd.brandname', 'mdl.model_name','sold_unit.new_customer'
            ,'color.name as color','old_owner.firstname as o_firstname',
            'old_owner.middlename as o_middlename','old_owner.lastname as o_lastname','new_owner.firstname',
            'new_owner.middlename','new_owner.lastname','sold_unit.invoice_reference_no'
            ,DB::raw("CASE WHEN sold_unit.sale_type = 'C' THEN 'CASH'
            WHEN sold_unit.sale_type = 'I' THEN 'INSTALLMENT' END sale_type"),'sold_unit.srp as approved_price',
            'sold_unit.dp','sold_unit.monthly_amo'
            ,'sold_unit.rebate','sold_unit.terms','sold_unit.sold_date','sold_unit.maker'
            ,'sold_unit.approver','sold_unit.status'
			)->where('sold_unit.status','1');  

            if(Auth::user()->userrole == 'Warehouse Custodian'){
                $received_units = $received_units->where('repo.branch_id',Auth::user()->branch)->get();
              
             }else{
                 $received_units = $received_units->get();
             }

    
		return $received_units;
    }

    public function getListForApproval($moduleid){
        $received_units = DB::table('repo_details as repo')
            ->join('branches as br', 'repo.branch_id', 'br.id')
            ->join('brands as brd', 'repo.brand_id', 'brd.id')
            ->join('unit_models as mdl','repo.model_id' , 'mdl.id')
            ->join('unit_colors as color','repo.color_id' , 'color.id')
            ->join('customer_profile as old_owner','repo.customer_acumatica_id' , 'old_owner.acumatica_id')
            ->join('sold_units as sold_unit', 'repo.id', 'sold_unit.repo_id')
            ->join('customer_profile as new_owner','sold_unit.new_customer' , 'new_owner.id')
			->select('sold_unit.id','repo.id as repo_id','repo.model_engine','repo.model_chassis','br.name as branchname'
            ,'brd.brandname', 'mdl.model_name','sold_unit.new_customer'
            ,'color.name as color','old_owner.firstname as o_firstname',
            'old_owner.middlename as o_middlename','old_owner.lastname as o_lastname','new_owner.firstname',
            'new_owner.middlename','new_owner.lastname','sold_unit.invoice_reference_no'
            ,DB::raw("CASE WHEN sold_unit.sale_type = 'C' THEN 'CASH'
            WHEN sold_unit.sale_type = 'I' THEN 'INSTALLMENT' END sale_type"),'sold_unit.srp as approved_price',
            'sold_unit.dp','sold_unit.monthly_amo'
            ,'sold_unit.rebate','sold_unit.terms','sold_unit.sold_date','sold_unit.maker'
            ,'sold_unit.approver','sold_unit.status','sold_unit.remarks','sold_unit.rate',
            'sold_unit.amount_finance','sold_unit.interest_rate','sold_unit.file_name','sold_unit.path'
			);  

            $count = 0;
            $get_approvers = approval_matrix_setting::where('module_id',$moduleid)->get();
            foreach($get_approvers as $approvers){
                   
                foreach($approvers->signatories as $approver){
                  
                   if(Auth::user()->id == $approver['user']){
                    $count++;
                   }
                }  
            }
    
            $role = '';
          
            if($count > 0){
                $role = 'Approver';
                $received_units = $received_units->where('sold_unit.status','0')->where('sold_unit.approver',Auth::user()->id)->get();
            }else{
                $role = 'Maker';
    
                $received_units = $received_units->whereIn('sold_unit.status',['0','2'])->where('sold_unit.maker',Auth::user()->id)->get();
            }
            $data = ['data' => $received_units, 'role' =>  $role];
            return $data;

    
		    return $received_units;
    }

    public function UnitInventoryMasterList(){

            $condition = '';

            if(Auth::user()->userrole == 'Warehouse Custodian'){
            $condition = " WHERE repo.branch_id ='".Auth::user()->branch."'";
            
            }


        $received_units = DB::select("SELECT distinct
                repo.id as repo_id,repo.msuisva_form_no as msuisva,repo.model_engine, repo.model_chassis,repo.branch_id
                ,branches.name as branchname,brands.brandname,model.model_name,color.name as color,
                CONCAT(old_owner.firstname,old_owner.middlename,old_owner.lastname) as ex_owner,
                old_owner.firstname as o_firstname, old_owner.middlename as o_middlename,old_owner.lastname as o_lastname,repo.created_at as date_received
                ,repo.original_srp,req.approved_price as current_appraised,DATEDIFF(DAY,(CONVERT(DATE,repo.date_surrender)),GETDATE()) as aging,
                qty.quantity,case when sold_count.quantity is null and transfer_count.quantity is null then 1 else 0 end availability,
                case when sold_count.quantity is null and transfer_count.quantity is not null then 'FOR TRANSFER'
                    WHEN sold_count.quantity is NOT null and transfer_count.quantity is null then'SOLD' ELSE 'AVAILABLE' end  status,
                    received.is_sold
            from repo_details as repo
            inner join (select max(id) id,model_engine,model_chassis from repo_details group by model_chassis,model_engine) as latest on latest.id = repo.id
            inner join recieve_unit_details as received on received.repo_id = repo.id
            left join request_approvals as req on req.repo_id = repo.id
            inner join branches on branches.id = repo.branch_id
            inner join  brands on brands.id = repo.brand_id
            inner join unit_models as model on model.id = repo.model_id
            inner join unit_colors as color on color.id = repo.color_id
            inner join customer_profile as old_owner on old_owner.acumatica_id = repo.customer_acumatica_id
            left join (select id,count(id) quantity from repo_details group by id) as qty on qty.id = repo.id
            left join (select repo_id,count(repo_id) quantity from sold_units where status in (0,1) 
                        group by repo_id) as sold_count  on sold_count.repo_id = repo.id
            left join (
                        select b.recieved_unit_id,count(b.recieved_unit_id) quantity from stock_transfer_approval a
                        inner join stock_transfer_unit b on b.stock_transfer_id = a.id
                        where a.status in (0,1) group by recieved_unit_id) as transfer_count
            on transfer_count.recieved_unit_id = received.id". $condition
        );
      
		return $received_units;
    }

    public function UnitHistory($engine,$chassis){
        $unit_history =  DB::table('repo_details as repo')
            ->join('branches as br', 'repo.branch_id', 'br.id')
            ->join('brands as brd', 'repo.brand_id', 'brd.id')
            ->join('unit_models as mdl','repo.model_id' , 'mdl.id')
            ->join('unit_colors as color','repo.color_id' , 'color.id')
            ->join('customer_profile as old_owner','repo.customer_acumatica_id' , 'old_owner.acumatica_id')  
            ->select('repo.model_engine','repo.model_chassis','repo.branch_id','br.name as branchname'
                     ,'brd.brandname', 'mdl.model_name','color.name as color',
                     DB::raw('CONCAT(old_owner.firstname,old_owner.middlename,old_owner.lastname) as ex_owner'),
                     'repo.date_sold','repo.date_surrender')
           ->where('repo.model_engine',$engine)
           ->where('repo.model_chassis',$chassis)
           ->get();

           return $unit_history;
    }

    public function calculateSuggestedPrice($id,$firstdatesold){

        $received_units = receive_unit::with(['spare_parts_details'])
			->where('id', $id)->first();

        $tmdp = 0;

        for($i = 0; $i < count($received_units->spare_parts_details); $i++){
            $data = $received_units->spare_parts_details[$i];
            $tmdp += $data->price;
        }

        $refurbish = request_refurbish::with(['missingParts'])
			->where('repo_id', $id)->first();
        $refurb = 0;

        if($refurbish){
            for($i = 0; $i < count($refurbish->missingParts); $i++){
                $data = $refurbish->missingParts[$i];
                $refurb += $data->price;
            } 
        }

        

        
    
  
        $start = Carbon::parse($firstdatesold);
        $end = Carbon::parse(Carbon::now());

        $unit_age = $end->diffInDays($start);

        $has_matrix_setup = unit_aging::count();

        if($has_matrix_setup == 0){
            return  $this->sendError('Validation Error.', 'No depriciation matrix. Please contact your system administrator!');
        }

        switch ($unit_age){
            case ($unit_age <= 180):
                $unit_criteria = unit_aging::where('days','>=',$unit_age)->where('days','<=',180)->first();
            break;
            case ($unit_age <= 360):
                $unit_criteria = unit_aging::where('days','>=',$unit_age)->where('days','<=',360)->first();
            break;
            case ($unit_age <= 720):
                $unit_criteria = unit_aging::where('days','>=',$unit_age)->where('days','<=',720)->first();
            break;
            case ($unit_age >= 721):
                $unit_criteria = unit_aging::latest('days')->first();
            break;
        }

        //start of totaling the suggested repo price

        $depreciation = ($received_units->unit_price) * ('0.'. ($unit_criteria->Depreceiation_Cost < 10 ? '0'.$unit_criteria->Depreceiation_Cost : $unit_criteria->Depreceiation_Cost));
        $md_max_limiter = ($received_units->unit_price) * ('0.'. ($unit_criteria->Estimated_Cost_of_MD_Parts < 10 ? '0'.$unit_criteria->Estimated_Cost_of_MD_Parts : $unit_criteria->Estimated_Cost_of_MD_Parts));
        $total_md = $tmdp < $md_max_limiter ? $tmdp : $md_max_limiter;
        $immidiate_sales_value =  ($received_units->unit_price) * ('0.'. ($unit_criteria->Immediate_Sales_Value < 10 ? '0'.$unit_criteria->Immediate_Sales_Value : $unit_criteria->Immediate_Sales_Value));

        $suggested_price = ($received_units->unit_price) - ($depreciation + ($refurb > 0 ? 0 : $total_md));

        //end of computation
        
        return [
                'days' => $unit_age,
                'depreciation' => $depreciation,
                'emdp' => $refurb > 0 ? 0 : $md_max_limiter,
                't_mdp' => $refurb > 0 ? 0 : $tmdp,
                'sp' => $refurb > 0 ? ($suggested_price + $refurb) : $suggested_price
            ];
        
    }

    public function requestRepoPriceApproval(Request $request){

        $rec_id = null;

        $validator = Validator::make($request->all(), [
            'approved_price' => 'required',
        ]);
   
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }

        $input =  $request->all();
       // $input['status'] = '0';
        $input['created_by'] = Auth::user()->id;
        
        $check = RequestApproval::where('received_unit_id',$request->received_unit_id)->count();

        DB::beginTransaction();

        if($check > 0){
            $getrecord = RequestApproval::select('id')->where('received_unit_id',$request->received_unit_id)->first();

            $update = RequestApproval::where('received_unit_id',$request->received_unit_id)
                            ->update(['approved_price' => $request->approved_price]);
            $updatestatus = receive_unit::where('id',$request->received_unit_id)->update(['status' => '0']);
            $rec_id = $getrecord->id;
        }else{
            $create = RequestApproval::create($input);
            $rec_id = $create->id;
        }

        $matrix =  $this->ApprovalMatrixActivityLog($request->module_id,$rec_id);

        if($matrix['status'] == 'error'){
            return $matrix;
        }else{
            //update the first holder of the transaction
            $save_holder = RequestApproval::where('id',$rec_id)->update(['approver' => $matrix['message']]);
        }

        DB::commit();
        
        return $this->sendResponse([], 'Request for approval successfully saved!');
    }

    public function submitRequestDecision(Request $request){

        $validator = Validator::make($request->all(), [
            'data_id' => 'required',
            'remarks' => 'required',
            'status' => 'required',
        ]);

        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }

        // get the transaction id
        $data = RequestApproval::where('received_unit_id',$request->data_id)->first();

        //first insert to decision matrix log
        $sequence = $this->approverDecision($request->module_id,$data->id,Auth::user()->id);

        if($sequence == 0){
            $update = receive_unit::where('id',$request->data_id)->update(['status' => $request->status]);
        }else{
            //if not the final approver then check if status is disapproved else ignore update
            if($request->status == 2){
                $update = receive_unit::where('id',$request->data_id)->update(['status' => $request->status]);
            }
        }

        if($request->status == 2){
            $appraisal_log = new appraisal_history;
            $appraisal_log->appraisal_req_id = $data->id;
            $appraisal_log->date_disapproved = Carbon::now();
            $appraisal_log->remarks = $request->remarks;
            $appraisal_log->approver = Auth::user()->id;
            $appraisal_log->save();
        }

        $arr = [
                    'approver' => $sequence == 0 ? Auth::user()->id : ($request->status == 2 ? Auth::user()->id : $sequence),
                    'date_approved' => $request->status == 1 ? Carbon::now() : null,
                    'remarks' => $request->remarks
                ];
        if($request->edit_price){
            $arr['approved_price'] = $request->approved_price;
            $arr['edited_price'] = $data->approved_price;
        }

        
        $updateRequest = RequestApproval::where('received_unit_id',$request->data_id)
                                ->update($arr);
        $msg = $request->status == 1 ? 'Request for approval successfully approved!' : 'Request for approval successfully disapproved!';
        return $this->sendResponse([], $msg);
    }

    public function tagUnitSale(Request $request){

        $rec_id = null;

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

        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }

        $input =  $request->all();
        // $input['status'] = '0';
     

         $check = sold_unit::where('repo_id',$request->repo_id)->count();

         DB::beginTransaction();
       
         
         if($check > 0){

            return $this->sendError('Validation Error.', 'Repo is already subject for sales tagging.! Please wait for approval');   
          
         }else{
            //create
             $create = new sold_unit;
             $create->repo_id = $input['repo_id'];
             $create->new_customer = $input['new_owner'];
             $create->invoice_reference_no = $input['invoice'];
             $create->sale_type = $input['sold_type'];
             $create->srp = $input['srp'];
             $create->dp = $input['dp'];
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

                //check for RNR uploading
                if($request->rate != '0.03'){
                    $folder_path = 'image/rnr';
                    $directory = public_path($folder_path);
                    if(!File::isDirectory($directory)){
                        File::makeDirectory($directory, 0777, true, true);
                    }
        
                    if($request->rnr != 'null'){
                        $image1 = $request->file("rnr");
                        if($image1){
                            $image_name1 = strtoupper(uniqid().'-'.$image1->getClientOriginalName());
                            $image1->move($directory, $image_name1);
                            // array_push($file_list,[
                            //     'filename' => $image_name1,
                            //     'path' => $folder_path.'/'.$image_name1
                            // ])

                            $create->file_name = $image_name1;
                            $create->path = $folder_path.'/'.$image_name1;
                        }
                    
                    }
            }

             $create->save();
             $rec_id = $create->id;
         }
 
         $matrix =  $this->ApprovalMatrixActivityLog($request->module_id,$rec_id);
 
         if($matrix['status'] == 'error'){
             return $matrix;
         }else{
             //update the first holder of the transaction
             $save_holder = sold_unit::where('id',$rec_id)->update(['approver' => $matrix['message']]);
         }
 
         DB::commit();
         
         return $this->sendResponse([], 'Tagging success. Please wait for the approval!');

      //  $update = receive_unit::where('id',$request->received_id)->update(['sold_type' => $request->sold_type, 'is_sold' => 'Y']);
        
    }

    public function submitTagUnitDecision(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'status' => 'required',
            'remarks' => 'required',
        ]);

        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }


        //first insert to decision matrix log
        $sequence = $this->approverDecision($request->module_id,$request->id,Auth::user()->id);

        if($sequence == 0){
            if($request->status == 1){
                $update = receive_unit::where('repo_id',$request->repo_id)->update(['sold_type' => $request->sold_type, 'is_sold' => 'Y']);
                
            }
            $tag = sold_unit::where('id',$request->id)->update(['sale_type' => $request->sold_type,'status' => $request->status]); 
            
        }else{
            //if not the final approver then check if status is disapproved else ignore update
            if($request->status == 2){
                $tag = sold_unit::where('id',$request->id)->update(['status' => $request->status]);
            }
        }

        
        $updateRequest = sold_unit::where('id',$request->id)
                                ->update([
                                            'approver' => $sequence == 0 ? Auth::user()->id : ($request->status == 2 ? Auth::user()->id : $sequence),
                                            'remarks' => $request->remarks
                                        ]);
        $msg = $request->status == 1 ? 'Request for approval successfully approved!' : 'Request for approval successfully disapproved!';
        return $this->sendResponse([], $msg);
    }

    public function updateSaleTagging(Request $request){

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
        ]);

        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }

        $arr = [
            'sale_type' => $request->sold_type,
            'dp' => $request->dp,
            'invoice_reference_no' => $request->invoice,
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

        if($request->rate != '0.03'){
                $folder_path = 'image/rnr';
                $directory = public_path($folder_path);
                if(!File::isDirectory($directory)){
                    File::makeDirectory($directory, 0777, true, true);
                }

                if($request->rnr != 'null'){
                    $image1 = $request->file("rnr");
                    if($image1){
                        $image_name1 = strtoupper(uniqid().'-'.$image1->getClientOriginalName());
                        $image1->move($directory, $image_name1);
                        // array_push($file_list,[
                        //     'filename' => $image_name1,
                        //     'path' => $folder_path.'/'.$image_name1
                        // ])

                        $arr['file_name'] = $image_name1;
                        $arr['path'] = $folder_path.'/'.$image_name1;
                    }
                
                }
        }

        $updateRequest = sold_unit::where('id',$request->id)
                                ->update($arr);
       
        return $this->sendResponse([], 'Request Successfully updated!');

    }

    public function appraisalActivityLog($requestid){
        $data = DB::table('appraisal_histories as b')
                  ->join('users as c', 'c.id','b.approver')
                  ->select('b.remarks','b.date_disapproved',DB::raw('CONCAT(c.firstname,c.middlename,c.lastname) as approver'))
                  ->get();

        return $data;
    }
}
