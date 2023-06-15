<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class system_menu extends Model
{
    use HasFactory;
    
    protected $table = 'system_menu';
    protected $fillable = [
        'category_name', 'parent_id', 'menu_name', 'file_path',
    ];
}
