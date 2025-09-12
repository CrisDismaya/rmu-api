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
            DB::raw("JSON_VALUE(signatories, '$[0].user') AS approverId"),
            'module_id',
            'level'
        );
    }
}
