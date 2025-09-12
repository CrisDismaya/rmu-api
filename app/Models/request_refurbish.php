<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class request_refurbish extends Model
{
    use HasFactory;

    protected $table = 'request_refurbishes';

    protected $fillable = [
        'repo_id',
        'branch',
        'maker',
        'approver',
        'date_approved',
        'files_names',
        'paths',
        'remarks',
        'status',
    ];

    public function missingParts(){
        return $this->hasMany(refurbish_detail::class, 'refurbish_id', 'id');
    }
}
