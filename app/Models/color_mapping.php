<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class color_mapping extends Model
{
    use HasFactory;

    public function model(){
        return $this->belongsTo(unit_model::class,'id','model_id');
    }

    public function colorName(){
        return $this->belongsTo(unit_color::class,'color_id','id');
    }
}
