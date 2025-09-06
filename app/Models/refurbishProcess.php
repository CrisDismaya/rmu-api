<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class refurbishProcess extends Model
{
    use HasFactory;

    protected $table = 'refurbish_processes';

    protected $fillable = [
        'refurbish_req_id',
        'files_names',
        'maker',
        'approver',
        'status',
        'remarks',
        're_class',
    ];
}
