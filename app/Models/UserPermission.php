<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPermission extends Model
{
    use HasFactory;

    protected $table = 'user_module_permissions';

    protected $fillable = [
        'user_id',
        'role_id',
        'menu_id',
        'view_permission',
        'add_permission',
        'update_permission',
        'approval_permission',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'role_id' => 'integer',
        'menu_id' => 'integer',
        'view_permission' => 'boolean',
        'add_permission' => 'boolean',
        'update_permission' => 'boolean',
        'approval_permission' => 'boolean',
    ];
}
