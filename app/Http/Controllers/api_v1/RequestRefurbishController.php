<?php

namespace App\Http\Controllers\api_v1;

use App\Http\Controllers\api_v1\BaseController as BaseController;
use App\Models\request_refurbish;
use App\Models\refurbish_detail;
use Illuminate\Http\Request;
use App\Http\Traits\helper;
use Carbon\Carbon;
use App\Models\approval_matrix_setting;
use Auth;
use DB;
use File;
use Validator;

class RequestRefurbishController extends BaseController
{
    //
    use helper;
    public function listOfForRefurbish(){

        $list_id = array();

        $get_all_repo =  request_refurbish::select('repo_id')->get();

        foreach($get_all_repo as $repo){
            array_push($list_id,$repo->repo_id);
        }
       
        $received_units = DB::table('recieve_unit_details AS rud')
            ->join('repo_details as repo','repo.id','rud.repo_id')
            ->join('branches as br', 'repo.branch_id', 'br.id')
			->join('brands as brd', 'repo.brand_id', 'brd.id')
			->join('unit_models as mdl','repo.model_id' , 'mdl.id')
            ->join('unit_colors as color','repo.color_id' , 'color.id')
			->select('rud.id as receive_id','repo.id as repo_id','repo.model_engine','repo.model_chassis','repo.date_sold',
            'br.name as branchname','brd.brandname', 'mdl.model_name','color.name as color')
            ->where('rud.status','0')
            ->whereIn('repo.classification',['D','E']);

            if(Auth::user()->userrole != 'Warehouse Custodian'){
                $received_units = $received_units->whereNotIn('repo.id',$list_id);
            }else{
                $received_units = $received_units
                                    ->whereNotIn('repo.id',$list_id)
                                    ->where('repo.branch_id',Auth::user()->branch);
            }
            
            $received_units = $received_units->get();

            $data = ['data' => $received_units, 'role' =>  'Maker'];
            return $data;

    }

    public function getMissingDamageParts($received_id){
        $get_spare_missing = DB::table('recieve_unit_spare_parts as a')
                                ->join('spare_parts as b','b.id','a.parts_id')
                                ->select('b.*','a.price')
                                ->where('recieve_id',$received_id)->get();

        return $get_spare_missing;
    }

    public function getRefurbishParts($refurbishid){
        $get_spare_missing = DB::table('refurbish_details as a')
                                ->join('spare_parts as b','b.id','a.spare_parts')
                                ->select('b.*','a.price')
                                ->where('a.refurbish_id',$refurbishid)->get();

        return $get_spare_missing;
    }

    public function requestRefurbish(Request $request){
        
        $file_list = array();
        $validator = Validator::make($request->all(), [
			'repo_id' => 'required|numeric',
			'spares' => 'required',
		]);
	
		if ($validator->fails()) {
			return $this->sendError('Validation Error.', $validator->errors()); 
		}

        if($request->q1 == 'null' && $request->q2 == 'null' && $request->q3 == 'null'){
            return $this->sendError('Validation Error.', 'Please upload atleast 1 Qoutation!');   
        }

    try
        {

        

        DB::beginTransaction();

        $folder_path = 'image/Qoutation';
		$directory = public_path($folder_path);
		if(!File::isDirectory($directory)){
			File::makeDirectory($directory, 0777, true, true);
		}

        if($request->q1 != 'null'){
            $image1 = $request->file("q1");
            if($image1){
                $image_name1 = strtoupper(uniqid().'-'.$image1->getClientOriginalName());
				$image1->move($directory, $image_name1);
              
                array_push($file_list,[
                    'filename' => $image_name1,
                    'path' => $folder_path.'/'.$image_name1
                ]);
            }
           
        }

        if($request->q2 != 'null'){
            $image1 = $request->file("q2");
            if($image1){
                $image_name1 = strtoupper(uniqid().'-'.$image1->getClientOriginalName());
				$image1->move($directory, $image_name1);
              
                array_push($file_list,[
                    'filename' => $image_name1,
                    'path' => $folder_path.'/'.$image_name1
                ]);
            }
           
        }

        if($request->q3 != 'null'){
            $image1 = $request->file("q3");
            if($image1){
                $image_name1 = strtoupper(uniqid().'-'.$image1->getClientOriginalName());
				$image1->move($directory, $image_name1);
              
                array_push($file_list,[
                    'filename' => $image_name1,
                    'path' => $folder_path.'/'.$image_name1
                ]);
            }
           
        }

        $refurbish = new request_refurbish;
        $refurbish->repo_id = $request->repo_id;
        $refurbish->branch = Auth::user()->branch;
        $refurbish->maker = Auth::user()->id;
        $refurbish->files_names = json_encode($file_list);
        $refurbish->save();

        $spares = json_decode($request->spares, true);
        foreach($spares as $parts){
            $details = new refurbish_detail;
            $details->refurbish_id = $refurbish->id;
            $details->spare_parts = $parts['parts_id'];
            $details->price = $parts['price'];
            $details->save();
        }

        $matrix =  $this->ApprovalMatrixActivityLog($request->module_id,$refurbish->id);

        if($matrix['status'] == 'error'){
            return $matrix;
        }else{
            //update the first holder of the transaction
            $save_holder = request_refurbish::where('id',$refurbish->id)->update(['approver' => $matrix['message']]);
        }

        DB::commit();

        return $this->sendResponse([], 'Request successfully save.');
    }catch (Exception $e) {
        // Code to handle the exception
        return "Error: " . $e->getMessage();
    }

    }

