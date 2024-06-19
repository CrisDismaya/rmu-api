<?php

namespace App\Http\Controllers\api_v1;

use Illuminate\Http\Request;
use App\Http\Controllers\api_v1\BaseController as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\approval_matrix_setting;
use App\Models\user_role;
use App\Models\system_menu;
use App\Http\Traits\resuableQuery;



class UserController extends BaseController
{
	//
    use resuableQuery; //helper traits

	public function getRoles()
	{

		try {
			return user_role::where('id', '!=', '4')->get();
		} catch (\Throwable $th) {
			return $this->sendError($th->errorInfo[2]);
		}
	}

	public function login(Request $request)
	{
		try {
			if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
				$user = Auth::user();
				$success['token'] =  $user->createToken('UserToken')->plainTextToken;
				$success['name'] =  $user->firstname . ' ' . $user->middlename . ' ' . $user->lastname;
				$success['role'] =  $user->userrole;

				return $this->sendResponse($success, 'User login successfully.');
			} else {
				return $this->sendError('Unauthorized', ['error' => 'Unauthorized']);
			}
		} catch (\Throwable $th) {
			return $this->sendError($th->errorInfo[2]);
		}
	}

	public function register(Request $request)
	{
		try {
			$validator = Validator::make($request->all(), [
				'employee_no' => 'required',
				'firstname' => 'required',
				'lastname' => 'required',
				'email' => 'required|email|unique:users',
				'userrole' => 'required',
				'branch' => 'required',
				'password' => 'required',
			]);

			if ($validator->fails()) {
				return $this->sendError('Validation Error.', $validator->errors());
			}

			$checker = User::where('employee_no', $request->employee_no)->count();

			if ($checker > 0) {
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
		} catch (\Throwable $th) {
			return $this->sendError($th->errorInfo[2]);
		}
	}

	public function updateUser(Request $request, $id)
	{

		try {
			$validator = Validator::make($request->all(), [
				'employee_no' => 'required',
				'firstname' => 'required',
				'lastname' => 'required',
				// 'email' => 'required|email|unique:users',
				'userrole' => 'required',
				'branch' => 'required',

			]);

			if ($validator->fails()) {
				return $this->sendError('Validation Error.', $validator->errors());
			}

			$input = $request->all();
			unset($input['password']);
			$user = User::where('id', $id)->update($input);
			//$success['default_password'] =  $random_password;

			return $this->sendResponse([], 'User updated successfully.');
		} catch (\Throwable $th) {
			return $this->sendError($th->errorInfo[2]);
		}
	}

	public function users()
	{
		try {


			$query = DB::table('users as a')
				->leftjoin('branches as b', 'b.id', 'a.branch')
				->select('a.*', 'b.name as branch_name')
				->where('a.id', '!=', '1')
				->get();

			return $query;
		} catch (\Throwable $th) {
			return $this->sendError($th->errorInfo[2]);
		}
	}

	public function changePassword(Request $request)
	{

		try {


			$validator = Validator::make($request->all(), [
				'old_password' => 'required',
				'new_password' => 'required',
			]);

			if ($validator->fails()) {
				return $this->sendError('Validation Error.', $validator->errors());
			}

			// $old_password = Hash::make($request->old_password);
			// dd($old_password);
			$user = User::where('id', Auth::user()->id)->first();


			if (Hash::check($request->old_password, $user->password)) {
				// The passwords match...
				$new_password = Hash::make($request->new_password);
				$update_password = User::where('id', Auth::user()->id)->update(['password' => $new_password]);

				return $this->sendResponse([], 'User update password successfully.');
			} else {
				return $this->sendError('Validation Error.', 'Old password not correct!');
			}
		} catch (\Throwable $th) {
			return $this->sendError($th->errorInfo[2]);
		}
	}

	public function deactivateUser($id, $status)
	{
		try {

			$update_status = User::where('id', $id)->update(['status' => $status]);

			return $this->sendResponse([], 'User deactivate successfully.');
		} catch (\Throwable $th) {
			return $this->sendError($th->errorInfo[2]);
		}
	}

	public function createApprovalMatrix(Request $request)
	{

		try {

			DB::beginTransaction();
			$approvers = approval_matrix_setting::where('module_id', $request->data[0]['module_id'])->get();

			$level = count($approvers);

			foreach ($request->data as $input) {
				$validator = Validator::make($input, [
					'module_id' => 'required',
					'level' => 'required',
					'signatories' => 'required',
				]);



				if ($validator->fails()) {
					return $this->sendError('Validation Error.', $validator->errors());
				}


				foreach ($approvers as $list) {
					if ($level > 0) {
						$input['level'] = $list->level + $input['level'];
					}

					foreach ($list->signatories as $approver) {

						foreach ($input['signatories'] as $req_approver) {

							if ($req_approver['user'] == $approver['user']) {
								return $this->sendError('Validation Error.', 'This signatory already exists in this module!');
							}
						}
					}
				}

				$create_natrix = approval_matrix_setting::create($input);
			}

			DB::commit();

			return $this->sendResponse([], 'success');
		} catch (\Throwable $th) {
			return $this->sendError($th->errorInfo[2]);
		}
	}

	public function currentModule($page)
	{

		try {


			$module = system_menu::select('id')->where('file_path', $page)->first();
			$data = '';
			if (empty($module)) {
				$data = 'No data returned';
			} else {
				$data = $module->id;
			}

			return $data;
		} catch (\Throwable $th) {
			return $this->sendError($th->errorInfo[2]);
		}
	}

	public function getAllModules()
	{
		try {
            $cteQuery = $this->cteQuery();

            $stmt = DB::select("
                DECLARE @userId INT = :userId;
                {$cteQuery}

                SELECT
                    CASE (SELECT COUNT(approverId) FROM approvers WHERE module_id = menu.id AND approverId = @userId)
                        WHEN 1 THEN 'Approver'
                        ELSE 'Maker'
                    END AS roles,
                    menu.id,
                    menu.category_name,
                    menu.menu_name,
                    menu.file_path,
                    menu.parent_id
                FROM users usr
                INNER JOIN user_role role ON usr.userrole = role.user_role_name
                INNER JOIN user_role_menu_mapping map ON role.id = map.user_role_id
                INNER JOIN system_menu menu ON map.menu_id = menu.id
                WHERE menu.status = 1 AND usr.id = @userId
                ",
                [ 'userId' => Auth::user()->id ]
            );

            return $stmt;
		} catch (\Throwable $th) {
			return $this->sendError($th->errorInfo[2]);
		}
	}

	public function removeMatrix($id)
	{

		try {

			$approvers = approval_matrix_setting::where('id', $id)->delete();

			return $this->sendResponse([], 'success');
		} catch (\Throwable $th) {
			return $this->sendError($th->errorInfo[2]);
		}
	}

	public function approverByPage($moduleid)
	{

		try {

			$users = array();
			$raw = array();
			$approvers = approval_matrix_setting::where('module_id', $moduleid)->get();
			foreach ($approvers as $key => $list) {
				foreach ($list->signatories as $approver) {
					array_push($users, $approver['user']);
					array_push($raw, ['approver' => $approver['user'], 'level' => $key + 1, 'rec_id' => $list->id]);
				}
			}

			$final_data = [];

			$get_approvers = User::select('id', 'firstname', 'middlename', 'lastname')->whereIn('id', $users)->get();
			foreach ($get_approvers as $approver) {
				foreach ($raw as $data) {
					if ($approver->id == $data['approver']) {
						array_push($final_data, [
							'id' => $data['rec_id'],
							'name' => $approver->firstname . ' ' . $approver->middlename . ' ' . $approver->lastname,
							'level' => $data['level']
						]);
					}
				}
			}

			return $final_data;
		} catch (\Throwable $th) {
			return $this->sendError($th->errorInfo[2]);
		}
	}

	public function getAllNotification()
	{
        $cteQuery = $this->cteQuery();
        $stmt = DB::select("
            DECLARE @approverId INT = :approverId, @makerId INT = :makerId, @branchId INT = :branchId;
            {$cteQuery}

            SELECT * FROM (
                SELECT
                    'Repo request price' AS module, rud.id, rud.status, req.approver, CONCAT(usr.firstname,' ',usr.lastname) AS requestor,
                    req.created_by AS maker, '_approval-unit.php' AS link,

                    CASE (SELECT COUNT(approverId) FROM approvers WHERE module_id = 6 AND approverId = @approverId)
                        WHEN 1 THEN 'Approver'
                        ELSE 'Maker'
                    END AS roles,
                    6 AS module_id
                FROM recieve_unit_details rud
                INNER JOIN request_approvals req ON req.received_unit_id = rud.id
                INNER JOIN users usr ON usr.id = req.created_by

                UNION ALL

                SELECT
                    'Repo request refurbish' as module, req.id, req.status, req.approver, CONCAT(usr.firstname,' ',usr.lastname) as requestor,
                    req.maker, '_refurbish-unit.php' as link,

                    CASE (SELECT COUNT(approverId) FROM approvers WHERE module_id = 23 AND approverId = @approverId)
                        WHEN 1 THEN 'Approver'
                        ELSE 'Maker'
                    END AS roles,
                    23 AS module_id
                FROM request_refurbishes req
                INNER JOIN users usr on usr.id = req.maker

                UNION ALL

                SELECT
                    'Sales approval' as module, sold.id, sold.status, sold.approver, CONCAT(usr.firstname,' ',usr.lastname) as requestor,
                    sold.maker, '_sales-tagging.php' as link,

                    CASE (SELECT COUNT(approverId) FROM approvers WHERE module_id = 19 AND approverId = @approverId)
                        WHEN 1 THEN 'Approver'
                        ELSE 'Maker'
                    END AS roles,
                    19 AS module_id
                FROM sold_units sold
                INNER JOIN users usr on usr.id = sold.maker

                UNION ALL

                SELECT
                    'Stock Transfer' as module, sta.id, sta.status, sta.approver, CONCAT(usr.firstname,' ', usr.lastname) AS requestor,
                    sta.created_by, '_stock_transfer.php' AS link,

                    CASE (SELECT COUNT(approverId) FROM approvers WHERE module_id = 5 AND approverId = @approverId)
                        WHEN 1 THEN 'Approver'
                        ELSE 'Maker'
                    END AS roles,
                    5 AS module_id
                FROM stock_transfer_approval sta
                INNER JOIN users usr ON usr.id = sta.created_by

                UNION ALL

                SELECT
                    'Settle Refurbishment' as module, sta.id, sta.status, sta.approver, CONCAT(usr.firstname,' ', usr.lastname) AS requestor,
                    sta.maker AS created_by, '_refurbish-process.php' AS link,

                    CASE (SELECT COUNT(approverId) FROM approvers WHERE module_id = 26 AND approverId = @approverId)
                        WHEN 1 THEN 'Approver'
                        ELSE 'Maker'
                    END AS roles,
                    26 AS module_id
                FROM refurbish_processes sta
                INNER JOIN users usr ON usr.id = sta.maker

                UNION ALL

                SELECT
                    'Received Stock Transfer' as module, stu1.id, stu1.is_received AS [status],
                    (CASE WHEN sta1.status = 1 THEN sta1.to_branch WHEN sta1.status = 2 THEN sta1.from_branch END) AS approver,
                    CONCAT(usr.firstname,' ', usr.lastname) as requestor, sta1.created_by, '_stock_transfer_received.php' as link,

                    CASE (SELECT COUNT(approverId) FROM approvers WHERE module_id = 22 AND approverId = @approverId)
                        WHEN 1 THEN 'Approver'
                        ELSE 'Maker'
                    END AS roles,
                    22 AS module_id
                FROM (
                    SELECT MAX(sta.id) AS approvalid, MAX(stu.recieved_unit_id) AS recievedid, MAX(stu.id) AS unitid
                    FROM stock_transfer_approval sta
                    INNER JOIN stock_transfer_unit stu ON sta.id = stu.stock_transfer_id
                    GROUP BY stu.recieved_unit_id
                ) sub
                INNER JOIN stock_transfer_approval sta1 ON sub.approvalid = sta1.id
                INNER JOIN stock_transfer_unit stu1 ON sub.unitid = stu1.id AND sub.approvalid = stu1.stock_transfer_id AND sub.recievedid = stu1.recieved_unit_id
                INNER JOIN recieve_unit_details rud1 ON sub.recievedid = rud1.id
                LEFT JOIN branches brh ON rud1.branch = brh.id
                LEFT JOIN users usr ON sta1.created_by = usr.id
                WHERE sta1.status = 1

                UNION ALL

                SELECT
                    'Repo Tagging' as module, repo.id, CASE WHEN received.status = 4 THEN 0 ELSE 0 END AS status, received.approver,
                    CONCAT(usr.firstname,' ', usr.lastname) as requestor, branch.id AS created_by, 'repo_tagging_approval.php' as link,

                    CASE (SELECT COUNT(approverId) FROM approvers WHERE module_id = 25 AND approverId = @approverId)
                        WHEN 1 THEN 'Approver'
                        ELSE 'Maker'
                    END AS roles,
                    25 AS module_id
                FROM  repo_details repo
                INNER JOIN recieve_unit_details received on repo.id = received.repo_id
                INNER JOIN branches branch on repo.branch_id = branch.id
                INNER JOIN users usr ON received.approver = usr.id
                WHERE received.status = 4

                UNION ALL

                SELECT
                    'For Settle Refurbishment' as module, req.id, CASE WHEN req.[status] = 3 THEN 0 ELSE req.[status] END AS [status],
                    req.branch AS approver, CONCAT(usr.firstname,' ',usr.lastname) AS requestor, req.maker, '_refurbish-process.php' as link,

                    CASE (SELECT COUNT(approverId) FROM approvers WHERE module_id = 26 AND approverId = @approverId)
                        WHEN 1 THEN 'Approver'
                        ELSE 'Maker'
                    END AS roles,
                    26 AS module_id
                FROM request_refurbishes req
                INNER JOIN users usr ON usr.id = req.maker
                WHERE req.status = 3
            ) AS notif
            WHERE notif.status = 0 AND CAST(notif.approver AS INT) = CAST((CASE WHEN notif.module IN ('Received Stock Transfer', 'For Settle Refurbishment') THEN @branchId ELSE @approverId END) AS INT)

            UNION ALL

            SELECT * FROM (
                SELECT
                    'Repo request price' AS module, rud.id, rud.status, req.approver, CONCAT(usr.firstname,' ',usr.lastname) AS requestor,
                    req.created_by AS maker,'_approval-unit.php' AS link,

                    CASE (SELECT COUNT(approverId) FROM approvers WHERE module_id = 6 AND approverId = @approverId)
                        WHEN 1 THEN 'Approver'
                        ELSE 'Maker'
                    END AS roles,
                    6 AS module_id
                FROM recieve_unit_details rud
                INNER JOIN request_approvals req ON req.received_unit_id = rud.id
                INNER JOIN users usr ON usr.id = req.approver

                UNION ALL

                SELECT
                    'Repo request refurbish' AS module, req.id, req.status, req.approver, CONCAT(usr.firstname,' ',usr.lastname) AS requestor,
                    req.maker, '_refurbish-unit.php' AS link,

                    CASE (SELECT COUNT(approverId) FROM approvers WHERE module_id = 23 AND approverId = @approverId)
                        WHEN 1 THEN 'Approver'
                        ELSE 'Maker'
                    END AS roles,
                    23 AS module_id
                FROM request_refurbishes req
                INNER JOIN users usr ON usr.id = req.approver

                UNION ALL

                SELECT
                    'Sales approval' AS module, sold.id, sold.status, sold.approver, CONCAT(usr.firstname,' ',usr.lastname) AS requestor,
                    sold.maker, '_sales-tagging.php' AS link,

                    CASE (SELECT COUNT(approverId) FROM approvers WHERE module_id = 19 AND approverId = @approverId)
                        WHEN 1 THEN 'Approver'
                        ELSE 'Maker'
                    END AS roles,
                    19 AS module_id
                FROM sold_units sold
                INNER JOIN users usr ON usr.id = sold.approver

                UNION ALL

                SELECT
                    'Settle Refurbishment' AS module, sta.id, sta.status, sta.approver, CONCAT(usr.firstname,' ', usr.lastname) AS requestor,
                    sta.maker AS created_by, '_refurbish-process.php' AS link,

                    CASE (SELECT COUNT(approverId) FROM approvers WHERE module_id = 26 AND approverId = @approverId)
                        WHEN 1 THEN 'Approver'
                        ELSE 'Maker'
                    END AS roles,
                    26 AS module_id
                FROM refurbish_processes sta
                INNER JOIN users usr ON usr.id = sta.maker

                UNION ALL

                SELECT
                    'Stock Transfer' AS module, sta.id, sta.status, sta.approver, CONCAT(usr.firstname,' ', usr.lastname) AS requestor,
                    sta.created_by, '_stock_transfer.php' AS link,

                    CASE (SELECT COUNT(approverId) FROM approvers WHERE module_id = 5 AND approverId = @approverId)
                        WHEN 1 THEN 'Approver'
                        ELSE 'Maker'
                    END AS roles,
                    5 AS module_id
                FROM stock_transfer_approval sta
                INNER JOIN users usr ON usr.id = sta.created_by

            ) AS disapprove
            WHERE disapprove.status = 2 AND disapprove.maker = @makerId
            ",
            [
                'approverId' => Auth::user()->id,
                'makerId' => Auth::user()->id,
                'branchId' => Auth::user()->branch,
            ]
        );

        return $stmt;
	}
}
