<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class customer_profiling extends Model
{
    use HasFactory;
    
    protected $table = 'customer_profile';

    protected $fillable = [ 
        'acumatica_id', 'firstname', 'middlename', 'lastname', 'contact', 'address', 'provinces', 'cities', 'barangays', 'zip_code',
        'nationality', 'source_of_income', 'marital_status', 'date_birth', 'birth_place', 'primary_id', 'primary_id_no', 'alternative_id', 'alternative_id_no'
    ];

}
