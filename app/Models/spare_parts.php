<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class spare_parts extends Model
{
    use HasFactory;

    protected $fillable = [
        // 'model_id',
        'name',
        'price',
        'status'
        // 'inventory_code'
    ];
}
