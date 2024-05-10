<?php

namespace App\Http\Traits;

use Illuminate\Support\Facades\DB;

trait resuableQuery {

    public function cteQuery(){

        $transactions = $this->forRefurbish()->unionAll($this->forApprisal())->toSql();
        $approvers = $this->forApprover()->toSql();
        $stmt = "
            WITH transactions AS (
                SELECT
                    ROW_NUMBER() OVER (ORDER BY sub.date_approved) AS row_num,
                    sub.repo_id,
                    sub.source_process,
                    sub.settled_total_cost,
                    sub.date_approved
                FROM (
                    {$transactions}
                ) sub
            ),
            approvers AS (
                {$approvers}
            )

        ";

        return $stmt;
    }

    private function forRefurbish()
    {
        return DB::table('request_refurbishes AS request')
            ->leftJoin('refurbish_processes AS settle', 'request.id', 'settle.refurbish_req_id')
            ->leftJoin(DB::raw('(
                SELECT
                    refurb_id, SUM(actual_price) AS total_cost
                FROM recieve_unit_spare_parts
                WHERE refurb_id IS NOT NULL
                GROUP BY refurb_id
                ) AS parts'),
                'request.id', 'parts.refurb_id'
            )
            ->select(
                DB::raw("'refurbishment' AS source_process"),
                'request.repo_id',
                'total_cost AS settled_total_cost',
                'settle.updated_at AS date_approved'
            );
    }

    private function forApprisal()
    {
        return DB::table(DB::raw('(
                SELECT MAX(id) AS latest_request_id, repo_id
                FROM request_approvals
                GROUP BY repo_id
            ) AS request'))
            ->join('appraisal_histories AS history', 'request.latest_request_id', 'history.appraisal_req_id')
            ->select(
                DB::raw("'appraisal' AS source_process"),
                'request.repo_id',
                'history.appraised_price',
                'history.created_at'
            );
    }

    private function forApprover()
    {
        return DB::table(DB::raw('approval_matrix_settings'))
            ->select(
                DB::raw("JSON_VALUE(signatories, '$[0].user') AS approverId"),
                'module_id',
                'level'
            );
    }
}
