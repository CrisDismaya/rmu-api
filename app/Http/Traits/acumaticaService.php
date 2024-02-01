<?php

namespace App\Http\Traits;

use App\Models\customer_profiling;
use App\Models\receive_unit;
use App\Models\sold_unit;
use App\Models\acumatica_logs;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Cookie\CookieJar;

trait acumaticaService {
	
	public $host = 'https://ciclo.acumatica.com';
	public $version = '20.200.001';
	public $sold_units_id;
	public $repo_id;
	public $filtered_status = [200, 202, 204];
	
	public function __construct(){
		$this->guzzle = new Guzzle();
		$this->cookies = new CookieJar();
	}

	public function acumatica_login(){
		$endpoint = "{$this->host}/entity/auth/login";
		$param =  [
			"name" => "Test User",
			"password" => "P@ssw0rd1",
			"Company" => "CICLO SUERTE CORPORATION"
		];

		$response = $this->guzzle->request('POST', $endpoint, [
			'json' => $param,
			'cookies' => $this->cookies,
			'content-type' => 'application/json',
			'verify' => false,
		]);

		$json =  json_decode($response->getBody()->getContents());
		$code = $response->getStatusCode();

		$res = [];
		if ($code == 200 || $code == 204) {
			$res = [ 'status' => 200, 'message' => 'Successfully Login' ];
		} else {
			$res = [ 'status' => $code, 'message' => $json ];
		}
		return $res;
	}

	public function acumatica_request($method, $url, $data, $action, $request){
		try {
			$response = $this->guzzle->request($method, $url, [
				'json' => $data,
				'cookies' => $this->cookies,
				'content-type' => 'application/json',
				'verify' => false,
				'delay' => 3000
			]);

			$responseBody = json_decode($response->getBody()->getContents());
			$code = $response->getStatusCode();
			
			$res = [];
			if ($code == 200 || $code == 204 || $code == 202) {
				$res = [ 'status' => 200, 'message' => 'Successfully Saved!', 'data' => $responseBody ];
			} else {
				$res = [ 'status' => 404, 'message' => 'Failed!', 'data' => $responseBody ];
			}

			if($action != 'SalesPerson'){
				$this->acumatica_logs($request, $method, $action, $code, $data, ($responseBody ?? []));
			}
			return $res;

		} catch (\Exception $e) {
			$record = DB::table('system_menu')->where('file_path', '_sales-tagging.php')->where('status', 1)->first();
			$this->rollBaclDecision($record->id, $this->sold_units_id, Auth::user()->id);

			$error_message = $e->getMessage();
			if($action != 'SalesPerson'){
				$this->acumatica_logs($request, $method, $action, 404, $data, $error_message);
			}
		}
	}

	public function getSalesAgentList(){
		$acu_login = $this->acumatica_login();
		$endpoint = "{$this->host}/entity/CICLO-API/{$this->version}/SalesPerson";

		$param =  [];
		$res = [];

		if($acu_login['status'] === 200){
			$response = $this->acumatica_request('GET', $endpoint, $param, 'SalesPerson', []);
			$res = $response;
			if($response['status'] === 200){
				// Update and UnHold Shipment
				$data = array();
				foreach ($response['data'] as $obj) {
					# code...
					
					if($obj->IsActive->value){
						array_push($data,['id' => $obj->SalespersonID->value, 'name' => $obj->Name->value]);
					}
				}
				return  $data;
			}
		}
		else{
			$res = [ 'status' => 400, 'message' => 'error login' ];
		}
		return $res;
	}

