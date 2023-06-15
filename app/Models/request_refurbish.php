<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class request_refurbish extends Model
{
    use HasFactory;

    public function missingParts(){
        return $this->hasMany(refurbish_detail::class, 'refurbish_id', 'id');
    }
}
