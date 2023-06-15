<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class stock_transfer extends Model
{
    use HasFactory;
    protected $table = 'stock_transfer_approval';
    protected $fillable = [
        'from_branch',
        'to_branch',
        'reference_code',
        'approver',
        'date_approved',
        'remarks',
        'created_by',
        'reason_for_transfer',
    ];
}