	public function create_customer($salesDetails){
		$acu_login = $this->acumatica_login();
		$endpoint = "{$this->host}/entity/CICLO-API/{$this->version}/Customer";
		
		$this->repo_id = $salesDetails->repo_id;
		$this->sold_units_id = $salesDetails->id;
		$customer_details = customer_profiling::where('id', $salesDetails->new_customer)->first();
		$province = DB::table('province')->where('OrderNumber',$customer_details->provinces)->first();
		$city = DB::table('city')->where('MappingId',$customer_details->cities)->first();
		$brgy = DB::table('barangay')->where('OrderNumber',$customer_details->barangays)->first();
		$orderType = $salesDetails->sale_type == 'C' ? 'RS' : 'RI'; 

		$param = [
			"CustomerID" =>  [ "value" => "<NEW>"],
			"CustomerClass" =>  [ "value" => "TCUST"],
			"CustomerName" =>  [ "value" => "$customer_details->firstname " . ($customer_details->middlename ?? ' ') . " $customer_details->lastname" ],
			"Status" =>  [ "value" => "ACTIVE"],
			"FirstName" =>  [ "value" => $customer_details->firstname],
			"LastName" =>  [ "value" => $customer_details->lastname],
			"AddressLine1" =>  [ "value" => $customer_details->address],
			"Barangay" =>  [ "value" => $brgy->Title],
			"City" => [ "value" => $city->Title],
			"Province" =>  [ "value" => $province->Title],
			"Country" => [ "value" => "PH"],
			"Email" =>  [ "value" => "jdelacruz@gmail.com"],
			"ContactNumber" =>  [ "value" => $customer_details->contact],
			"Terms" =>  [ "value" => ($salesDetails->terms * 30) . "D"],
			"StatementCycleID" =>  [ "value" => "30D"],
			"FinanceBy" =>  [ "value" => "CUST00000000011"],
			"TaxZone" =>  [ "value" => "VAT"],
			"TIN" =>  [ "value" => "000-000-000-000"],
			"ATC" =>  [ "value" => "W000"],
			"Branch" =>  [ "value" => "PAS032"],
		];
		
		if($acu_login['status'] === 200){
			$checker = $this->acumatica_checker($this->sold_units_id);
			if($checker['boolean']){
				$id_checker = $customer_details->acumatica_id;
				$acumaticaPrefixId = 'CUST';
				if(strpos($id_checker, $acumaticaPrefixId) !== false){
					return $this->create_sales_order(
						$customer_details->acumatica_id,
						$customer_details,
						$salesDetails->branch,
						$salesDetails->repo_id,
						$salesDetails->ExternalReference,
						$salesDetails->AgentID,
						$orderType
					);
				}
				else{
					$response = $this->acumatica_request('PUT', $endpoint, $param, 'Customer', $salesDetails);
					$status = $response['status'] ?? null;
					if($status === 200){
						$acumatica_id = $response['data']->CustomerID->value;
						customer_profiling::where('id', $customer_details->id)->update([ 'acumatica_id' => $acumatica_id ]);
						return $this->create_sales_order(
							$acumatica_id,
							$customer_details,
							$salesDetails->branch,
							$salesDetails->repo_id,
							$salesDetails->ExternalReference,
							$salesDetails->AgentID,
							$orderType
						);
					}
					else {
						return false;
					}
				}
			}
		}
		else{
			return false;
		}
		return true;
	}
	
	public function create_sales_order($acumatica_id, $customer, $branch, $repoId, $ExternalReference, $AgentID, $orderType){
		$acu_login = $this->acumatica_login();
		  
		$branchInfo = DB::table('branches')->where('id', $branch)->first();
		$repo_details = DB::table('repo_details')->where('id', $repoId)->first();
		$inventoryInfo = DB::table('unit_models')->where('id', $repo_details->model_id)->first();
		$colorInfo = DB::table('unit_colors')->where('id', $repo_details->color_id)->first();
		
		$endpoint = "{$this->host}/entity/CICLO-API/{$this->version}/SalesOrder";
		$param =  [
			"OrderType" => [ "value" => $orderType ],
			"OrderRefNbr" => [ "value" => "<NEW>" ],
			"CustomerID" => [ "value" => $acumatica_id ],
			"Date" => [ "value" => date('m/d/Y') ],
			"Description" =>  [ "value" => "SOLD REPO UNIT TO $customer->firstname " . ($customer->middlename ?? ' ') . " $customer->lastname" ],
			"CustomerOrderNumber" => [ "value" => "TRANSASIATIC" ],
			"ExternalReference" => [ "value" => $ExternalReference ],
			"LocalPurchase" => [ "value" => "No" ],
			"BranchID" => [ "value" => $branchInfo->branchCode ],
			"AgentID" => [ "value" => $AgentID ],
			"Detail" => [
				[
					"BranchID" => [ "value" => $branchInfo->branchCode ],
					"InventoryID" => [ "value" => $inventoryInfo->inventory_code ],
					"ColorID" => [ "value" => $colorInfo->code ],
					"WarehouseID" => [ "value" => $branchInfo->warehouseID ],
					"EquipmentAction" => [ "value" => "N/A" ],
					"LotSerialNbr" => [ "value" => $repo_details->model_engine."#".$repo_details->model_chassis ],
					"Quantity" => [ "value" => "1" ],
					"UnitPrice" => [ "value" => "0" ],
					"TaxCategory" => [ "value" => "SGOODS" ]
				]
			]
		];
		$res = [];
		
		if($acu_login['status'] === 200){
			$response = $this->acumatica_request('PUT', $endpoint, $param, 'SalesOrder', [
				'acumatica_id' => $acumatica_id,
				'customer' => $customer,
				'branch' => $branch,
				'repoId' => $repoId,
				'ExternalReference' => $ExternalReference,
				'AgentID' => $AgentID,
				'orderType' => $orderType
			]);
			$status = $response['status'] ?? null;
			if($status === 200){
				// create shipment
				$OrderRefNbr = $response['data']->OrderRefNbr->value;
				$this->create_shipment($OrderRefNbr, $branchInfo->warehouseID, $orderType);
				return true;
			}
			else {
				return false;
			}
		}
		else{
			return false;
		}
		return false;
	}
	
