<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class approval_matrix_setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'module_id',
        'level',
        'signatories'
    ];

    protected $casts = [
        'signatories' => 'array'
    ];
}
