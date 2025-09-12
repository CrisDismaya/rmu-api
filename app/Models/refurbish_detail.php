<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class refurbish_detail extends Model
{
    use HasFactory;

    protected $table = 'refurbish_details';

    protected $fillable = [
        'refurbish_id',
        'spare_parts',
        'price',
        'actual_price',
        'status',
    ];

    public function refurbish(){
        return $this->belongsTo(request_refurbish::class,'id','refurbish_id');
    }
}
