<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class source_of_income extends Model
{
    use HasFactory;
    protected $table = 'source_of_income';
    protected $fillable = [
        'source'
    ];
}
