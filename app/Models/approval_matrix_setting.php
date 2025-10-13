<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
class approval_matrix_setting extends Model
{
    use HasFactory;

    protected $table = 'approval_matrix_settings';

    protected $fillable = [
        'module_id',
        'level',
        'signatories'
    ];

    protected $casts = [
        'signatories' => 'array'
    ];

    public function scopeForApprover($query)
    {
        return $query->select(
            DB::raw("JSON_VALUE(signatories, '$[0].role') AS approver_role_id"),
            DB::raw("JSON_QUERY(signatories, '$[0].users') AS approver_users"),
            'module_id',
            'level'
        );
    }

    public function scopeGetApprovalMatrix($query, $moduleid, $userrole)
    {
        $sql = "
            SELECT
                ams.module_id,
                ams.level,
                CAST(JSON_VALUE(ams.signatories, '$[0].role') AS INT) AS approver_role_id,
                CONCAT('[', ISNULL(users_list.users, ''), ']') AS approver_users
            FROM approval_matrix_settings ams
            OUTER APPLY (
                SELECT STRING_AGG(
                    CONCAT('{\"id\":', JSON_VALUE([value], '$.id'), '}'), ','
                ) AS users
                FROM OPENJSON(ams.signatories, '$[0].users')
            ) users_list
            INNER JOIN user_role role
                ON role.id = CAST(JSON_VALUE(ams.signatories, '$[0].role') AS INT)
            WHERE ams.module_id = :module AND role.user_role_name = :role
        ";

        $bindings = [
            'module' => $moduleid,
            'role'   => $userrole,
        ];

        return DB::select($sql, $bindings);
    }

}
