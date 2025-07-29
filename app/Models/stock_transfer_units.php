<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class stock_transfer_units extends Model
{
	use HasFactory;
	protected $table = 'stock_transfer_unit';
	protected $fillable = [
		'stock_transfer_id',
		'recieved_unit_id',
        'transaction_number_inventory_out',
        'inventory_out_at',
        'is_received',
        'trans_no_received',
        'received_at',
	];
}
