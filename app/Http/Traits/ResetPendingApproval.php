<?php

namespace App\Http\Traits;

use App\Enums\ApprovableModule;
use App\Models\approval_activity_log AS ApprovalActivityLog;
use App\Models\approval_matrix_setting AS ApprovalMatrixSetting;
use App\Models\receive_unit AS ReceiveUnit;
use App\Models\received_part AS ReceivedSparePart;
use App\Models\refurbishProcess AS RefurbishProcess;
use App\Models\repo;
use App\Models\request_refurbish AS RequestRefurbish;
use App\Models\RequestApproval;
use App\Models\stock_transfer AS StockTransfer;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait ResetPendingApproval
{
    public function resetApproval($moduleId)
    {
        $check = ApprovalMatrixSetting::where('module_id', $moduleId)->firstOrFail();

        switch ($check->module_id) {
            case ApprovableModule::STOCK_TRANSFER:
                return $this->resetStockTransferApproval();

            case ApprovableModule::REPO_TAGGING:
                return $this->resetRepoTaggingApproval();

            case ApprovableModule::REQUEST_PRICE_APPRAISAL:
                return $this->resetPriceAppriasalApproval();

            case ApprovableModule::REQUEST_REFURBISHMENT:
                return $this->resetRefurbishApproval();

            case ApprovableModule::SETTLE_REFERBISHMENT:
                return $this->resetSettleRefurbishApproval();

            default:
                Log::error("Unsupported module ID: {$moduleId}");
        }

        return response()->json([
            'success' => false,
            'message' => 'Unsupported module ID.',
        ], 400);
    }

    private function getApprovalMatrix(int $moduleId)
    {
        return ApprovalMatrixSetting::forApprover()
            ->where('module_id', $moduleId)
            ->orderBy('level', 'asc')
            ->get();
    }

    private function getFirstApprover(int $moduleId)
    {
        return $this->getApprovalMatrix($moduleId)->first();
    }

    private function removeApprovalLogs(int $moduleId, array $recordIds)
    {
        ApprovalActivityLog::query()
        ->where('module_id', $moduleId)
        ->whereIn('rec_id', $recordIds)
        ->delete();
    }

    public function resetStockTransferApproval()
    {
        $records = StockTransfer::where('status', 0)->get();

        if ($records->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No pending stock transfer approvals found.',
            ], 404);
        }

        $approver = $this->getFirstApprover(ApprovableModule::STOCK_TRANSFER);

        if (!$approver) {
            return response()->json([
                'success' => false,
                'message' => 'No approvers found for this module.',
            ], 404);
        }

        DB::transaction(function () use ($records, $approver) {
            $recordIds = [];

            foreach ($records as $record) {
                $record->approver = $approver->approverId;
                $record->date_approved = null;
                $record->remarks = null;
                $record->reason_for_transfer = null;
                $record->save();

                $recordIds[] = $record->id;

                Log::info("Updated Stock Transfer ID {$record->id} with approver {$approver->approverId}");
            }

            // Remove logs only after updating approvers
            $this->removeApprovalLogs(ApprovableModule::STOCK_TRANSFER, $recordIds);
        });

        return response()->json([
            'success' => true,
            'message' => 'Pending stock transfer approvals have been reset.',
        ], 200);
    }

    public function resetRepoTaggingApproval()
    {
        $records = repo::where('status', 4)->get();

        if ($records->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No pending repo details approvals found.',
            ], 404);
        }

        $approver = $this->getFirstApprover(ApprovableModule::REPO_TAGGING);

         if (!$approver) {
            return response()->json([
                'success' => false,
                'message' => 'No approvers found for this module.',
            ], 404);
        }

        DB::transaction(function () use ($records, $approver) {
            $recordIds = [];

            foreach ($records as $record) {
                $record->approver = $approver->approverId;
                $record->date_approved = null;
                $record->save();

                $recordIds[] = $record->id;

                Log::info("Updated Repo Details ID {$record->id} with approver {$approver->approverId}");
            }

            // Remove logs only after updating approvers
            $this->removeApprovalLogs(ApprovableModule::REPO_TAGGING, $recordIds);
        });

        return response()->json([
            'success' => true,
            'message' => 'Pending stock transfer approvals have been reset.',
        ], 200);
    }

    public function resetPriceAppriasalApproval()
    {
        $records = RequestApproval::where('status', 0)->get();

        if ($records->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No pending price appriasal approvals found.',
            ], 404);
        }

        $approver = $this->getFirstApprover(ApprovableModule::REQUEST_PRICE_APPRAISAL);

         if (!$approver) {
            return response()->json([
                'success' => false,
                'message' => 'No approvers found for this module.',
            ], 404);
        }

        DB::transaction(function () use ($records, $approver) {
            $recordIds = [];

            foreach ($records as $record) {
                $record->approver = $approver->approverId;
                $record->date_approved = null;
                $record->save();

                $recordIds[] = $record->id;

                Log::info("Updated Price Appriasal ID {$record->id} with approver {$approver->approverId}");
            }

            // Remove logs only after updating approvers
            $this->removeApprovalLogs(ApprovableModule::REQUEST_PRICE_APPRAISAL, $recordIds);
        });

        return response()->json([
            'success' => true,
            'message' => 'Pending price appraisal approvals have been reset.',
        ], 200);
    }

    public function resetRefurbishApproval()
    {
        $records = RequestRefurbish::where('status', 0)->get();

        if ($records->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No pending refurbishment approvals found.',
            ], 404);
        }

        $approver = $this->getFirstApprover(ApprovableModule::REQUEST_REFURBISHMENT);

         if (!$approver) {
            return response()->json([
                'success' => false,
                'message' => 'No approvers found for this module.',
            ], 404);
        }

        DB::transaction(function () use ($records, $approver) {
            $recordIds = [];

            foreach ($records as $record) {
                $record->approver = $approver->approverId;
                $record->date_approved = null;
                $record->remarks = null;
                $record->save();

                $recordIds[] = $record->id;

                Log::info("Updated Refurbishment ID {$record->id} with approver {$approver->approverId}");
            }

            // Remove logs only after updating approvers
            $this->removeApprovalLogs(ApprovableModule::REQUEST_REFURBISHMENT, $recordIds);
        });

        return response()->json([
            'success' => true,
            'message' => 'Pending refurbishment approvals have been reset.',
        ], 200);
    }

    public function resetSettleRefurbishApproval()
    {
        $records = RefurbishProcess::where('status', 0)->get();

        if ($records->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No pending refurbishment approvals found.',
            ], 404);
        }

        $approver = $this->getFirstApprover(ApprovableModule::SETTLE_REFERBISHMENT);

        if (!$approver) {
            return response()->json([
                'success' => false,
                'message' => 'No approvers found for this module.',
            ], 404);
        }

        DB::transaction(function () use ($records, $approver) {
            $recordIds = [];

            foreach ($records as $record) {
                $record->approver = $approver->approverId;
                $record->remarks = null;
                $record->save();

                $recordIds[] = $record->id;

                Log::info("Updated Refurbishment ID {$record->id} with approver {$approver->approverId}");
            }

            // Remove logs only after updating approvers
            $this->removeApprovalLogs(ApprovableModule::SETTLE_REFERBISHMENT, $recordIds);
        });

        return response()->json([
            'success' => true,
            'message' => 'Pending refurbishment approvals have been reset.',
        ], 200);
    }

}
