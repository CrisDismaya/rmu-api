<?php
namespace App\Http\Traits;
use App\Models\approval_activity_log;
use App\Models\approval_matrix_setting;
use DB;
use Auth;


trait helper {

	public function uuidGenerator(){
		if (function_exists('com_create_guid')){
            return com_create_guid();
        }else{
            mt_srand((double)microtime()*10000);
            $charid = strtoupper(md5(uniqid(rand(), true)));
            $hyphen = chr(45);// "-"
            $uuid = chr(123)// "{"
                .substr($charid, 0, 8).$hyphen
                .substr($charid, 8, 4).$hyphen
                .substr($charid,12, 4).$hyphen
                .substr($charid,16, 4).$hyphen
                .substr($charid,20,12)
                .chr(125);// "}"
            return $uuid;
        }
	}
    
    public function insert($table,$data = []){
        $data['created_at'] = \Carbon\Carbon::now();
        $data['updated_at'] = \Carbon\Carbon::now();
        $data['createdby'] = Auth::user()->id;
        $insert = DB::table($table)->insert($data);
        $id = DB::getPdo()->lastInsertId();
        $rec = DB::table($table)->where('id',$id)->first();
        return $rec;
    }

    public function update($table,$data = [],$condition){
        $update_data = DB::table($table)->where($condition)->update($data);
        // $getUpdatedRecord = DB::table($table)->where($condition)->first();
        return true;
    }

    public function getAll($table,$data = [],$condition,$jointables = [],$columns = []){
        $get = DB::table($table);
        if(count($jointables) > 0){
            foreach($jointables as $tables){
                $get->leftJoin(...$tables);
            }
        }
        return $get->select($columns)->get();
    }

    public function getByRecord($table,$data = [],$condition,$jointables = [],$columns = []){
        $rec = DB::table($table)->where($condition);
        if(count($jointables) > 0){
            foreach($jointables as $tables){
                $rec->leftJoin(...$tables);
            }
        }
        return $rec->first();
    }

    public function delete($table,$data = [],$condition){
        $delete = DB::table($table)->where($condition)->delete();

        return true;
    }

    public function recordChecker($table,$condition){
        $counter = DB::table($table)->where($condition)->count();

        return $counter;
    }

    public function ApprovalMatrixActivityLog($module, $record_id){

        //first get all the approver in the module
        $get_approvers = approval_matrix_setting::where('module_id',$module)->get();

        if(count($get_approvers) == 0){
            return ['status' => 'error', 'message' => 'Please setup approval matrix for this module thanks.!'];
        }else{

            foreach($get_approvers as $approvers){
               
                foreach($approvers->signatories as $approver){
                    $activity_matrix = new approval_activity_log;
                    $activity_matrix->module_id = $module;
                    $activity_matrix->rec_id = $record_id;
                    $activity_matrix->user_id = $approver['user'];
                    $activity_matrix->order = $approvers->level;
                    $activity_matrix->save();
                }
               
            }

            $get_first_approver = approval_activity_log::where('module_id',$module)
                                                       ->where('rec_id',$record_id)
                                                       ->first();
            return ['status' => 'success', 'message' => $get_first_approver->user_id];
           
        }

    }

    public function approverDecision($module, $record_id, $user){

        //get the last level of approving level orders
        $max_level = approval_activity_log::select('order')->where('module_id',$module)
                                        ->where('rec_id',$record_id)
                                        ->orderBy('order','DESC')
                                        ->first();

        //get the approver level of order
        $check_seq = approval_activity_log::where('module_id',$module)
                                        ->where('rec_id',$record_id)
                                        ->where('user_id',$user)
                                        ->first();

        $decide = approval_activity_log::where('module_id',$module)
                                        ->where('rec_id',$record_id)
                                        ->where('user_id',$user)
                                        ->update(['decision' => 'A']);

        if($max_level->order == $check_seq->order){
            //if the user is the last level in approver return 0
            return 0;
        }else{
            //get the next approver
            $next =  approval_activity_log::select('user_id')->where('module_id',$module)
                                                    ->where('rec_id',$record_id)
                                                    ->where('order','>',$check_seq->order)
                                                    ->orderBy('order','asc')->first();

            return $next->user_id;
        }
    }
	
}