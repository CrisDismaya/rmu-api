<?php

namespace App\Http\Controllers\api_v1;

use Illuminate\Http\Request;
use App\Http\Controllers\api_v1\BaseController as BaseController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\approval_matrix_setting;
use App\Models\user_role;
use App\Models\system_menu;



class UserController extends BaseController
{
    //

    public function getRoles(){

        return user_role::all();
    }

    public function login(Request $request)
    {
        if(Auth::attempt(['email' => $request->email, 'password' => $request->password])){ 
            $user = Auth::user(); 
            $success['token'] =  $user->createToken('UserToken')->plainTextToken; 
            $success['name'] =  $user->firstname.' '.$user->middlename.' '.$user->lastname;
            $success['role'] =  $user->userrole;

            return $this->sendResponse($success, 'User login successfully.');
        }else{ 
            return $this->sendError('Unauthorized', ['error'=>'Unauthorized']);
        }
         
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_no' => 'required',
            'firstname' => 'required',
            'lastname' => 'required',
            'email' => 'required|email|unique:users',
            'userrole' => 'required',
            'branch' => 'required',
            'password' => 'required',
        ]);
   
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }
        
        $checker = User::where('employee_no', $request->employee_no)->count();

        if($checker > 0){
            return $this->sendError('Validation Error.', 'User already exists!');       
        }

        $input = $request->all();
        $password_length = 8;
      //  $random_password = substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($password_length/strlen($x)) )),1,$password_length);
        $input['password'] = Hash::make($input['password']);
        $input['status'] = '1';
        $user = User::create($input);
        //$success['default_password'] =  $random_password;
   
        return $this->sendResponse([], 'User register successfully.');
    }

    public function updateUser(Request $request, $id){
        $validator = Validator::make($request->all(), [
            'employee_no' => 'required',
            'firstname' => 'required',
            'lastname' => 'required',
            // 'email' => 'required|email|unique:users',
            'userrole' => 'required',
            'branch' => 'required',
          
        ]);
   
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }

        $input = $request->all();
        unset($input['password']);
        $user = User::where('id',$id)->update($input);
        //$success['default_password'] =  $random_password;
   
        return $this->sendResponse([], 'User updated successfully.');
    }

    public function users(){
        $query = DB::table('users as a')
                   ->leftjoin('branches as b','b.id','a.branch')
                   ->select('a.*','b.name as branch_name')
                   ->get();

        return $query;
    }

    public function changePassword(Request $request){
        $validator = Validator::make($request->all(), [
            'old_password' => 'required',
            'new_password' => 'required',
        ]);
   
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }
       
        // $old_password = Hash::make($request->old_password);
        // dd($old_password);
        $user = User::where('id',Auth::user()->id)->first();


        if (Hash::check($request->old_password, $user->password)) {
            // The passwords match...
            $new_password = Hash::make($request->new_password);
            $update_password = User::where('id',Auth::user()->id)->update(['password' => $new_password]);

            return $this->sendResponse([], 'User update password successfully.');
        }else{
            return $this->sendError('Validation Error.', 'Old password not correct!');
        }

    }

    public function deactivateUser($id,$status){
        $update_status = User::where('id',$id)->update(['status' => $status]);

        return $this->sendResponse([], 'User deactivate successfully.');
    }

    public function createApprovalMatrix(Request $request){
        
        DB::beginTransaction();
        $approvers = approval_matrix_setting::where('module_id',$request->data[0]['module_id'])->get();

        $level = count($approvers);
       
        foreach($request->data as $input){
            $validator = Validator::make($input, [
                'module_id' => 'required',
                'level' => 'required',
                'signatories' => 'required',
            ]);

          
       
            if($validator->fails()){
                return $this->sendError('Validation Error.', $validator->errors());       
            }

            
            foreach($approvers as $list){
                if($level > 0){
                    $input['level'] = $list->level + $input['level'];
                }

                foreach($list->signatories as $approver){
          
                    foreach($input['signatories'] as $req_approver){
                       
                       if($req_approver['user'] == $approver['user']){
                            return $this->sendError('Validation Error.', 'This signatory already exists in this module!');   
                       }
                    }
                }
            }

            $create_natrix = approval_matrix_setting::create($input);
        }

        DB::commit();
        
        return $this->sendResponse([], 'success');
    }

    public function currentModule($page){
        $module = system_menu::select('id')->where('file_path',$page)->first();
        $data = '';
        if(empty($module)){
          $data = 'No data returned';
        }else{
          $data = $module->id;
        }
  
        return $data;
      }

    public function getAllModules(){

        $query = DB::table('users as a')
                   ->join('user_role as b','b.user_role_name','a.userrole')
                   ->join('user_role_menu_mapping as c','c.user_role_id','b.id')
                   ->join('system_menu as d','d.id','c.menu_id')
                   ->select('d.*')
                   ->where('a.id',Auth::user()->id)
                   ->get();
        return $query;
    }

    public function removeMatrix($id){
        $approvers = approval_matrix_setting::where('id',$id)->delete();

        return $this->sendResponse([], 'success');
    }

    public function approverByPage($moduleid){
        $users = array();
        $raw = array();
        $approvers = approval_matrix_setting::where('module_id',$moduleid)->get();
        foreach($approvers as $key=>$list){
            foreach($list->signatories as $approver){
                array_push($users,$approver['user']);
                array_push($raw,['approver' => $approver['user'],'level' => $key+1,'rec_id' => $list->id]);
                
            }
        }

        $final_data = [];

        $get_approvers = User::select('id','firstname','middlename','lastname')->whereIn('id',$users)->get();
        foreach($get_approvers as $approver){
            foreach($raw as $data){
                if($approver->id == $data['approver']){
                    array_push($final_data,[
                        'id' =>$data['rec_id'],
                        'name' => $approver->firstname.' '.$approver->middlename.' '.$approver->lastname,
                        'level' => $data['level']
                    ]);
                }
            }
        }

        return $final_data;
    }

    public function getAllNotification(){
        $param = ['approver' => Auth::user()->id, 'maker' => Auth::user()->id];
        $query = DB::select("
                                select notif.* from
                                (
                                    select 'Repo request price' as module, a.id,a.status,b.approver, CONCAT(c.firstname,' ',c.lastname) as requestor,
                                    b.created_by as maker,'_approval-unit.php' as link
                                    from recieve_unit_details as a inner join request_approvals b on b.received_unit_id = a.id
                                    inner join users as c on c.id = b.created_by
                                    
                                    union all
                                    
                                    select 'Repo request refurbish' as module, a.id,a.status,a.approver,CONCAT(c.firstname,' ',c.lastname) as requestor,
                                     a.maker,'_refurbish-unit.php' as link
                                     from request_refurbishes as a
                                    inner join users as c on c.id = a.maker
                                    
                                    union all
                                    
                                    select 'Sales approval' as module, a.id,a.status,a.approver,CONCAT(c.firstname,' ',c.lastname) as requestor,
                                     a.maker,'_sales-tagging.php' as link
                                     from sold_units as a
                                    inner join users as c on c.id = a.maker

                                    union all

                                    SELECT 'Stock Transfer' as module, sta.id, sta.status, sta.approver, CONCAT(usr.firstname,' ', usr.lastname) as requestor, sta.created_by, '_stock_transfer.php' as link
                                    FROM stock_transfer_approval sta
                                    inner join users usr on usr.id = sta.created_by
                                
                                ) as notif
                                where notif.status = 0 and notif.approver = :approver

                                union all

                                select disapprove.* from
                                (
                                    select 'Repo request price' as module, a.id,a.status,b.approver, CONCAT(c.firstname,' ',c.lastname) as requestor,
                                    b.created_by as maker,'_approval-unit.php' as link
                                    from recieve_unit_details as a inner join request_approvals b on b.received_unit_id = a.id
                                    inner join users as c on c.id = b.approver
                                    
                                    union all
                                    
                                    select 'Repo request refurbish' as module, a.id,a.status,a.approver,CONCAT(c.firstname,' ',c.lastname) as requestor,
                                     a.maker,'_refurbish-unit.php' as link
                                     from request_refurbishes as a
                                    inner join users as c on c.id = a.approver
                                    
                                    union all
                                    
                                    select 'Sales approval' as module, a.id,a.status,a.approver,CONCAT(c.firstname,' ',c.lastname) as requestor,
                                     a.maker,'_sales-tagging.php' as link
                                     from sold_units as a
                                    inner join users as c on c.id = a.approver

                                    union all

                                    SELECT 'Stock Transfer' as module, sta.id, sta.status, sta.approver, CONCAT(usr.firstname,' ', usr.lastname) as requestor, sta.created_by, '_stock_transfer.php' as link
                                    FROM stock_transfer_approval sta
                                    inner join users usr on usr.id = sta.created_by
                                
                                ) as disapprove
                                where disapprove.status = 2 and disapprove.maker = :maker
                    ",$param);

        return $query;
    }
}
