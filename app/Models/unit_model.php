<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class unit_model extends Model
{
    use HasFactory;

    protected $fillable = [
        'brand_id',
        'model_name',
        'inventory_code'
    ];

    public function colors(){
        return $this->hasMany(color_mapping::class,'model_id','id');
    }

    public function brands(){
        return $this->hasMany(brand::class,'id','brand_id');
    }
}