    public function getListForApprovalRefurbish($moduleid){

        $refurbish_request = DB::table('repo_details as repo')
            ->join('request_refurbishes as refurbish','refurbish.repo_id','repo.id')
            ->join('branches as br', 'repo.branch_id', 'br.id')
			->join('brands as brd', 'repo.brand_id', 'brd.id')
			->join('unit_models as mdl','repo.model_id' , 'mdl.id')
            ->join('unit_colors as color','repo.color_id' , 'color.id')
            ->join('users as holder', 'refurbish.approver', 'holder.id')
            ->join('users as req', 'refurbish.maker', 'req.id')
			->select('refurbish.id as refurbish_id','refurbish.files_names as qoute','repo.id as repo_id','repo.model_engine','repo.model_chassis','repo.date_sold',
            'br.name as branchname','brd.brandname', 'mdl.model_name','color.name as color',
            DB::raw("CASE WHEN refurbish.status = '0' THEN 'WAITING FOR APPROVAL'
            WHEN refurbish.status = '1' THEN 'APPROVED' WHEN refurbish.status = '2' THEN 'DISAPPROVED' END status
       "),'refurbish.remarks',DB::raw('CONCAT(holder.firstname,holder.middlename,holder.lastname) as current_holder')
       ,DB::raw('CONCAT(req.firstname,req.middlename,req.lastname) as requestor'));
            // ->whereIn('refurbish.status',['0','2'])
            // ->where('refurbish.approver',Auth::user()->id)
            // ->get();

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
                $refurbish_request = $refurbish_request->where('refurbish.status','0')->where('refurbish.approver',Auth::user()->id)->get();
            }else{
                $role = 'Maker';
    
                $check = request_refurbish::where('maker', Auth::user()->id)->count();
    
                if($check > 0){
                    $refurbish_request = $refurbish_request->where('refurbish.maker', Auth::user()->id);
                }
                
                $refurbish_request = $refurbish_request->whereIn('refurbish.status',['0','2'])->get();
            }
            $data = ['data' => $refurbish_request, 'role' =>  $role];
            return $data;
    }

    public function refurbishDecision(Request $request){
        $validator = Validator::make($request->all(), [
            'data_id' => 'required',
            'remarks' => 'required',
            'status' => 'required',
        ]);

        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }

        // get the transaction id
        $data = request_refurbish::where('id',$request->data_id)->first();

        //first insert to decision matrix log
        $sequence = $this->approverDecision($request->module_id,$data->id,Auth::user()->id);

        if($sequence == 0){
            $update = request_refurbish::where('id',$request->data_id)->update(['status' => $request->status]);
        }else{
            //if not the final approver then check if status is disapproved else ignore update
            if($request->status == 2){
                $update = request_refurbish::where('id',$request->data_id)->update(['status' => $request->status]);
            }
        }

        $arr = [
                    'approver' => $sequence == 0 ? Auth::user()->id : ($request->status == 2 ? Auth::user()->id : $sequence),
                    'date_approved' => $request->status == 1 ? Carbon::now() : null,
                    'remarks' => $request->remarks
                ];

        //remove 1st set of spare parts
        $remove = refurbish_detail::where('refurbish_id',$request->data_id)->delete();

        $spares = json_decode($request->spares, true);
            foreach($spares as $parts){
                $details = new refurbish_detail;
                $details->refurbish_id = $request->data_id;
                $details->spare_parts = $parts['parts_id'];
                $details->price = $parts['price'];
                $details->save();
            }
   
        $updateRequest = request_refurbish::where('id',$request->data_id)
                                ->update($arr);
        $msg = $request->status == 1 ? 'Request for refurbish approval successfully approved!' : 'Request for refurbish approval successfully disapproved!';
        return $this->sendResponse([], $msg);
    }
}
