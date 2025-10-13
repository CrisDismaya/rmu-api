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
        try {
            DB::transaction(function () {
                $this->info('Fetching approval matrix...');

                $matrix = DB::select("SELECT
                        MIN(sub.id) AS id,
                        sub.module_id,
                        MIN(sub.level) AS level,
                        (
                            SELECT
                                role.id AS role,
                                (
                                    SELECT
                                        users.id
                                    FROM users
                                    INNER JOIN (
                                        SELECT
                                            module_id,
                                            level,
                                            JSON_VALUE(signatories, '$[0].user') AS approver_user_id
                                        FROM approval_matrix_settings
                                    ) sub2
                                    ON sub2.approver_user_id = users.id
                                    AND sub2.module_id = sub.module_id
                                    AND role.user_role_name = users.userrole
                                    FOR JSON PATH
                                ) AS users
                            FOR JSON PATH
                        ) AS signatories
                    FROM (
                        SELECT
                            id,
                            module_id,
                            level,
                            JSON_VALUE(signatories, '$[0].user') AS approver_user_id
                        FROM approval_matrix_settings
                    ) sub
                    INNER JOIN users ON sub.approver_user_id = users.id
                    INNER JOIN user_role AS role ON users.userrole = role.user_role_name
                    GROUP BY sub.module_id, role.id, role.user_role_name
                    ORDER BY sub.module_id, MIN(sub.level)
                ");

                foreach ($matrix as $row) {
                    DB::table('approval_matrix_settings')
                    ->where('id', $row->id)
                    ->update([
                        'signatories' => $row->signatories
                    ]);
                }

                $matrix_ids = collect($matrix)->pluck('id')->toArray();

                $this->info('Cleaning old matrix entries...');
                DB::table('approval_matrix_settings')
                    ->whereNotIn('id', $matrix_ids)
                    ->delete();

                $this->info('Migrating approval activity logs...');
                DB::table('approval_activity_logs')
                    ->whereNotNull('decision')
                    ->update(['approved_by' => DB::raw('user_id')]);

                // SELECT null AS module_id, id AS record_id FROM recieve_unit_details WHERE status = 0
                // SELECT null AS module_id, id AS record_id FROM stock_transfer_approval WHERE status = 0
                // SELECT null AS module_id, id AS record_id FROM request_approvals WHERE status = 0
                // SELECT null AS module_id, id AS record_id FROM request_refurbishes WHERE status = 0
                // SELECT null AS module_id, id AS record_id FROM refurbish_processes WHERE status = 0
                // SELECT null AS module_id, id AS record_id FROM sold_units WHERE status = 0
                // SELECT null AS module_id, id AS record_id FROM physical_inventory_docs WHERE status = 0
                DB::table('approval_activity_logs')
                    ->whereNull('decision')
                    ->delete();

                $this->info('Updating logs with role references...');
                DB::statement("
                    UPDATE logs
                    SET user_id = r.id
                    FROM approval_activity_logs logs
                    LEFT JOIN users u ON u.id = logs.user_id
                    LEFT JOIN user_role r ON u.userrole = r.user_role_name
                ");

            });

            $this->info('✅ Migration completed successfully!');
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Approval matrix migration failed: ' . $e->getMessage());
            $this->error('Migration failed, check logs for details.');
            return 1;
        }

        return 0;



        // try {
        //     // --- STEP 1: Transform signatories in approval_matrix_settings ---
        //     $records = DB::table('approval_matrix_settings')->get();

        //     foreach ($records as $record) {
        //         $original = json_decode($record->signatories, true);
        //         if (!is_array($original)) {
        //             $this->warn("Skipping ID {$record->id}: invalid JSON");
        //             continue;
        //         }

        //         $newSignatories = [];

        //         foreach ($original as $entry) {
        //             $userId = $entry['user'] ?? null;
        //             if (!$userId) continue;

        //             $user = DB::table('users')->where('id', $userId)->first();
        //             if (!$user) continue;

        //             $role = DB::table('user_role')->where('user_role_name', $user->userrole)->first();
        //             if (!$role) continue;

        //             $newSignatories[] = ['user' => (string) $role->id];
        //         }

        //         // Update only if we have valid new signatories
        //         if (!empty($newSignatories)) {
        //             DB::table('approval_matrix_settings')
        //                 ->where('id', $record->id)
        //                 ->update(['signatories' => json_encode($newSignatories)]);

        //             $this->info("Updated approval_matrix_settings ID {$record->id}");
        //         } else {
        //             $this->warn("No valid role mappings for ID {$record->id}");
        //         }
        //     }

        //     // --- STEP 2: Update approval_activity_logs ---
        //     // (1) Set approved_by = user_id where decision is not null
        //     DB::table('approval_activity_logs')
        //         ->whereNotNull('decision')
        //         ->update([
        //             'approved_by' => DB::raw('user_id')
        //         ]);

        //     // (2) Update user_id to corresponding role_id
        //     DB::statement("
        //         UPDATE logs
        //         SET user_id = r.id
        //         FROM approval_activity_logs logs
        //         LEFT JOIN users u ON u.id = logs.user_id
        //         LEFT JOIN user_role r ON u.userrole = r.user_role_name
        //     ");

        //     DB::commit();
        //     $this->info('Approval matrix and activity logs updated successfully.');

        // } catch (\Throwable $e) {
        //     DB::rollBack();
        //     Log::error('Approval matrix update failed: ' . $e->getMessage());
        //     $this->error('Update failed. Changes rolled back.');
        // }

        return Command::SUCCESS;
    }

}
