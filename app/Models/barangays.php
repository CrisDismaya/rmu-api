<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class barangays extends Model
{
    use HasFactory;
    protected $table = 'tbl_cities';

    protected $fillable = [ 'code', 'name', 'oldName', 'subMunicipalityCode', 'cityCode', 'municipalityCode', 'districtCode', 'provinceCode', 'regionCode', 'islandGroupCode', 'psgc10DigitCode' ];
}
