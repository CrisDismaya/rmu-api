<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class approval_activity_log extends Model
{
    use HasFactory;

    protected $table = 'approval_activity_logs';

    protected $fillable = [
        'module_id',
        'rec_id',
        'user_id', // This is the user role ID
        'order', // order of approval
        'decision', // A for approved, null for pending
        'approved_by', // user ID who approved
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'approved_by', 'id');
    }
}
