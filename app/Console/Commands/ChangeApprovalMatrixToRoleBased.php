<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ChangeApprovalMatrixToRoleBased extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'approval:matrix';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Convert user-based approval matrix to role-based structure';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        DB::beginTransaction();

        try {
            // --- STEP 1: Transform signatories in approval_matrix_settings ---
            $records = DB::table('approval_matrix_settings')->get();

            foreach ($records as $record) {
                $original = json_decode($record->signatories, true);
                if (!is_array($original)) {
                    $this->warn("Skipping ID {$record->id}: invalid JSON");
                    continue;
                }

                $newSignatories = [];

                foreach ($original as $entry) {
                    $userId = $entry['user'] ?? null;
                    if (!$userId) continue;

                    $user = DB::table('users')->where('id', $userId)->first();
                    if (!$user) continue;

                    $role = DB::table('user_role')->where('user_role_name', $user->userrole)->first();
                    if (!$role) continue;

                    $newSignatories[] = ['user' => (string) $role->id];
                }

                // Update only if we have valid new signatories
                if (!empty($newSignatories)) {
                    DB::table('approval_matrix_settings')
                        ->where('id', $record->id)
                        ->update(['signatories' => json_encode($newSignatories)]);

                    $this->info("Updated approval_matrix_settings ID {$record->id}");
                } else {
                    $this->warn("No valid role mappings for ID {$record->id}");
                }
            }

            // --- STEP 2: Update approval_activity_logs ---
            // (1) Set approved_by = user_id where decision is not null
            DB::table('approval_activity_logs')
                ->whereNotNull('decision')
                ->update([
                    'approved_by' => DB::raw('user_id')
                ]);

            // (2) Update user_id to corresponding role_id
            DB::statement("
                UPDATE logs
                SET user_id = r.id
                FROM approval_activity_logs logs
                LEFT JOIN users u ON u.id = logs.user_id
                LEFT JOIN user_role r ON u.userrole = r.user_role_name
            ");

            DB::commit();
            $this->info('Approval matrix and activity logs updated successfully.');

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Approval matrix update failed: ' . $e->getMessage());
            $this->error('Update failed. Changes rolled back.');
        }

        return Command::SUCCESS;
    }

}
