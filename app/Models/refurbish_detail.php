<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class refurbish_detail extends Model
{
    use HasFactory;

    public function refurbish(){
        return $this->belongsTo(request_refurbish::class,'id','refurbish_id');
    }
}
