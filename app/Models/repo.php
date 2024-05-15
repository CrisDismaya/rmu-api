<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class repo extends Model
{
    use HasFactory;

    protected $table = 'repo_details';

    protected $fillable = [
        'branch_id',
        'customer_acumatica_id',
        'brand_id',
        'brand_id',
        'model_id',
        'model_engine',
        'model_chassis',
        'color_id',
        'plate_number',
        'mv_file_number',
        'year_model',
        'orcr_status',
        'unit_documents',
        'date_sold',
        'date_surrender',
        'original_srp',
        'last_payment',
        'loan_number',
        'odo_meter',
        'location',
        'transfer_branch_id',
        'classification',
        'apprehension',
        'apprehension_description',
        'apprehension_summary',
        'msuisva_form_no',
    ];

    function branch_details(){
        return $this->hasOne(branch::class, 'id', 'branch_id');
    }

    function customer_details(){
        return $this->hasOne(customer_profiling::class, 'acumatica_id', 'customer_acumatica_id');
    }

    function brand_details(){
        return $this->hasOne(brand::class, 'id', 'brand_id');
    }

    function model_details(){
        return $this->hasOne(unit_model::class, 'id', 'model_id');
    }

    function color_details(){
        return $this->hasOne(unit_color::class, 'id', 'color_id');
    }

    function picture_details(){
        return $this->hasMany(FilesUploaded::class, 'reference_id', 'id')->where('is_deleted', '0');
    }

    function received_details(){
        return $this->hasOne(receive_unit::class, 'repo_id', 'id');
    }
}
