<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class acumatica_logs extends Model
{
    use HasFactory;
    protected $table = 'acumatica_logs';
    protected $fillable = [
        'sold_units_id', 'request', 'method', 'action',
        'status_code', 'parameter', 'response', 'attempt',
    ];
}
