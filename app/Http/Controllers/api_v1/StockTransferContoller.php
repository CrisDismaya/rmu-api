<?php

namespace App\Http\Controllers\api_v1;

use App\Http\Controllers\api_v1\BaseController as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\branch;
use App\Models\repo;
use App\Models\receive_unit;
use App\Models\FilesUploaded;
use App\Models\stock_transfer;
use App\Models\stock_transfer_units;
use App\Models\user_role;
use App\Models\approval_matrix_setting;
use App\Http\Traits\helper;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Log;
use App\Http\Traits\TransactionNumberGenerator;
use App\Http\Traits\ApprovalSequence;

class StockTransferContoller extends BaseController
{
	//
	use helper;
    use TransactionNumberGenerator, ApprovalSequence;

	public function branchesList()
	{
		try {
			return DB::select(
				"SELECT DISTINCT
					brh.id,
					brh.name
				FROM branches AS brh
				INNER JOIN users AS usr ON brh.id = usr.branch
				WHERE brh.status = 1
				AND usr.userrole = 'Warehouse Custodian'
				AND brh.id NOT IN (:branchid)",
				['branchid' => Auth::user()->branch]
			);
		} catch (\Throwable $th) {
			return $this->sendError($th->errorInfo[2]);
		}
	}

	public function ModelList()
	{
		try {
			return DB::select(
				"SELECT
						rud.id, brd.brandname, rep.model_engine, rep.model_chassis, clr.name AS color_name,
						rep.plate_number, UPPER(mdl.model_name) AS model_name
					FROM repo_details rep
					INNER JOIN recieve_unit_details rud ON rep.id = rud.repo_id AND rep.branch_id = rud.branch
					LEFT JOIN brands AS brd ON rep.brand_id = brd.id
					LEFT JOIN unit_models AS mdl ON rep.model_id = mdl.id
					LEFT JOIN unit_colors AS clr ON rep.color_id = clr.id
					LEFT JOIN (
						SELECT
							sub.approvalid, sub.recievedid, sta1.status AS approvalstatus,
							CASE WHEN sta1.status = 1 THEN sta1.to_branch WHEN sta1.status = 2 THEN sta1.from_branch END AS current_branch
						FROM (
							SELECT MAX(sta.id) AS approvalid, MAX(stu.recieved_unit_id) AS recievedid
							FROM stock_transfer_approval sta
							INNER JOIN stock_transfer_unit stu ON sta.id = stu.stock_transfer_id
							GROUP BY stu.recieved_unit_id
						) sub
						INNER JOIN stock_transfer_approval sta1 ON sub.approvalid = sta1.id
					) app ON rud.id = app.recievedid
					LEFT JOIN sold_units sld ON rep.id = sld.repo_id AND rep.branch_id = sld.branch
					LEFT JOIN request_refurbishes ref ON rep.id = ref.repo_id AND rep.branch_id = ref.branch
					LEFT JOIN (
						SELECT
							repo.id AS repo_id, COUNT(upload.id) AS total_upload_required_files
						FROM repo_details repo
						LEFT JOIN files_uploaded upload ON repo.id = upload.reference_id AND repo.branch_id = upload.branch_id
						INNER JOIN (
							SELECT * FROM files WHERE isRequired = 1 AND status = 1
						) files ON upload.files_id = files.id
						WHERE upload.is_deleted = 0
						GROUP BY repo.id, upload.branch_id
					) files ON files.repo_id = rep.id
					WHERE rud.status NOT IN (4) AND UPPER(rud.is_sold) = 'N' AND rep.branch_id = ?
					AND (app.current_branch IS NULL OR app.current_branch = rep.branch_id)
					AND (app.approvalstatus IS NULL OR app.approvalstatus IN (1, 2))
					AND (sld.id IS NULL OR sld.status IN (2))
					AND (ref.id IS NULL OR ref.status IN (2, 3, 4))
					AND ISNULL(files.total_upload_required_files, 0) = (SELECT COUNT(*) FROM files WHERE isRequired = 1 AND status = 1)
                    ORDER BY rep.created_at DESC
				",
				array(Auth::user()->branch)
			);
		} catch (\Throwable $th) {
			return $this->sendError($th->errorInfo[2]);
		}
	}

