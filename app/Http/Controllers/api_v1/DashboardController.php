<?php

namespace App\Http\Controllers\api_v1;

use Illuminate\Http\Request;
use App\Http\Controllers\api_v1\BaseController as BaseController;
use Illuminate\Support\Facades\Validator;
use App\Models\RequestApproval;
use App\Models\unit_aging;
use App\Models\receive_unit;
use Carbon\Carbon;
use DB;
use Auth;

class DashboardController extends BaseController
{
	//

	public function dashboardCounter()
	{
		$for_approval = 0;
		$received_units = DB::table('recieve_unit_details AS rud');

		if (Auth::user()->userrole == 'finance') {
			$received_units = $received_units->where('rud.status', '0')->count();
			$for_approval = $received_units;
		}

		$sidebar_counter_notif = [
			'for_approval' => $for_approval
		];

		return $sidebar_counter_notif;
	}

	function getSidebarNotif()
	{
		$stock_transfer = $receive_stock_transfer = 0;

		if (strtoupper(Auth::user()->userrole) == strtoupper('Verifier') || strtoupper(Auth::user()->userrole) == strtoupper('General Manager')) {
			$stock_transfer = DB::table('stock_transfer_approval')
				->where('approver', function ($query) {
					$query->select('id')
						->from('users')
						->whereRaw('UPPER(userrole) = UPPER(?)', [Auth::user()->userrole]);
				})
				->where('status', '=', '0')
				->count();
		} else if (strtoupper(Auth::user()->userrole) == strtoupper('Warehouse Custodian')) {
			$receive_stock_transfer = DB::table('stock_transfer_approval as sta')
				->join('stock_transfer_unit as stu', 'sta.id', '=', 'stu.stock_transfer_id')
				->where('sta.status', '=', 1)
				->where('stu.is_received', '=', 0)
				->where('sta.to_branch', '=', Auth::user()->branch)
				->count('stu.id');
		}

		$sidebar_counter_notif = [
			'stock_transfer' => $stock_transfer,
			'receive_stock_transfer' => $receive_stock_transfer
		];
		return $sidebar_counter_notif;
	}
}
