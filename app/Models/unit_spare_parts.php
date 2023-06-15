<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class unit_spare_parts extends Model
{
    use HasFactory;
    
    protected $table = 'recieve_unit_spare_parts';
    
    protected $fillable = [
        'recieve_id',
        'parts_id',
        'parts_status',
        'price',
        'parts_remarks',
        // 'is_deleted'
    ];

    function spare_parts_details(){
        return $this->hasOne(spare_parts::class, 'id', 'parts_id');
    }

}
