<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class brand extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'brandname',
    ];

    public function model(){
        return $this->belongsTo(unit_model::class,'brand_id','id');
    }
}
