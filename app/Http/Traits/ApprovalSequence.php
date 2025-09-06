<?php

namespace App\Http\Traits;

use App\Models\approval_activity_log AS ApprovalActivityLog;
use App\Models\approval_matrix_setting AS ApprovalMatrixSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait ApprovalSequence
{
    /**
     * Get the full approver sequence for a module
     */
    public function getApprovers(int $moduleId)
    {
        return ApprovalMatrixSetting::forApprover()
            ->where('module_id', $moduleId)
            ->orderBy('id', 'asc')   // assuming "sequence" column exists
            ->get();
    }

    /**
     * Get the first approver of a module
     */
    public function getFirstApprover(int $moduleId)
    {
        return $this->getApprovers($moduleId)->first();
    }


    /**
     * Get the current approver of a module
     */
    public function getCurrentApprover(int $moduleId, int $roleId)
    {
        return ApprovalMatrixSetting::forApprover()
            ->where('module_id', $moduleId)
            ->whereRaw("JSON_VALUE(signatories, '$[0].user') = ?", [$roleId])
            ->orderBy('id', 'asc')
            ->first();
    }

    /**
     * Get the next approver in sequence
     */
    public function getNextApprover(int $moduleId, int $currentLevel)
    {
        return ApprovalMatrixSetting::forApprover()
            ->where('module_id', $moduleId)
            ->where('level', '>', function ($query) use ($moduleId, $currentLevel) {
                $query->select('level')
                    ->from('approval_matrix_settings')
                    ->where('module_id', $moduleId)
                    ->where('level', $currentLevel)
                    ->limit(1);
            })
            ->orderBy('level', 'asc')
            ->first();
    }

    /**
     * Create a new approval log entry
     */
    public function logApproval(int $moduleId, int $recordId, int $userId, int $roleId, int $sequence, ?string $status)
    {
        return ApprovalActivityLog::create([
            'module_id'   => $moduleId,
            'rec_id'      => $recordId,
            'user_id'     => $roleId,
            'order'       => $sequence,
            'decision'    => $status,
            'approved_by' => $userId,
        ]);
    }

    /**
     * Assign the first approver to a record
     */
    public function assignFirstApprover(int $moduleId): ?int
    {
        $approver = $this->getFirstApprover($moduleId);

        if (!$approver) {
            Log::warning("No approvers found for module {$moduleId}");
            return null;
        }

        return $approver->approverId;
    }
}
