<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhysicalInventoryDoc extends Model
{
    use HasFactory;

    protected $table = 'physical_inventory_docs';

    protected $fillable = [
        'branch_id',
        'selected_date',
        'created_by',
        'deleted_by',
        'is_deleted',
        'deleted_at',
        'status',
        'approver',
        'approved_date',
        'reason',
        'remarks',
    ];
}