	public function getAllForApprovals(Request $request, $moduleid)
    {
        try {
            $user = Auth::user();
            $id = $user->id;
            $role = $user->userrole;
            $branch = $user->branch;

            $query = DB::table('stock_transfer_approval as sta')
                ->leftJoin('users as usr', 'sta.created_by', '=', 'usr.id')
                ->select(
                    'sta.id',
                    'sta.reference_code',
                    DB::raw("(SELECT NAME FROM branches WHERE id = sta.from_branch) AS from_branch"),
                    DB::raw("(SELECT NAME FROM branches WHERE id = sta.to_branch) AS to_branch"),
                    'sta.approver AS approver_id',
                    DB::raw("(SELECT user_role_name FROM user_role WHERE id = sta.approver) AS approver_name"),
                    DB::raw("CONCAT(usr.firstname,' ',usr.lastname) AS created_by"),
                    DB::raw("(SELECT COUNT(recieved_unit_id) FROM stock_transfer_unit WHERE stock_transfer_id = sta.id) AS transfer_units_count"),
                    DB::raw("CASE WHEN sta.remarks IS NULL THEN '' ELSE sta.remarks END AS remarks"),
                    'usr.userrole',
                    'sta.status AS status_id',
                    DB::raw("CASE WHEN sta.status = '0' THEN 'Pending' WHEN sta.status = '1' THEN 'Approved' WHEN sta.status = '2' THEN 'Disapproved' ELSE '' END AS approval_status")
                );

            if ($role == 'Verifier' || $role == 'General Manager') {
                $query->where(function ($q) use ($id) {
                    $q->where('sta.approver', function ($subQuery) use ($id) {
                        $subQuery->select('roles.id')
                            ->from('users')
                            ->join('user_role as roles', 'users.userrole', '=', 'roles.user_role_name')
                            ->where('users.id', $id)
                            ->limit(1);
                    });
                });
            } elseif ($role == 'Warehouse Custodian') {
                $query->where('sta.from_branch', $branch);
            }

            return DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    if ($search = $request->get('search')['value']) {
                        $query->where(function ($q) use ($search) {
                            $q->orWhere('sta.reference_code', 'like', "%{$search}%")
                            ->orWhere(DB::raw("CONCAT(usr.firstname,' ',usr.lastname)"), 'like', "%{$search}%");
                        });
                    }
                })
                ->order(function ($q) {
                    $q->orderByDesc('sta.created_at');
                })
                ->make(true);

        } catch (\Throwable $th) {
            return $this->sendError($th->getMessage());
        }
    }

	function getTransferUnits($id)
	{
		try {
			return DB::select(
				"SELECT
					rud.id, brd.brandname, rep.model_engine, rep.model_chassis, clr.name AS color_name,
					rep.plate_number, UPPER(mdl.model_name) AS model_name,
					rep.date_sold, lst.date_surrender, DATEDIFF(day, rep.date_sold, lst.date_surrender) AS aging_unit_days,
					rep.id AS repo_id
				FROM stock_transfer_approval sta
				INNER JOIN stock_transfer_unit stu ON sta.id = stu.stock_transfer_id
				INNER JOIN recieve_unit_details rud ON stu.recieved_unit_id = rud.id
				INNER JOIN repo_details AS rep ON rud.repo_id = rep.id
				LEFT JOIN (
					SELECT
						MAX(brand_id) AS brand_id, MAX(model_id) AS model_id, MAX(date_surrender) AS date_surrender
					FROM repo_details
					GROUP BY brand_id, model_id
				) lst ON rep.brand_id = lst.brand_id AND rep.model_id = lst.model_id
				LEFT JOIN brands AS brd ON rep.brand_id = brd.id
				LEFT JOIN unit_models AS mdl ON rep.model_id = mdl.id
				LEFT JOIN unit_colors AS clr ON rep.color_id = clr.id
				WHERE sta.id = ?",
				array($id)
			);
		} catch (\Throwable $th) {
			return $this->sendError($th->errorInfo[2]);
		}
	}

	public function createStockTransfer(Request $request)
	{
        $validator = Validator::make($request->all(), [
            'module_id'           => 'required',
            'transfer_to_branch'  => 'required|numeric',
            'list_of_transfer'    => 'required|json',
            'reason_for_transfer' => 'required|string'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }

        return DB::transaction(function () use ($request) {
            $stockTransfer = stock_transfer::create([
                'from_branch'         => Auth::user()->branch,
                'to_branch'           => $request->transfer_to_branch,
                'created_by'          => Auth::id(),
                'reason_for_transfer' => $request->reason_for_transfer,
            ]);

            $transactionNumber = $this->generateTransactionNumber(
                'stock_transfer',
                null,
                $stockTransfer->created_at
            );

            $stockTransfer->update(['reference_code' => $transactionNumber]);

            $units = json_decode($request->list_of_transfer, true);
            foreach ($units as $unitId) {
                stock_transfer_units::create([
                    'stock_transfer_id' => $stockTransfer->id,
                    'recieved_unit_id'  => $unitId
                ]);
            }

            $firstApproverId = $this->assignFirstApprover((int) $request->module_id);

            if (!$firstApproverId) {
                throw new \Exception("No approver found for this module.");
            }

            $stockTransfer->update([ 'approver' => $firstApproverId ]);

            return $this->sendResponse([], 'Stock Transfer Successfully Saved');
        });
	}

	public function submitApproverDecision(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id'        => 'required|numeric',
                'status'    => 'required|numeric',
                'module_id' => 'required|numeric',
                'remarks'   => 'nullable'
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors());
            }

            $userId   = Auth::id();
            $roleId   = user_role::where('user_role_name', Auth::user()->userrole)->value('id');
            $moduleId = $request->module_id;
            $recordId = $request->id;

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
                    // no more approvers, mark record as fully approved
                    stock_transfer::where('id', $recordId)
                        ->update(['status' => 1]);

                    $units = stock_transfer_units::where('stock_transfer_id', $recordId)->get();
                    $startCount = $this->forInventoryOutCount();

                    foreach ($units as $index => $unit) {
                        $rowNumber = $startCount + $index;
                        $transactionNumber = $this->generateTransactionNumber('inventory_out', $rowNumber, now());

                        $unit->update([
                            'transaction_number_inventory_out' => $transactionNumber,
                            'inventory_out_at' => now(),
                        ]);
                    }
                }
            }

            if ($request->status == 2) {
                $this->logApproval($moduleId, $recordId, $userId, $roleId, 1, null);

                stock_transfer::where('id', $recordId)->update([
                    'status'   => 2,
                    'approver' => $roleId
                ]);

                stock_transfer_units::where('stock_transfer_id', $recordId)
                    ->update(['is_received' => 9, 'is_use_old_files' => 9]);
            }

            stock_transfer::where('id', $recordId)->update([
                'approver'      => $nextApproverId ?? $roleId,
                'date_approved' => now(),
                'remarks'       => $request->remarks,
            ]);

            DB::commit();

            return $this->sendResponse([],
                $request->status == 1
                    ? 'Request successfully approved!'
                    : 'Request successfully disapproved!'
            );

        } catch (\Throwable $th) {
            DB::rollBack();
            $this->rollBaclDecision($request->module_id, $request->id, $roleId);
            return $this->sendError($th->getMessage());
        }
    }

	public function getAllReceiveStockTransfer(Request $request)
    {
        try {
            $query = DB::table('stock_transfer_approval as sta')
                ->join('stock_transfer_unit as stu', 'sta.id', '=', 'stu.stock_transfer_id')
                ->join('recieve_unit_details as rud', 'stu.recieved_unit_id', '=', 'rud.id')
                ->join('repo_details as rep', 'rud.repo_id', '=', 'rep.id')
                ->leftJoin('customer_profile as cus', 'rep.customer_acumatica_id', '=', 'cus.id')
                ->leftJoin('branches as brh', 'sta.from_branch', '=', 'brh.id')
                ->leftJoin('brands as brd', 'rep.brand_id', '=', 'brd.id')
                ->leftJoin('unit_models as mdl', 'rep.model_id', '=', 'mdl.id')
                ->select([
                    DB::raw("REPLACE(sta.reference_code, 'ST', 'RT') AS reference_code"),
                    'brh.name as branch_name',
                    DB::raw("CONCAT(cus.firstname, ' ', cus.lastname) AS customer_name"),
                    'brd.brandname',
                    'mdl.model_name',
                    DB::raw("UPPER(rep.model_engine) AS engine"),
                    DB::raw("UPPER(rep.model_chassis) AS chassis"),
                    'rep.id as repo_id',
                    'sta.id as stock_approval_id',
                    'stu.id as stock_unit_id',
                    'stu.is_received',
                    'stu.is_use_old_files',
                    DB::raw("
                        CASE
                            WHEN stu.is_received = 0 AND stu.is_use_old_files = 0 THEN 'NO DECISION'
                            ELSE 'WITH DECISION'
                        END AS received_status
                    "),
                ])
                ->where('sta.status', 1)
                ->where('stu.is_received', 0)
                ->where('sta.to_branch', Auth::user()->branch);

            return DataTables::of($query)
                ->filter(function ($query) use ($request) {
                    if ($search = $request->get('search')['value']) {
                        $query->where(function ($q) use ($search) {
                            $q->orWhere('sta.reference_code', 'like', "%{$search}%")
                            ->orWhere(DB::raw("CONCAT(cus.firstname,' ',cus.lastname)"), 'like', "%{$search}%")
                            ->orWhere('rep.model_engine', 'like', "%{$search}%")
                            ->orWhere('rep.model_chassis', 'like', "%{$search}%")
                            ;
                        });
                    }
                })
                ->order(function ($q) {
                    $q->orderByDesc('sta.created_at');
                })
                ->make(true);

        } catch (\Throwable $th) {
            return $this->sendError($th->getMessage());
        }
    }

	function getAllFileUploaded(Request $request)
	{
		try {
			$response = [];
			$files_info = DB::select(
				"SELECT * FROM (
					SELECT
						MIN(id) AS min_id, MAX(id) AS max_id, branch_id, reference_id AS repo_id, CAST(created_at AS DATE) AS dates
					FROM files_uploaded
					GROUP BY branch_id, reference_id, CAST(created_at AS DATE)
				) AS sub
				WHERE sub.repo_id = :repoid
				ORDER BY min_id DESC",
				['repoid' => $request->repoid]
			);

			foreach ($files_info as $item) {
				$images = DB::select(
					"SELECT * FROM files_uploaded WHERE id >= :min_id AND id <= :max_id",
					['min_id' => $item->min_id, 'max_id' => $item->max_id]
				);
				array_push($response, $images);
			}
			return $response;
		} catch (\Throwable $th) {
			return $this->sendError($th->errorInfo[2]);
		}
	}

	function receivedDesicion(Request $request)
	{
		try {
			$repo = repo::where('id', '=', $request->repoid)->first();
			$receive = receive_unit::where('repo_id', '=', $repo->id)->where('branch', '=', $repo->branch_id)->first();
			$pictures = FilesUploaded::where('reference_id', '=', $repo->id)->where('is_deleted', '=', 0)->get();

			DB::beginTransaction();

			stock_transfer_units::where('id', '=', $request->unitid)->update([
                'is_received' => '1',
                'is_use_old_files' => $request->decisionid,
                'trans_no_received' => $this->generateTransactionNumber('receive_transfer', null, now()),
                'received_at' => Carbon::now(),
            ]);
			repo::where('id', '=', $repo->id)->update(['branch_id' => Auth::user()->branch, 'transfer_branch_id' => $request->unitid]);
			receive_unit::where('id', '=', $receive->id)->update(['branch' => Auth::user()->branch, 'status' => '0']);

			// 1 = Use Previous Images / 2 = Upload New Images
			foreach ($pictures as $pics) {
				if ($request->decisionid == 1) {
					$format = [
						'module_id' => $pics['module_id'],
						'branch_id' => Auth::user()->branch,
						'reference_id' => $pics['reference_id'],
						'files_id' => $pics['files_id'],
						'files_name' => $pics['files_name'],
						'path' => $pics['path'],
					];
					FilesUploaded::create($format);
				}
				FilesUploaded::where('id', '=', $pics['id'])->update(['is_deleted' => 1]);
			}

			DB::commit();

			return $this->sendResponse([], 'Received Unit for Stock Transfer Successfully');
		} catch (\Throwable $th) {
			return $this->sendError($th->errorInfo[2]);
		}
	}

	function getTransferredUnits()
	{
		try {
		    $stmt = DB::select(
				"WITH
				receives AS (
					SELECT
						ROW_NUMBER() OVER ( PARTITION BY rud.repo_id ORDER BY rud.repo_id ) AS row_num,
						rud.id AS origin_id
					FROM recieve_unit_details rud
					LEFT JOIN stock_transfer_unit stu ON rud.id = stu.recieved_unit_id AND stu.is_received = 1
				)

				SELECT reps.*, ruds.id AS origin,
					compareid_to = (SELECT origin_id FROM receives rev WHERE rev.row_num = revs.row_num - 1)
				FROM receives revs
				INNER JOIN recieve_unit_details ruds ON revs.origin_id = ruds.id
				LEFT JOIN (
					SELECT
						rep.id AS reps_id, cus.acumatica_id, UPPER(CONCAT(cus.firstname,' ',cus.middlename,' ',cus.lastname)) AS customer_name,
						brd.brandname, UPPER(mdl.model_name) AS model_name, rep.model_engine, rep.model_chassis, clr.name AS color_name, rep.plate_number
					FROM repo_details rep
					LEFT JOIN customer_profile cus ON rep.customer_acumatica_id = cus.id
					LEFT JOIN brands brd ON rep.brand_id = brd.id
					LEFT JOIN unit_models mdl ON rep.model_id = mdl.id
					LEFT JOIN unit_colors clr ON rep.color_id = clr.id
				) reps ON ruds.repo_id = reps.reps_id
				INNER JOIN stock_transfer_unit stus ON ruds.id = stus.recieved_unit_id
				INNER JOIN stock_transfer_approval stas ON stus.stock_transfer_id = stas.id
				WHERE stus.is_received = 1 AND stas.to_branch = ?",
				array(Auth::user()->branch)
			);

            $datatables = Datatables::of($stmt);
            return $datatables->make(true);
		} catch (\Throwable $th) {
			return $this->sendError($th->errorInfo[2]);
		}
	}

	function getComparisionSpareParts(Request $request)
	{
		try {
			$new = $this->get_comparison_list($request->origin);
			$old = $this->get_comparison_list($request->compare_to);

			return $this->sendResponse(['new_list' => $new, 'old_list' => $old], '');
		} catch (\Throwable $th) {
			return $this->sendError($th->errorInfo[2]);
		}
	}

	function get_comparison_list($id)
	{
		try {
			return DB::select(
				"SELECT
					rups.*, prts.inventory_code, prts.name
				FROM recieve_unit_details ruds
				INNER JOIN recieve_unit_spare_parts rups ON ruds.id = rups.recieve_id AND rups.is_deleted = 0
				LEFT JOIN spare_parts prts ON rups.parts_id = prts.id
				WHERE ruds.id = ?",
				array($id)
			);
		} catch (\Throwable $th) {
			return $this->sendError($th->errorInfo[2]);
		}
	}

	function fetch_stock_transfer_approved()
	{
		try {
			$query = DB::table('stock_transfer_approval as sta')
				->join('stock_transfer_unit as stu', 'sta.id', '=', 'stu.stock_transfer_id')
				->leftJoin('recieve_unit_details as rud', 'stu.recieved_unit_id', '=', 'rud.id')
				->leftJoin('repo_details as rep', 'rud.repo_id', '=', 'rep.id')
				->leftJoin('brands as brd', 'rep.brand_id', '=', 'brd.id')
				->leftJoin('unit_models as mdl', 'rep.model_id', '=', 'mdl.id')
				->leftJoin('unit_colors as clr', 'rep.color_id', '=', 'clr.id')
				->select([
					'sta.reference_code', 'rep.id As repo_id',
					'brd.brandname',
					'mdl.model_name AS model',
					DB::raw('origin = (SELECT name FROM branches WHERE id = sta.from_branch)'),
					DB::raw('receiver = (SELECT name FROM branches WHERE id = sta.to_branch)'),
					DB::raw('UPPER(rep.model_engine) AS engine'),
					DB::raw('UPPER(rep.model_chassis) AS chassis'),
					DB::raw('UPPER(rep.plate_number) AS plate'),
					'clr.name as color',
					DB::raw('sta.date_approved'),
					DB::raw("CASE WHEN sta.status = '0' THEN 'Pending' WHEN sta.status = '1' THEN 'Approved' WHEN sta.status = '2' THEN 'Disapproved' ELSE '' END AS approval_status")
				]);

			if (Auth::user()->userrole == 'Warehouse Custodian') {
				$list = $query->where('sta.from_branch', '=', Auth::user()->branch)->get();
			} else {
				$list = $query->get();
			}

			return $list;
		} catch (\Throwable $th) {
			return $this->sendError($th->errorInfo[2]);
		}
	}
}
