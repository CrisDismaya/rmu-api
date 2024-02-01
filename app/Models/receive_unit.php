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
		'loan_amount',
		'total_payments',
		'principal_balance',
		'is_certified_no_parts',
		'original_owner',
		'approver',
		'date_approved',
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
