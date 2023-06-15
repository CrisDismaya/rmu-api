<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class unit_aging extends Model
{
    use HasFactory;

    protected $fillable = [
        'days',
        'Depreceiation_Cost',
        'Estimated_Cost_of_MD_Parts',
        'Max_Depreciation_from_Original_SP',
        'Immediate_Sales_Value',
    ];
}
