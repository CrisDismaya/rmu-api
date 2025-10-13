<?php

namespace App\Http\Controllers\api_v1;

use App\Http\Controllers\api_v1\BaseController as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\request_refurbish;
use App\Models\refurbish_detail;
use App\Models\refurbishProcess;
use App\Models\repo AS RepoDetails;
use App\Models\user_role;
use App\Models\approval_matrix_setting AS ApprovalMatrixSetting;
use Illuminate\Http\Request;
use App\Http\Traits\helper;
use App\Http\Traits\ResuableQuery;
use Yajra\Datatables\Datatables;
use App\Http\Traits\ApprovalSequence;

class RequestRefurbishController extends BaseController
{
	//
	use helper, ResuableQuery, ApprovalSequence;

	public function listOfForRefurbish()
	{
		try {
            $user = Auth::user();

            $stmt = RepoDetails::query()
                ->select([
                    'received.id as receive_id',
                    'repo.id as repo_id',
                    'repo.model_engine',
                    'repo.model_chassis',
                    'repo.date_sold',
                    'branch.name as branchname',
                    'brand.brandname',
                    'model.model_name',
                    'color.name as color'
                ])
                ->from('repo_details as repo')
                ->join('recieve_unit_details as received', 'repo.id', '=', 'received.repo_id')
                ->join('branches as branch', 'repo.branch_id', '=', 'branch.id')
                ->join('brands as brand', 'repo.brand_id', '=', 'brand.id')
                ->join('unit_models as model', 'repo.model_id', '=', 'model.id')
                ->join('unit_colors as color', 'repo.color_id', '=', 'color.id')
                ->leftJoin(DB::raw("
                    (
                        SELECT
                            repo.id AS repo_id,
                            COUNT(upload.id) AS total_upload_required_files
                        FROM repo_details repo
                        LEFT JOIN files_uploaded upload
                            ON repo.id = upload.reference_id
                        AND repo.branch_id = upload.branch_id
                        INNER JOIN (
                            SELECT * FROM files WHERE isRequired = 1 AND status = 1
                        ) files ON upload.files_id = files.id
                        WHERE upload.is_deleted = 0
                        GROUP BY repo.id, upload.branch_id
                    ) files
                "), 'files.repo_id', '=', 'repo.id')
                ->leftJoin('sold_units as sold', function ($join) {
                    $join->on('repo.id', '=', 'sold.repo_id')
                        ->on('branch.id', '=', 'sold.branch')
                        ->where('sold.status', '=', 0);
                })
                ->leftJoin('request_approvals as appraisal', function ($join) {
                    $join->on('repo.id', '=', 'appraisal.repo_id')
                        ->on('branch.id', '=', 'appraisal.branch')
                        ->where('appraisal.status', '=', 0);
                })
                ->leftJoin(DB::raw("
                    (
                        SELECT units.recieved_unit_id AS received_id
                        FROM stock_transfer_unit units
                        INNER JOIN stock_transfer_approval approval
                            ON units.stock_transfer_id = approval.id
                        WHERE approval.status = 0
                    ) stock
                "), 'received.id', '=', 'stock.received_id')
                ->leftJoin('request_refurbishes as refurbish', function ($join) {
                    $join->on('repo.id', '=', 'refurbish.repo_id')
                        ->on('branch.id', '=', 'refurbish.branch');
                })
                ->where('received.status', '!=', 4)->where('received.is_sold', 'N')
                ->whereRaw('ISNULL(files.total_upload_required_files, 0) = (SELECT COUNT(*) FROM files WHERE isRequired = 1 AND status = 1)')
                ->whereRaw('ISNULL((SELECT COUNT(*) FROM recieve_unit_spare_parts WHERE recieve_id = received.id AND is_deleted = 0 AND refurb_id IS NULL), 0) > 0')
                ->whereNull('sold.id')
                ->whereNull('appraisal.id')
                ->whereNull('stock.received_id')
                ->whereNull('refurbish.repo_id');

            if ($user->userrole == 'Warehouse Custodian') {
				$stmt->where('branch.id', $user->branch);
			}

            return Datatables::of($stmt)
                ->order(function ($q) {
                    $q->orderByDesc('repo.id');
                })
                ->make(true);

		} catch (\Throwable $th) {
			return $this->sendError($th->getMessage());
		}
	}

	public function getMissingDamageParts($received_id)
	{
		try {
			return DB::table('recieve_unit_spare_parts as a')
				->join('spare_parts as b', 'b.id', 'a.parts_id')
				->select('b.*', 'a.price', 'a.id as received_ids')
				->where('recieve_id', $received_id)
				->where('is_deleted', '=', '0')
                ->whereNull('refurb_decision')->orWhere('refurb_decision', '!=', 'done')
				->get();
		} catch (\Throwable $th) {
			return $this->sendError($th->errorInfo[2]);
		}
	}

	public function getPartsForRefurbish(Request $request)
    {
        try {
            $receivedId = $request->received_id;

            $partsQuery = DB::table('recieve_unit_spare_parts as rsp')
                ->leftJoin('spare_parts as sp', 'sp.id', '=', 'rsp.parts_id')
                ->select(
                    'sp.*',
                    'rsp.price',
                    'rsp.id as record_id',
                    'rsp.actual_price',
                    DB::raw("COALESCE(rsp.refurb_decision, '') AS status")
                )
                ->where('rsp.recieve_id', $receivedId)
                ->where('rsp.is_deleted', 0);

            // Only include parts that are not yet refurbished or not marked "done"
            $partsQuery->where(function ($query) {
                $query->whereNull('rsp.refurb_decision')
                    ->orWhere('rsp.refurb_decision', '!=', 'done');
            });

            return $partsQuery->get();
        } catch (\Throwable $th) {
            return $this->sendError($th->getMessage());
        }
    }


	public function getRefurbishParts($repo_id)
	{
		try {
            return DB::select("SELECT
                    parts.id as received_ids,
                    CASE
                        WHEN parts.actual_price IS NULL THEN parts.price
                        ELSE parts.actual_price
                    END AS price,
                    spare.name,
                    parts.id
                FROM repo_details repo
                INNER JOIN recieve_unit_details received ON repo.id = received.repo_id
                LEFT JOIN recieve_unit_spare_parts parts ON received.id = parts.recieve_id
                LEFT JOIN spare_parts spare ON parts.parts_id = spare.id
                INNER JOIN request_refurbishes refurb ON repo.id = refurb.repo_id
                WHERE parts.is_deleted = 0 AND repo.id = :repoId",
                [ 'repoId' => $repo_id ]
            );

			// return DB::table("recieve_unit_details as received")
			// 	->join("recieve_unit_spare_parts as received_parts", "received.id", "received_parts.recieve_id")
			// 	->leftjoin("spare_parts as parts", "received_parts.parts_id", "parts.id")
			// 	->select("received_parts.id as received_ids", "received_parts.price", "parts.name", "parts.id")
			// 	->where('received.repo_id', '=', $repo_id)
			// 	->get();

			// $get_spare_missing = DB::table('refurbish_details as a')
			//     ->join('spare_parts as b', 'b.id', 'a.spare_parts')
			//     ->select('b.*', 'a.price')
			//     ->where('a.refurbish_id', $refurbishid)->get();

			// return $get_spare_missing;
		} catch (\Throwable $th) {
			return $this->sendError($th->errorInfo[2]);
		}
	}

	public function getUploadedDocuments($refurbishid)
	{

		try {

			$getDocuments = DB::table('refurbish_processes')->select('files_names')->where('refurbish_req_id', $refurbishid)->get();

			return $getDocuments;
		} catch (\Throwable $th) {
			return $this->sendError($th->errorInfo[2]);
		}
	}

	public function updateRefurbishProcess(Request $request, $id)
	{
		try {
			$file_list = array();
			$validator = Validator::make($request->all(), [
				'refurbish_id' => 'required|numeric',
				'spares' => 'required',
			]);

			if ($validator->fails()) {
				return $this->sendError('Validation Error.', $validator->errors());
			}

			$get_data = refurbishProcess::where('id', $id)->first(); // get record data first

			DB::beginTransaction();
			$arr = ['status' => '0'];
			if ($request->total_documents > 0) {

				$folder_path = 'image/Refurbish/' . strtoupper($request->repo_id . '-' . $request->model_engine . '-' . $request->model_chassis);
				$directory = public_path($folder_path);
				if (!File::isDirectory($directory)) {
					File::makeDirectory($directory, 0777, true, true);
				}

				$input = $request->all();

				for ($i = 0; $i < $request->total_documents; $i++) {

					$image = $request->file("related_documents_" . $i + 1);
					if ($image) {
						$image_name = strtoupper(uniqid() . '-' . $image->getClientOriginalName());
						$image->move($directory, $image_name);

						array_push($file_list, [
							'filename' => $image_name,
							'path' => $folder_path . '/' . $image_name
						]);
					}
				}

				$tmp_filelist = $get_data->files_names;
				$final_filelist = json_decode($tmp_filelist, true);

				for ($i = 0; $i < count($final_filelist); $i++) {
					array_push($file_list, [
						'filename' => $final_filelist[$i]['filename'],
						'path' => $final_filelist[$i]['path']
					]);
				}

				$arr = ['files_names' => json_encode($file_list), 'status' => '0'];
			}


			$get_data = refurbishProcess::where('id', $id)->update($arr);

			$spares = json_decode($request->spares, true);
			foreach ($spares as $parts) {
				DB::table("recieve_unit_spare_parts")
					->where("id", '=', $parts['received_parts_id'])
					->update([
						'actual_price' => $parts['actual_price'],
						'refurb_decision' => $parts['status'],
					]);
			}

			$matrix =  $this->ApprovalMatrixActivityLog($request->module_id, $id);

			if ($matrix['status'] == 'error') {
				return $matrix;
			} else {
				//update the first holder of the transaction
				$save_holder = refurbishProcess::where('id', $id)->update(['approver' => $matrix['message']]);
			}

			DB::commit();

			return $this->sendResponse([], 'Request refurbish approval saved.');
		} catch (\Throwable $th) {
			return $this->sendError($th->errorInfo[2]);
		}
	}

	public function proceedRefurbish(Request $request)
	{
        $validator = Validator::make($request->all(), [
            'refurbish_id' => 'required|numeric',
            'spares'       => 'required|json',
            'module_id'    => 'required|numeric',
            'repo_id'      => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }

        if ((int) $request->total_documents === 0) {
            return $this->sendError('Validation Error.', 'Please upload some documents');
        }

        return DB::transaction(function () use ($request) {
            $fileList = [];
            $folderPath = 'image/Refurbish/' . strtoupper(
                $request->repo_id . '-' . $request->model_engine . '-' . $request->model_chassis
            );

            $directory = public_path($folderPath);
            if (!File::isDirectory($directory)) {
                File::makeDirectory($directory, 0777, true, true);
            }

            // Handle documents
            for ($i = 0; $i < (int) $request->total_documents; $i++) {
                $image = $request->file("related_documents_" . $i);

                if ($image) {
                    $imageName = strtoupper(uniqid() . '-' . $image->getClientOriginalName());
                    $image->move($directory, $imageName);

                    $fileList[] = [
                        'filename' => $imageName,
                        'path'     => $folderPath . '/' . $imageName,
                    ];
                }
            }

            $refurbish = RefurbishProcess::create([
                'refurbish_req_id' => $request->refurbish_id,
                'maker'            => Auth::id(),
                'files_names'      => json_encode($fileList),
                're_class'         => $request->classification,
            ]);

            // Update spares
            $spares = json_decode($request->spares, true);
            foreach ($spares as $parts) {
                DB::table("recieve_unit_spare_parts")
                    ->where("id", $parts['received_parts_id'])
                    ->update([
                        'actual_price'    => (double) $parts['actual_price'],
                        'refurb_decision' => $parts['status'],
                        'refurb_id'       => $parts['status'] === 'done' ? $request->refurbish_id : null,
                    ]);
            }

            $firstApproverId = $this->assignFirstApprover((int) $request->module_id);

            if (!$firstApproverId) {
                throw new \Exception("No approver found for this module.");
            }

            $refurbish->update([ 'approver' => $firstApproverId ]);

            request_refurbish::where('id', $request->refurbish_id)->update(['status' => '3']);

            return $this->sendResponse($spares, 'Request refurbish approval saved.');
        });
	}

	public function cancelRefurbish(Request $request)
	{
		try {
			DB::transaction(function () use ($request) {
                // Delete refurbish parent
                request_refurbish::where('id', $request->id)->delete();

                // Delete refurbish details
                refurbish_detail::where('refurbish_id', $request->id)->delete();

                // Delete refurbish processes
                RefurbishProcess::where('refurbish_req_id', $request->id)->delete();
            });

			return $this->sendResponse([], 'Request successfully removed.');
		} catch (\Throwable $th) {
			return $this->sendError($th->errorInfo[2]);
		}
	}

	public function requestRefurbish(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'module_id' => 'required',
            'repo_id'   => 'required',
            'q1'        => 'required',
            'q2'        => 'required',
            'q3'        => 'required',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }

        // Require all 3 files
        if (!$request->hasFile('q1') || !$request->hasFile('q2') || !$request->hasFile('q3')) {
            return $this->sendError('Validation Error.', 'Please upload all 3 Quotations!');
        }

        return DB::transaction(function () use ($request) {
            $folderPath = 'image/Qoutation';
            $directory  = public_path($folderPath);

            if (!File::isDirectory($directory)) {
                File::makeDirectory($directory, 0777, true, true);
            }

            // Process q1–q3 in a loop
            $fileList = [];
            foreach (['q1', 'q2', 'q3'] as $field) {
                if ($request->hasFile($field)) {
                    $file = $request->file($field);
                    $filename = strtoupper(uniqid() . '-' . $file->getClientOriginalName());
                    $file->move($directory, $filename);

                    $fileList[] = [
                        'filename' => $filename,
                        'path'     => $folderPath . '/' . $filename,
                    ];
                }
            }

            $refurbish = request_refurbish::create([
                'repo_id'     => $request->repo_id,
                'branch'      => Auth::user()->branch,
                'maker'       => Auth::id(),
                'files_names' => json_encode($fileList),
            ]);

            $firstApproverId = $this->assignFirstApprover((int) $request->module_id);
            if (!$firstApproverId) {
                throw new \Exception("No approver found for this module.");
            }

            $refurbish->update(['approver' => $firstApproverId]);

            return $this->sendResponse([], 'Request Refurbish Successfully Saved');
        });
    }

    public function updateRefurbish(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'repo_id' => 'required',
            'spares'  => 'required',
            'q1'      => 'required',
            'q2'      => 'required',
            'q3'      => 'required',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }

        return DB::transaction(function () use ($request, $id) {
            $folderPath = 'image/Qoutation';
            $directory  = public_path($folderPath);

            if (!File::isDirectory($directory)) {
                File::makeDirectory($directory, 0777, true, true);
            }

            $fileList = [];
            foreach (['q1', 'q2', 'q3'] as $field) {
                if ($request->hasFile($field)) {
                    $file     = $request->file($field);
                    $filename = strtoupper(uniqid() . '-' . $file->getClientOriginalName());
                    $file->move($directory, $filename);

                    $fileList[] = [
                        'filename' => $filename,
                        'path'     => $folderPath . '/' . $filename,
                    ];
                }
            }

            request_refurbish::where('id', $id)->update([
                'files_names' => json_encode($fileList),
                'status'      => '0',
            ]);

            return $this->sendResponse([], 'Request Refurbish Successfully Updated');
        });
    }

	public function getListForApprovalRefurbish($moduleId)
	{
		try {
            $user = Auth::user();
            $id = $user->id;
            $role = $user->userrole;
            $branch = $user->branch;

            $users = ApprovalMatrixSetting::getApprovalMatrix($moduleId, $role);

            $userRow = collect($users)->first();

            if (! $userRow && $role != 'Warehouse Custodian') {
                return DataTables::of(collect())->make(true);
            }

            $approverRoleId = $userRow->approver_role_id ?? null;
            $approverUsersRaw = $userRow->approver_users ?? '[]';
            $decoded = json_decode($approverUsersRaw, true);
            $userIds = collect($decoded)->pluck('id')->all();
            $currentUserId = Auth::id();

            if (! in_array($currentUserId, $userIds) && $role != 'Warehouse Custodian') {
                return DataTables::of(collect())->make(true);
            }

            $approverIds = is_array($approverRoleId) ? $approverRoleId : [$approverRoleId];

            $query = DB::table('repo_details as repo')
                ->join('recieve_unit_details as rud', 'repo.id', '=', 'rud.repo_id')
                ->join('request_refurbishes as refurbish', function ($join) {
                    $join->on('repo.id', '=', 'refurbish.repo_id')
                        ->on('repo.branch_id', '=', 'refurbish.branch');
                })
                ->leftJoin('branches as br', 'repo.branch_id', '=', 'br.id')
                ->leftJoin('brands as brd', 'repo.brand_id', '=', 'brd.id')
                ->leftJoin('unit_models as mdl', 'repo.model_id', '=', 'mdl.id')
                ->leftJoin('unit_colors as color', 'repo.color_id', '=', 'color.id')
                ->leftJoin('user_role as holder', 'refurbish.approver', '=', 'holder.id')
                ->leftJoin('users as req', 'refurbish.maker', '=', 'req.id')
                ->select(
                    'refurbish.id as refurbish_id',
                    'refurbish.files_names as qoute',
                    'repo.id as repo_id',
                    'repo.model_engine',
                    'repo.model_chassis',
                    'repo.date_sold',
                    'br.name as branchname',
                    'brd.brandname',
                    'mdl.model_name',
                    'color.name as color',
                    DB::raw("
                        CASE refurbish.status
                            WHEN '0' THEN 'WAITING FOR APPROVAL'
                            WHEN '1' THEN 'APPROVED'
                            WHEN '2' THEN 'DISAPPROVED'
                            WHEN '3' THEN 'Proceed to Settle Refurbishment'
                            WHEN '4' THEN 'Done'
                        END AS status
                    "),
                    'refurbish.remarks',
                    DB::raw("holder.user_role_name as current_holder"),
                    DB::raw("CONCAT(req.firstname, ' ', req.middlename, ' ', req.lastname) as requestor")
                )
                ->where('rud.status', '!=', 4)
                ->where('rud.is_sold', 'N');


            if ($role != 'Warehouse Custodian') {
                $query->whereIn('refurbish.approver', $approverIds);
                Log::info('Filter applied for Approver.');
            } else {
                Log::info('Filter applied for Warehouse Custodian. Branch: ' . $branch);
                $query->where('refurbish.branch', $branch);
            }

            return Datatables::of($query)
                ->order(function ($q) {
                    $q->orderByDesc('refurbish.id');
                })
                ->make(true);

        } catch (\Throwable $th) {
            Log::error('Error in getListForApprovalRefurbish: ' . $th->getMessage());
            return $this->sendError('An error occurred while retrieving data.');
        }
	}

	public function listForRefurbishProcess($moduleid)
	{
		try {
            $user = Auth::user();
            $id = $user->id;
            $role = $user->userrole;
            $branch = $user->branch;

            $users = ApprovalMatrixSetting::getApprovalMatrix($moduleid, $role);

            $userRow = collect($users)->first();

            if (! $userRow && $role != 'Warehouse Custodian') {
                return DataTables::of(collect())->make(true);
            }

            $approverRoleId = $userRow->approver_role_id ?? null;
            $approverUsersRaw = $userRow->approver_users ?? '[]';
            $decoded = json_decode($approverUsersRaw, true);
            $userIds = collect($decoded)->pluck('id')->all();
            $currentUserId = Auth::id();

            if (! in_array($currentUserId, $userIds) && $role != 'Warehouse Custodian') {
                return DataTables::of(collect())->make(true);
            }

            $approverIds = is_array($approverRoleId) ? $approverRoleId : [$approverRoleId];

            $cteQuery = $this->cteQuery();

            $stmt = DB::select("
                DECLARE @role NVARCHAR(10) = :role, @userId INT = :userId, @roleId INT = :roleId, @branchId INT = :branchId;
                {$cteQuery}

                SELECT
                    process.id as processid,
                    refurbish.id as refurbish_id,
                    JSON_QUERY(process.files_names) as qoute,
                    repo.id as repo_id,
                    repo.model_engine,
                    repo.model_chassis,
                    repo.date_sold,
                    br.name as branchname,
                    brd.brandname,
                    mdl.model_name,
                    color.name as color,
                    CASE
                        WHEN process.status = '0' THEN 'WAITING FOR APPROVAL'
                        WHEN process.status = '1' THEN 'APPROVED'
                        WHEN process.status = '2' THEN 'DISAPPROVED'
                        ELSE 'Subject For Refurbishing'
                    END as status,
                    process.remarks,
                    holder.user_role_name as current_holder,
                    CONCAT(req.firstname, req.middlename, req.lastname) as requestor,
                    UPPER(CASE
                        WHEN defineClass.class_percent <= 5 THEN 'A'
                        WHEN defineClass.class_percent >= 6 AND defineClass.class_percent <= 10 THEN 'B'
                        WHEN defineClass.class_percent >= 11 AND defineClass.class_percent <= 15 THEN 'C'
                        WHEN defineClass.class_percent >= 16 AND defineClass.class_percent <= 20 THEN 'D'
                        WHEN defineClass.class_percent >= 21 THEN 'E'
                        ELSE '0'
                    END) AS [classification],
                    receive.id as receive_id
                FROM repo_details as repo
                INNER JOIN request_refurbishes as refurbish ON repo.id = refurbish.repo_id AND repo.branch_id = refurbish.branch
                INNER JOIN recieve_unit_details as receive ON repo.id = receive.repo_id
                INNER JOIN branches as br ON repo.branch_id = br.id
                INNER JOIN brands as brd ON repo.brand_id = brd.id
                INNER JOIN unit_models as mdl ON repo.model_id = mdl.id
                INNER JOIN unit_colors as color ON repo.color_id = color.id
                LEFT JOIN refurbish_processes as process ON process.refurbish_req_id = refurbish.id
                LEFT JOIN user_role as holder ON process.approver = holder.id
                LEFT JOIN users as req ON process.maker = req.id
                LEFT JOIN defineClassification defineClass ON repo.id = defineClass.repo_id
                WHERE (
                    (
                        @role != 'Warehouse Custodian' AND process.status = 0 AND process.approver IN (@roleId)
                    )
                    OR
                    (
                        @role = 'Warehouse Custodian' AND refurbish.status = 3 AND refurbish.branch = @branchId
                    )
                )
                ",
                [ 'role' => $role, 'userId' => $user->user_id, 'roleId' => $approverRoleId, 'branchId' => $branch ]
            );
            $datatables = Datatables::of($stmt);

            return $datatables->make(true);
		} catch (\Throwable $th) {
			return $this->sendError($th->errorInfo[2]);
		}
	}

	public function refurbishDecision(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'data_id'        => 'required|numeric',
                'status'    => 'required|numeric', // 1 = Approve, 2 = Disapprove
                'module_id' => 'required|numeric',
                'remarks'   => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors());
            }

            $userId   = Auth::id();
            $roleId   = user_role::where('user_role_name', Auth::user()->userrole)->value('id');
            $moduleId = $request->module_id;
            $recordId = $request->data_id;

            // ✅ check if already approved
            $check = $this->checkIfApproved($moduleId, $recordId, $roleId);
            if ($check['status']) {
                $approverName = $check['name'] ?? 'Unknown Approver';
                return $this->sendError(
                    "This request has already been approved by {$approverName}.",
                    ['approver' => $check['approver']]
                );
            }

            DB::beginTransaction();

            $currentApprover = $this->getCurrentApprover($moduleId, $roleId);
            $nextApproverId = null;

            // ✅ Approval flow
            if ($request->status == 1) {
                $this->logApproval($moduleId, $recordId, $userId, $roleId, $currentApprover->level, 'A');

                $nextApprover = $this->getNextApprover($moduleId, $currentApprover->level);

                if ($nextApprover) {
                    $nextApproverId = $nextApprover->approverId;
                } else {
                    // No more approvers -> mark refurbish as fully approved
                    request_refurbish::where('id', $recordId)
                        ->update(['status' => 3]);

                    refurbish_detail::where('refurbish_id', $recordId)->delete();
                }
            }

            // ✅ Disapproval flow
            if ($request->status == 2) {
                $this->logApproval($moduleId, $recordId, $userId, $roleId, $currentApprover->level, 'D');

                request_refurbish::where('id', $recordId)->update([
                    'status'   => 2,
                    'approver' => $roleId,
                ]);

                refurbish_detail::where('refurbish_id', $recordId)->delete();
            }

            // ✅ Always update approver, remarks, and approval date
            request_refurbish::where('id', $recordId)->update([
                'approver'      => $nextApproverId ?? $roleId,
                'date_approved' => now(),
                'remarks'       => $request->remarks,
            ]);

            DB::commit();

            return $this->sendResponse([],
                $request->status == 1
                    ? 'Request for refurbish successfully approved!'
                    : 'Request for refurbish successfully disapproved!'
            );

        } catch (\Throwable $th) {
            DB::rollBack();
            $this->rollBaclDecision($request->module_id, $request->id, $roleId ?? null);
            return $this->sendError($th->getMessage());
        }
    }

	public function getRefurbishPartsTotalCost($repoid)
	{
		$data = DB::table('request_refurbishes as a')
			->join('refurbish_details as b', 'b.refurbish_id', 'a.id')
			->select('b.*')
			->where('a.repo_id', $repoid)
			->where('a.status', '3')->get();

		$total = 0;

		for ($i = 0; $i < count($data); $i++) {
			$total += $data[$i]->price;
		}

		return $total;
	}

	public function refurbishProcessDecision(Request $request)
	{
        try {
            $validator = Validator::make($request->all(), [
                'data_id'   => 'required|numeric',
                'status'    => 'required|in:1,2', // 1 - Approve, 2 - Disapprove
                'module_id' => 'required|numeric',
                'remarks'   => 'nullable|string',
                'repo_id'   => 'required|numeric',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors());
            }

            $userId   = Auth::id();
            $roleId   = user_role::where('user_role_name', Auth::user()->userrole)->value('id');
            $moduleId = $request->module_id;
            $recordId = $request->data_id;

            $check = $this->checkIfApproved($moduleId, $recordId, $roleId);
            if ($check['status']) {
                $approverName = $check['name'] ?? 'Unknown Approver';
                return $this->sendError(
                    "This refurbish request has already been approved by {$approverName}.",
                    ['approver' => $check['approver']]
                );
            }

            DB::beginTransaction();

            $currentApprover = $this->getCurrentApprover($moduleId, $roleId);
            $nextApproverId  = null;

            $process = RefurbishProcess::find($recordId);

            if (!$process) {
                return $this->sendError('Refurbish process not found.');
            }

            if ($request->status == 1) {
                $this->logApproval($moduleId, $recordId, $userId, $roleId, $currentApprover->level, 'A');

                $nextApprover = $this->getNextApprover($moduleId, $currentApprover->level);

                if ($nextApprover) {
                    $nextApproverId = $nextApprover->approverId;
                } else {
                    // No more approvers, mark refurbish process & request as approved
                    $process->update(['status' => 1]);

                    request_refurbish::where('id', $process->refurbish_req_id)
                        ->update(['status' => 4]); // Completed
                }
            }

            if ($request->status == 2) {
                $this->logApproval($moduleId, $recordId, $userId, $roleId, $currentApprover->level, 'D');

                $process->update([
                    'status'   => 2,
                    'approver' => $roleId,
                ]);

                request_refurbish::where('id', $process->refurbish_req_id)
                    ->update(['status' => 2]); // Disapproved
            }

            $process->update([
                'approver'     => $nextApproverId ?? $roleId,
                'remarks'      => $request->remarks,
                'date_approved'=> now(),
            ]);

            DB::commit();

            return $this->sendResponse([],
                $request->status == 1
                    ? 'Refurbish process successfully approved!'
                    : 'Refurbish process successfully disapproved!'
            );
		} catch (\Throwable $th) {
            DB::rollBack();
            $this->rollBaclDecision($request->module_id, $request->id, Auth::user()->id);
            return $this->sendError($th->getMessage());
        }
	}

	public function refurbishUnitList(Request $request)
	{
		try {
            $statusFilter = $request->input('status');

			$stmt = DB::table('repo_details as repo')
				->join('branches as br', 'repo.branch_id', 'br.id')
				->join('brands as brd', 'repo.brand_id', 'brd.id')
				->join('unit_models as mdl', 'repo.model_id', 'mdl.id')
				->join('unit_colors as color', 'repo.color_id', 'color.id')
				->join('customer_profile as old_owner', 'repo.customer_acumatica_id', 'old_owner.id')
				->join('request_refurbishes as refurbish', 'repo.id', 'refurbish.repo_id')
				->select(
					'refurbish.id',
					'repo.id as repo_id',
					'repo.model_engine',
					'repo.model_chassis',
					'br.name as branchname',
					'brd.brandname',
					'mdl.model_name',
					'color.name as color',
					'old_owner.firstname as o_firstname',
					'old_owner.middlename as o_middlename',
					'old_owner.lastname as o_lastname',
					DB::raw('CONVERT(DATE,refurbish.created_at) AS date_req'),
					DB::raw("
                        CASE
                            WHEN refurbish.status = '0' THEN 'PENDING'
                            WHEN refurbish.status = '1' THEN 'APPROVED'
                            WHEN refurbish.status = '2' THEN 'DISAPPROVED'
                            WHEN refurbish.status = '3' THEN 'ON GOING REFURBISH'
                            WHEN refurbish.status = '4' THEN 'DONE'
                            ELSE 'Unknown'
                        END status
                    "),
                    'refurbish.created_at',
            );

            if (Auth::user()->userrole == 'Warehouse Custodian') {
                $stmt->where('refurbish.branch', Auth::user()->branch);
            }

            if ($statusFilter !== null && $statusFilter !== 'all') {
                $stmt->where('refurbish.status', (int) $statusFilter);
            }

            return Datatables::of($stmt)
                ->order(function ($q) {
                    $q->orderByDesc('refurbish.created_at');
                })
                ->make(true);

		} catch (\Throwable $th) {
			return $this->sendError($th->errorInfo[2]);
		}
	}

    public function settledRefurbishAccounting(){
        try {

            $stmt = DB::select("SELECT
                    branch.name AS branchName,
                    brand.brandname AS brand,
                    model.model_name AS model,
                    color.name AS color,
                    UPPER(repo.model_engine) AS engine,
                    UPPER(repo.model_chassis) AS chassis,
                    UPPER(
                        CONCAT(customer.firstname,
                            CASE
                                WHEN customer.middlename != '' THEN CONCAT(' ', customer.middlename, ' ')
                            ELSE ' ' END, customer.lastname
                        )
                    ) AS exOwner,
                    cost.SettledDate AS SettledDate,
                    received.principal_balance,
                    cost.total_cost_parts
                FROM repo_details repo
                INNER JOIN recieve_unit_details received ON repo.id = received.repo_id
                LEFT JOIN branches branch ON repo.branch_id = branch.id
                LEFT JOIN brands brand ON repo.brand_id = brand.id
                LEFT JOIN unit_models model ON repo.model_id = model.id
                LEFT JOIN unit_colors color ON repo.color_id = color.id
                LEFT JOIN customer_profile customer ON repo.customer_acumatica_id = customer.id
                LEFT JOIN (
                    SELECT
                        request.repo_id,
                        SUM(total_cost) AS total_cost_parts,
                        FORMAT(settle.updated_at, 'MMM dd, yyyy') AS SettledDate
                    FROM request_refurbishes request
                    LEFT JOIN refurbish_processes settle ON request.id = settle.refurbish_req_id
                    LEFT JOIN (
                        SELECT
                            refurb_id, SUM(actual_price) AS total_cost
                        FROM recieve_unit_spare_parts
                        WHERE refurb_id IS NOT NULL
                        GROUP BY refurb_id
                    ) parts ON request.id = parts.refurb_id
                    WHERE settle.status = 1
                    GROUP BY request.repo_id, FORMAT(settle.updated_at, 'MMM dd, yyyy')
                ) cost ON repo.id = cost.repo_id"
            );

            $datatables = Datatables::of($stmt);

            return $datatables->make(true);

        } catch (\Throwable $th) {
			return $this->sendError($th->errorInfo[2]);
		}
    }
}
