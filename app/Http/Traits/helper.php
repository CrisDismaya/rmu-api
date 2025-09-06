<?php

namespace App\Http\Traits;

use App\Models\approval_activity_log;
use App\Models\approval_matrix_setting;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;
use Exception;

trait helper
{

	public function uuidGenerator()
	{
		if (function_exists('com_create_guid')) {
			return com_create_guid();
		} else {
			mt_srand((float)microtime() * 10000);
			$charid = strtoupper(md5(uniqid(rand(), true)));
			$hyphen = chr(45); // "-"
			$uuid = chr(123) // "{"
				. substr($charid, 0, 8) . $hyphen
				. substr($charid, 8, 4) . $hyphen
				. substr($charid, 12, 4) . $hyphen
				. substr($charid, 16, 4) . $hyphen
				. substr($charid, 20, 12)
				. chr(125); // "}"
			return $uuid;
		}
	}

	public function insert($table, $data = [])
	{
		$data['created_at'] = \Carbon\Carbon::now();
		$data['updated_at'] = \Carbon\Carbon::now();
		$data['createdby'] = Auth::user()->id;
		$insert = DB::table($table)->insert($data);
		$id = DB::getPdo()->lastInsertId();
		$rec = DB::table($table)->where('id', $id)->first();
		return $rec;
	}

	public function update($table, $data = [], $condition)
	{
		$update_data = DB::table($table)->where($condition)->update($data);
		// $getUpdatedRecord = DB::table($table)->where($condition)->first();
		return true;
	}

	public function getAll($table, $data = [], $condition, $jointables = [], $columns = [])
	{
		$get = DB::table($table);
		if (count($jointables) > 0) {
			foreach ($jointables as $tables) {
				$get->leftJoin(...$tables);
			}
		}
		return $get->select($columns)->get();
	}

	public function getByRecord($table, $data = [], $condition, $jointables = [], $columns = [])
	{
		$rec = DB::table($table)->where($condition);
		if (count($jointables) > 0) {
			foreach ($jointables as $tables) {
				$rec->leftJoin(...$tables);
			}
		}
		return $rec->first();
	}

	public function delete($table, $data = [], $condition)
	{
		$delete = DB::table($table)->where($condition)->delete();

		return true;
	}

	public function recordChecker($table, $condition)
	{
		$counter = DB::table($table)->where($condition)->count();

		return $counter;
	}

	public function ApprovalMatrixActivityLog($module, $record_id)
	{

		//first get all the approver in the module
		$get_approvers = approval_matrix_setting::where('module_id', $module)->get();

		if (count($get_approvers) == 0) {
			return ['status' => 'error', 'message' => 'Please setup approval matrix for this module thanks.!'];
		} else {

			foreach ($get_approvers as $approvers) {

				foreach ($approvers->signatories as $approver) {
					$activity_matrix = new approval_activity_log;
					$activity_matrix->module_id = $module;
					$activity_matrix->rec_id = $record_id;
					$activity_matrix->user_id = $approver['user'];
					$activity_matrix->order = $approvers->level;
					$activity_matrix->save();
				}
			}

			$get_first_approver = approval_activity_log::where('module_id', $module)
				->where('rec_id', $record_id)
				->first();
			return ['status' => 'success', 'message' => $get_first_approver->user_id];
		}
	}

