<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class menu_mapping extends Model
{
    use HasFactory;
    protected $table = 'user_role_menu_mapping';
    protected $fillable = [
        'user_role_id', 'menu_id', 'created_by',
    ];
}
