<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class received_part extends Model
{
    use HasFactory;

    protected $table = 'recieve_unit_spare_parts';

    protected $fillable = [
        'received_id',
        'parts_id',
        'parts_status',
        'price',
        'parts_remarks',
        'is_deleted',
        'actual_price',
        'refurb_decision',
        'refurb_id',
    ];

}