	public function create_shipment($OrderRefNbr, $warehouse, $orderType){
		$acu_login = $this->acumatica_login();
		$endpoint = "{$this->host}/entity/CICLO-API/{$this->version}/SalesOrder/CreateShipment";
		$param =  [
			"entity" => [
				"OrderType" => [ "value" => "{$orderType}" ],
				"OrderRefNbr" => [ "value" => $OrderRefNbr ],
			],
			"Parameters" => [
				"ShipmentDate" => [ "value" => date('m/d/Y') ],
				"WarehouseID" => [ "value" => $warehouse ]	
			]
		];
		$res = [];

		if($acu_login['status'] === 200){
			$response = $this->acumatica_request('POST', $endpoint, $param, 'CreateShipment', [
				'OrderRefNbr' => $OrderRefNbr,
				'warehouse' => $warehouse,
				'orderType' => $orderType,
			]);
			
			$status = $response['status'] ?? null;
			if($status === 200){
				return $this->getShipmentNumber($OrderRefNbr, $orderType);
			}
			else {
				return false;
			}
		}
		else{
			return false;
		}
		return false;
	}
	
	public function getShipmentNumber($OrderRefNbr, $orderType){
		$acu_login = $this->acumatica_login();
		$endpoint = 'https://ciclo.acumatica.com/entity/CICLO-API/20.200.001/SalesOrder?$filter=OrderType eq '."'$orderType'".' and OrderRefNbr eq '."'$OrderRefNbr'";
		$param =  [];
		$res = [];

		if($acu_login['status'] === 200){
			$response = $this->acumatica_request('GET', $endpoint, $param, 'SalesOrder', [
				'OrderRefNbr' => $OrderRefNbr,
				'orderType' => $orderType,
			]);
			
			$status = $response['status'] ?? null;
			if($status === 200){
				// Update and UnHold Shipment
				$ShipmentNbr = $response['data'][0]->ShipmentNbr->value;
				$Description = $response['data'][0]->Description->value;

				return $this->update_and_unhold_shipment($ShipmentNbr, $Description);
			}
			else {
				return false;
			}
		}
		else{
			return false;
		}
		return false;
	}
	
	public function update_and_unhold_shipment($ShipmentNbr, $desciption){
		$acu_login = $this->acumatica_login();
		$endpoint = "{$this->host}/entity/CICLO-API/{$this->version}/Shipment";
		$param =  [
			"Type" => [ "value" => "Shipment" ],
			"ShipmentNbr" => [ "value" => "{$ShipmentNbr}" ],
			"ControlQuantity" => [ "value" => "1" ],
			"Description" => [ "value" => "{$desciption}" ],
		];
		$res = [];

		if($acu_login['status'] === 200){
			$response = $this->acumatica_request('PUT', $endpoint, $param, 'Shipment', [
				'ShipmentNbr' => $ShipmentNbr,
				'desciption' => $desciption,
			]);
			
			$status = $response['status'] ?? null;
			if($status === 200){
				return $this->confirm_shipment($ShipmentNbr);
			}
			else {
				return false;
			}
		}
		else{
			return false;
		}
		return false;
	}

	public function confirm_shipment($ShipmentNbr){
		$acu_login = $this->acumatica_login();
		$endpoint = "{$this->host}/entity/CICLO-API/{$this->version}/Shipment/ConfirmShipment";
		$param =  [
			"entity" => [ 
				"Type" => [ "value" => "Shipment" ],
				"ShipmentNbr" => [ "value" => "{$ShipmentNbr}" ],
			]
		];
		$res = [];

		if($acu_login['status'] === 200){
			$response = $this->acumatica_request('POST', $endpoint, $param, 'ConfirmShipment', [
				'ShipmentNbr' => $ShipmentNbr
			]);

			$status = $response['status'] ?? null;
			if($status === 200){
				return $this->update_in_shipment($ShipmentNbr);
			}
			else {
				return false;
			}
		}
		else{
			return false;
		}
		return false;
	}