    public function approverDecision($module, $record_id, $userId)
    {
        try {
            $user = DB::table('users')
                ->leftJoin('user_role as role', 'users.userrole', '=', 'role.user_role_name')
                ->where('users.id', $userId)
                ->select('users.*', 'role.id as role_id')
                ->first();

            // Get the last level of approving level orders
            $max_level = approval_activity_log::select('order')
                ->where('module_id', $module)
                ->where('rec_id', $record_id)
                ->whereNull('decision')
                ->orderBy('order', 'DESC')
                ->first();

            Log::info('Max approver level fetched', [
                'module_id' => $module,
                'rec_id' => $record_id,
                'max_order' => $max_level?->order,
            ]);

            // Get the current user's pending approval entry
            $check_seq = approval_activity_log::where('module_id', $module)
                ->where('rec_id', $record_id)
                ->whereNull('decision')
                ->where('user_id', $user->role_id)
                ->first();

            Log::info('Current user approval level', [
                'user_id' => $user->id,
                'current_order' => $check_seq?->order,
            ]);

            // Try to update approval regardless
            $updated = approval_activity_log::where('module_id', $module)
                ->where('rec_id', $record_id)
                ->where('user_id', $user->role_id)
                ->whereNull('decision')
                ->update([
                    'decision' => 'A',
                    'approved_by' => $user->id,
                ]);

            Log::info('Approval updated', [
                'user_id' => $user,
                'updated_rows' => $updated,
            ]);

            // If the user has no pending approval
            if (!$check_seq) {
                Log::warning('User does not have a pending approval entry', [
                    'user_id' => $user->id,
                    'module_id' => $module,
                    'rec_id' => $record_id,
                ]);
                return 0;
            }

            // If user is the last approver in the sequence
            if ($max_level && $max_level->order == $check_seq?->order) {
                Log::info("User is last level approver", [
                    'user_id' => $user->id,
                    'module_id' => $module,
                    'rec_id' => $record_id,
                ]);
                return 0;
            }

            // Get the next approver after current user's level
            $next = approval_activity_log::select('user_id')
                ->where('module_id', $module)
                ->where('rec_id', $record_id)
                ->where('order', '>', $check_seq?->order)
                ->whereNull('decision')
                ->orderBy('order', 'asc')
                ->first();

            Log::info("Next approver fetched", [
                'current_user_id' => $user->id,
                'next_user_id' => $next?->user_id,
                'module_id' => $module,
                'rec_id' => $record_id,
            ]);

            return $next?->user_id ?? 0;

        } catch (\Throwable $th) {
            Log::error('Error in approverDecision', [
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
                'module_id' => $module,
                'rec_id' => $record_id,
                'user_id' => $user,
            ]);
            return 0;
        }
    }

	public function disapprovedDecision($module, $record_id, $user)
	{
		try {

			$result = DB::table('approval_activity_logs')
				->select(DB::raw('MIN(user_id) as userid'))
				->where('module_id', '=', $module)
				->where('rec_id', '=', $record_id)
				->first();
			$userId = $result->userid;

			approval_activity_log::where('module_id', $module)
				->where('rec_id', $record_id)
				->update([
                    'decision' => 'D'
                ]);
			return $userId;
		} catch (\Throwable $th) {
			return $this->sendError($th->errorInfo[2]);
		}
	}

	public function rollBaclDecision($module, $record_id, $userId)
	{
		try {
            $user = DB::table('users')
                ->leftJoin('user_role as role', 'users.userrole', '=', 'role.user_role_name')
                ->where('users.id', $userId)
                ->select('users.*', 'role.id as role_id')
                ->first();

			approval_activity_log::where('module_id', $module)
				->where('rec_id', $record_id)
				->where('user_id', $user->role_id)
				->update([
                    'decision' => null,
                ]);
		} catch (\Throwable $th) {
			return $this->sendError($th->errorInfo[2]);
		}
	}

    public function checkIfApproved($moduleId, $recordId, $roleId)
    {
        $approver =  approval_activity_log::with(['user'])
            ->where([
                ['module_id', $moduleId],
                ['rec_id', $recordId],
                ['user_id', $roleId],
                ['decision', 'A']
            ]);

        Log::info('Checking if approved', [
            'module_id' => $moduleId,
            'rec_id' => $recordId,
            'user_id' => $roleId
        ]);

        $status = $approver->exists();
        $approved = $approver->first();

        return [
            'status' => $status,
            'approver' => $approved,
            'name' => $approved && $approved->user
                        ? $approved->user->firstname . ' ' . $approved->user->lastname
                        : null,
        ];
    }
}
