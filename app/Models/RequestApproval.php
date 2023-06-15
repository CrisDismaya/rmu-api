<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequestApproval extends Model
{
    use HasFactory;

    protected $fillable = [
        'received_unit_id',
        'repo_id',
        'branch',
        'unit_age_days',
        'depreciation_cost',
        'estimated_missing_dmg_parts',
        'total_missing_dmg_parts',
        'suggested_price',
        'approved_price',
        'approver',
        'date_approved',
        'remarks',
        'created_by'
    ];
}
