<?php

namespace App\Http\Traits;

use App\Enums\ApprovableModule;
use App\Models\approval_activity_log AS ApprovalActivityLog;
use App\Models\approval_matrix_setting AS ApprovalMatrixSetting;
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
            // you can add more modules here later
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
        $stocks = StockTransfer::where('status', 0)->get();

        if ($stocks->isEmpty()) {
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

        DB::transaction(function () use ($stocks, $approver) {
            $recordIds = [];

            foreach ($stocks as $record) {
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
}
