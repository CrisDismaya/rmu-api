<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class unit_color extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
    ];

    public function colorName(){
        return $this->hasMany(color_mapping::class,'color_id','id');
    }
}
