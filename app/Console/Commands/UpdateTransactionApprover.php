<?php

namespace App\Console\Commands;


use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\Enums\ApprovableModule;

use App\Models\approval_matrix_setting AS ApprovalMatrixSetting;
use App\Models\receive_unit AS ReceivedUnit;
use App\Models\refurbishProcess;
use App\Models\request_refurbish AS RequestRefurbish;
use App\Models\RequestApproval;
use App\Models\sold_unit AS SoldUnit;
use App\Models\stock_transfer AS StockTransfer;
use App\Models\PhysicalInventoryDoc AS PhysicalInventory;

class UpdateTransactionApprover extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'approval:update-transaction-approver {moduleId}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update approval_activity_logs to use role-based approvers instead of user-based';

    /**
     * @var array<int, array{model: string, reset?: bool}>
     *
     */
    protected array $handlers = [
        ApprovableModule::REPO_TAGGING              => ['model' => ReceivedUnit::class, 'reset' => true],
        ApprovableModule::STOCK_TRANSFER            => ['model' => StockTransfer::class],
        ApprovableModule::REQUEST_PRICE_APPRAISAL   => ['model' => RequestApproval::class],
        ApprovableModule::REQUEST_REFURBISHMENT     => ['model' => RequestRefurbish::class],
        ApprovableModule::SETTLE_REFERBISHMENT      => ['model' => refurbishProcess::class],
        ApprovableModule::SALES_TAGGING             => ['model' => SoldUnit::class],
        ApprovableModule::PHYSICAL_INVENTORY        => ['model' => PhysicalInventory::class],
    ];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $moduleId = (int) $this->argument('moduleId');

        if (!$this->isValidModule($moduleId)) {
            $this->error("Module ID {$moduleId} not found or inactive.");
            return self::FAILURE;
        }

        if (!isset($this->handlers[$moduleId])) {
            $this->warn("No handler implemented for module ID: {$moduleId}");
            Log::warning("No handler defined for module ID: {$moduleId}");
            return self::SUCCESS;
        }

        $handler = $this->handlers[$moduleId];
        $modelClass = $handler['model'];
        $reset = $handler['reset'] ?? false;

        $this->processModuleRecords($moduleId, $modelClass, $reset);

        $this->info("✅ Done processing module ID {$moduleId}.");
        return self::SUCCESS;
    }

    /**
     * Validate if the module exists and is active
     */
    protected function isValidModule(int $moduleId): bool
    {
        return DB::table('system_menu')
            ->whereIn('id', function ($q) {
                $q->select(DB::raw('DISTINCT module_id'))
                  ->from('approval_matrix_settings');
            })
            ->where('status', 1)
            ->whereNotIn('id', [22, 24, 29])
            ->where('id', $moduleId)
            ->exists();
    }

    /**
     * Fetch approver logs grouped by rec_id
     */
    protected function getApproverLogged(int $moduleId, array $ids)
    {
        if (empty($ids)) return collect();

        $idList = implode(',', array_map('intval', $ids));

        $sql = "
            SELECT *, ROW_NUMBER() OVER (PARTITION BY rec_id, user_id ORDER BY id DESC) AS rn
            FROM approval_activity_logs
            WHERE module_id = ? AND decision = 'A' AND rec_id IN ($idList)
            ORDER BY id
        ";

        return collect(DB::select($sql, [$moduleId]))->groupBy('rec_id');
    }

    /**
     * ✅ Get default approver role for module
     */
    protected function getDefaultRole(int $moduleId): ?int
    {
        $matrix = ApprovalMatrixSetting::query()
            ->where('module_id', $moduleId)
            ->orderBy('level', 'asc')
            ->first();

        if (!$matrix) return null;

        $signatories = is_array($matrix->signatories)
            ? $matrix->signatories
            : json_decode($matrix->signatories ?? '[]', true);

        return $signatories[0]['role'] ?? null;
    }

    /**
     * Reusable module processing logic
     */
    protected function processModuleRecords(int $moduleId, string $modelClass, bool $resetStatus = false): void
    {
        $moduleName = ApprovableModule::labels()[$moduleId] ?? "Module {$moduleId}";
        Log::info("=== [{$moduleName}] Process started for module ID {$moduleId} ===");

        // Fetch records
        $records = $modelClass::all(['id', 'approver', 'status']);
        $count = $records->count();
        Log::info("Found {$count} records for {$moduleName}.");

        if ($count === 0) {
            $this->warn("No records found for {$moduleName}.");
            return;
        }

        // Optional: Reset status (e.g., for repo tagging)
        if ($resetStatus) {
            $affected = $modelClass::query()
                ->where('status', '!=', 4)
                ->update(['status' => 1]);
            Log::info("Reset status to 1 for {$affected} records (excluding status = 4).");
        }

        $ids = $records->pluck('id')->all();
        $logsByRecId = $this->getApproverLogged($moduleId, $ids);
        $defaultRole = $this->getDefaultRole($moduleId);

        if (!$defaultRole) {
            $this->warn("No approval matrix found for module ID {$moduleId}");
            Log::warning("No approval matrix found for module ID {$moduleId}");
        }

        foreach ($records as $record) {
            $logsForRecord = $logsByRecId->get($record->id);

            if ($logsForRecord && $logsForRecord->isNotEmpty()) {
                $lastLog = $logsForRecord->sortByDesc('id')->first();

                // Optional: only update active status (1)
                if ($resetStatus || $record->status == 1) {
                    $record->update(['approver' => $lastLog->user_id]);
                    Log::info("Record {$record->id}: approver updated to {$lastLog->user_id} (log ID {$lastLog->id}).");
                    continue;
                }
            }

            if ($defaultRole) {
                $record->update(['approver' => $defaultRole]);
                Log::info("Record {$record->id}: default approver role {$defaultRole} applied.");
            } else {
                Log::warning("Record {$record->id}: No log found and no default role available.");
            }
        }

        Log::info("=== [{$moduleName}] Process completed for module ID {$moduleId} ===");
    }
}
