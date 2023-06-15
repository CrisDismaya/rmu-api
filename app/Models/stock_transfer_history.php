<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class stock_transfer_history extends Model
{
    use HasFactory;
    protected $table = 'history_stock_transfer';
    protected $fillable = [
        'stock_transfer_id',
        'received_unit_id',
        'from_branch',
        'to_branch'
    ];
}
