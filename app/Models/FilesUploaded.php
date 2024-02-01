<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FilesUploaded extends Model
{
    use HasFactory;

    protected $table = 'files_uploaded';

    protected $fillable = ['reference_id', 'module_id', 'branch_id', 'files_id', 'files_name', 'path'];
}
