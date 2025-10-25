<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class sold_unit extends Model
{
    use HasFactory;

    protected $table = 'sold_units';

    protected $fillable = [
        'repo_id',
        'branch',
        'new_customer',
        'invoice_reference_no',
        'ExternalReference',
        'AgentID',
        'sale_type',
        'srp',
        'dp',
        'amount_paid',
        'monthly_amo',
        'rebate',
        'terms',
        'rate',
        'interest_rate',
        'amount_finance',
        'sold_date',
        'maker',
        'approver',
        'file_name',
        'path',
        'status',
        'remarks',
        'transaction_number',
        'transaction_number_inventory_out',
        'inventory_out_at',
        'pt_receipt_no',
        'pt_date',
        'pt_bank',
        'pt_amount',
        'pt_uploads',
    ];
}