	public function update_in_shipment($ShipmentNbr){
		$acu_login = $this->acumatica_login();
		$endpoint = "{$this->host}/entity/CICLO-API/{$this->version}/Shipment/updateIN";
		$param =  [
			"entity" => [ 
				"Type" => [ "value" => "Shipment" ],
				"ShipmentNbr" => [ "value" => "{$ShipmentNbr}" ],
			]
		];
		$res = [];

		if($acu_login['status'] === 200){
			$response = $this->acumatica_request('POST', $endpoint, $param, 'updateIN', [
				'ShipmentNbr' => $ShipmentNbr
			]);
			
			$status = $response['status'] ?? null;
			if($status == 200){
				return true;
			}
			else {
				return false;
			}
		}
		else{
			return false;
		}
		return false;
	}

	public function acumatica_checker($id){
		$record = DB::table('acumatica_logs')->where('sold_units_id', $id)->get();

		if(count($record) > 0){
			$object = $record[count($record) - 1];

			$latest_action = $object->action;
			$latest_method = $object->method;
			$latest_status_code = $object->status_code;
			$array = json_decode($object->request);

			if($latest_action == 'Customer' && $latest_method == 'PUT' && $latest_status_code == 404){
				$this->create_customer($array);
				return [ 'message' => 'Customer', 'boolean' => false ];
			}
			else if($latest_action == 'SalesOrder' && $latest_method == 'PUT' && $latest_status_code == 404){
				$this->create_sales_order($array->acumatica_id, $array->customer, $array->branch, $array->repoId, $array->ExternalReference, $array->AgentID, $array->orderType);
				return [ 'message' => 'SalesOrder', 'boolean' => false ];
			}
			else if($latest_action == 'CreateShipment' && $latest_method == 'POST' && $latest_status_code == 404){
				$this->create_shipment($array->OrderRefNbr, $array->warehouse, $array->orderType);
				return [ 'message' => 'CreateShipment', 'boolean' => false ];
			}
			else if($latest_action == 'SalesOrder' && $latest_method == 'GET' && $latest_status_code == 404){
				$this->getShipmentNumber($array->OrderRefNbr, $array->orderType);
				return [ 'message' => 'SalesOrder', 'boolean' => false ];
			}
			else if($latest_action == 'Shipment' && $latest_method == 'PUT' && $latest_status_code == 404){
				$this->update_and_unhold_shipment($array->ShipmentNbr, $array->desciption);
				return [ 'message' => 'Shipment', 'boolean' => false ];
			}
			else if($latest_action == 'ConfirmShipment' && $latest_method == 'POST' && $latest_status_code == 404){
				$this->confirm_shipment($array->ShipmentNbr);
				return [ 'message' => 'ConfirmShipment', 'boolean' => false ];
			}
			else if($latest_action == 'updateIN' && $latest_method == 'POST' && $latest_status_code == 404){
				$this->update_in_shipment($array->ShipmentNbr);
				return [ 'message' => 'updateIN', 'boolean' => false ];
			}
		}
		return [ 'message' => 'new transactiopn', 'boolean' => true ];
	}

	public function acumatica_logs($request, $method, $action, $status_code, $parameter, $response){

		$record = DB::table('acumatica_logs')
			->where('sold_units_id', '=', $this->sold_units_id)
			->whereRaw('UPPER(method) = UPPER(?)', [$method])
			->whereRaw('UPPER(action) = UPPER(?)', [$action])
			->first();

		if ($record === null) {
			acumatica_logs::create([
				'sold_units_id' => $this->sold_units_id,
				'request' => json_encode($request),
				'method' => strtoupper($method),
				'action' => $action,
				'status_code' => $status_code,
				'parameter' => json_encode($parameter),
				'response' => json_encode($response),
			]);
		} else {
			acumatica_logs::where('sold_units_id', $this->sold_units_id)
				->whereRaw('UPPER(method) = UPPER(?)', [$method])
				->whereRaw('UPPER(action) = UPPER(?)', [$action])
				->update([
					'request' => json_encode($request),
					'status_code' => $status_code,
					'parameter' => json_encode($parameter),
					'response' => json_encode($response),
					'attempt' => $record->attempt + 1,
				]);
		}

		return $record;
	}
}
