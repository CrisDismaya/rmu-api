<?php

namespace App\Console\Commands;

use App\Models\approval_activity_log;
use App\Models\receive_unit;
use App\Models\refurbishProcess;
use App\Models\request_refurbish;
use App\Models\RequestApproval;
use App\Models\sold_unit;
use App\Models\stock_transfer;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $moduleId = (int) $this->argument('moduleId');

        // menu have approval
        $modules = DB::table('system_menu')
            ->whereIn('id', function ($query) {
                $query->select(DB::raw('DISTINCT module_id'))
                    ->from('approval_matrix_settings');
            })
            ->where('status', 1)
            ->whereNotIn('id', [22, 24, 29])
            ->get();

        $validModule = $modules->firstWhere('id', $moduleId);

        if (!$validModule) {
            $this->error("Module ID {$moduleId} not found in approval_matrix_settings or is inactive.");
            return Command::FAILURE;
        }

        $this->info("Processing module: {$validModule->menu_name} (ID: {$validModule->id})");

        // Call specific handler
        switch ($moduleId) {
            case 25:
                $this->handleRepoTagging();
                break;
            case 5:
                $this->handleStockTransfer();
                break;
            case 6:
                $this->handleRequestPriceAppraisal();
                break;
            case 23:
                $this->handleRequestRefurbish();
                break;
            case 26:
                $this->handleSettleRefurbish();
                break;
            case 19:
                $this->handleSoldUnit();
                break;
            default:
                Log::warning("No handler defined for module ID: {$moduleId} - {$validModule->menu_name}");
                $this->warn("No handler implemented for module ID: {$moduleId}");
                break;
        }

        $this->info("Done.");
        return Command::SUCCESS;
    }

    public function getApproverRole($ids)
    {
        return DB::table('users')
            ->select(
                DB::raw("users.id AS user_id"),
                DB::raw("roles.id AS role_id")
            )
            ->join('user_role as roles', 'users.userrole', 'roles.user_role_name')
            ->whereIn('users.id', $ids)
            ->get();
    }

    public function handleRepoTagging()
    {
        $repos = receive_unit::all();

        if ($repos->isEmpty()) {
            $this->warn("No records found for Repo Tagging.");
            return;
        }

        $ids = $repos->pluck('approver')->unique();
        $approvers = $this->getApproverRole($ids)->keyBy('user_id');

        foreach ($repos as $record) {
            $approver = $approvers->get($record->approver);

            if ($approver) {
                $record->approver = $approver->role_id;
                $record->save();
                $this->info("Updated approver for record ID: {$record->id} => Role ID: {$approver->role_id}");
            } else {
                $this->warn("No approver found for Repo Tagging ID: {$record->id}");
            }
        }
    }

    public function handleStockTransfer()
    {
        $transfers = stock_transfer::all();

        if ($transfers->isEmpty()) {
            $this->warn("No records found for Stock Transfers.");
            return;
        }

        $ids = $transfers->pluck('approver');
        $approvers = $this->getApproverRole($ids)->keyBy('user_id');

        foreach ($transfers as $record) {
            $approver = $approvers->get($record->approver);

            if ($approver) {
                $record->approver = $approver->role_id;
                $record->save();

                $this->info("Updated Stock Transfer ID {$record->id} with approver {$approver->role_id}");
            } else {
                $this->warn("No approver found for Stock Transfer ID {$record->id}");
            }
        }
    }

    public function handleRequestPriceAppraisal()
    {
        $appraisal = RequestApproval::all();

        if ($appraisal->isEmpty()) {
            $this->warn("No records found for Request Price Appraisal.");
            return;
        }

        $ids = $appraisal->pluck('approver');
        $approvers = $this->getApproverRole($ids)->keyBy('user_id');

        foreach ($appraisal as $record) {
            $approver = $approvers->get($record->approver);

            if ($approver) {
                $record->approver = $approver->role_id;
                $record->save();

                $this->info("Updated Request Price Appraisal ID {$record->id} with approver {$approver->role_id}");
            } else {
                $this->warn("No approver found for Request Price Appraisal ID {$record->id}");
            }
        }
    }

    public function handleRequestRefurbish()
    {
        $refurbish = request_refurbish::all();

        if ($refurbish->isEmpty()) {
            $this->warn("No records found for Request Refurbish.");
            return;
        }

        $ids = $refurbish->pluck('approver');
        $approvers = $this->getApproverRole($ids)->keyBy('user_id');

        foreach ($refurbish as $record) {
            $approver = $approvers->get($record->approver);

            if ($approver) {
                $record->approver = $approver->role_id;
                $record->save();

                $this->info("Updated Request Refurbish ID {$record->id} with approver {$approver->role_id}");
            } else {
                $this->warn("No approver found for Request Refurbish ID {$record->id}");
            }
        }
    }

    public function handleSettleRefurbish()
    {
        $settle = refurbishProcess::all();

        if ($settle->isEmpty()) {
            $this->warn("No records found for Settle Refurbish.");
            return;
        }

        $ids = $settle->pluck('approver');
        $approvers = $this->getApproverRole($ids)->keyBy('user_id');

        foreach ($settle as $record) {
            $approver = $approvers->get($record->approver);

            if ($approver) {
                $record->approver = $approver->role_id;
                $record->save();

                $this->info("Updated Settle Refurbish ID {$record->id} with approver {$approver->role_id}");
            } else {
                $this->warn("No approver found for Settle Refurbish ID {$record->id}");
            }
        }
    }

    public function handleSoldUnit()
    {
        $sold = sold_unit::all();

        if ($sold->isEmpty()) {
            $this->warn("No records found for Sold Unit.");
            return;
        }

        $ids = $sold->pluck('approver');
        $approvers = $this->getApproverRole($ids)->keyBy('user_id');

        foreach ($sold as $record) {
            $approver = $approvers->get($record->approver);

            if ($approver) {
                $record->approver = $approver->role_id;
                $record->save();

                $this->info("Updated Sold Unit ID {$record->id} with approver {$approver->role_id}");
            } else {
                $this->warn("No approver found for Sold Unit ID {$record->id}");
            }
        }
    }
}
