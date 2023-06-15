<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class cities extends Model
{
    use HasFactory;

    protected $table = 'tbl_cities';

    protected $fillable = [ 'code', 'name', 'oldName', 'isCapital', 'provinceCode', 'districtCode', 'regionCode', 'islandGroupCode', 'psgc10DigitCode' ];

}
