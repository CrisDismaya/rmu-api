<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class receive_unit extends Model
{
	use HasFactory;

	protected $table = 'recieve_unit_details';

	protected $fillable = [
        'branch',
        'repo_id',
        'unit_price',
        'status',
        'is_sold',
        'sold_type',
        'loan_amount',
        'principal_balance',
        'total_payments',
        'is_certified_no_parts',
        'original_owner',
        'original_owner_id',
        'approver',
        'date_approved',
        'redemption_at',
	];

	function repo_details(){
		return $this->hasOne(repo::class, 'id', 'repo_id');
	}

	function spare_parts_details(){
		return $this->hasMany(unit_spare_parts::class, 'recieve_id', 'id');
	}

	function files_details(){
		return $this->hasMany(FilesUploaded::class, 'reference_id', 'id')->where('is_deleted', '=', '0');
	}

}
