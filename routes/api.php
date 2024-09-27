<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\api_v1\UserController;
use App\Http\Controllers\api_v1\BrandController;
use App\Http\Controllers\api_v1\ColorController;
use App\Http\Controllers\api_v1\ModelController;
use App\Http\Controllers\api_v1\PartsController;
use App\Http\Controllers\api_v1\BranchController;
use App\Http\Controllers\api_v1\AgingController;
use App\Http\Controllers\api_v1\CustomerProfileController;
use App\Http\Controllers\api_v1\RepoController;
use App\Http\Controllers\api_v1\ReceiveUnitController;
use App\Http\Controllers\api_v1\RequestApprovalController;
use App\Http\Controllers\api_v1\StockTransferContoller;
use App\Http\Controllers\api_v1\DashboardController;
use App\Http\Controllers\api_v1\UserRoleController;
use App\Http\Controllers\api_v1\SystemMenuController;
use App\Http\Controllers\api_v1\AccessFileController;
use App\Http\Controllers\api_v1\ReportController;
use App\Http\Controllers\api_v1\RequestRefurbishController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });



// Route::group(['middleware' => ['web']], function () {
// //generate csrf token in able to use it in sending request
//    Route::get('get_csrf',function(){
//       return response()->json(['csrf_token' => csrf_token()]);
//    });

// });


Route::post('login', [UserController::class, 'login']);
Route::get('generateReport/{formtype}/{id}/{src}', [ReportController::class, 'generateReport']);
// Route::get('checking', [RequestApprovalController::class, 'checking']);

Route::middleware('auth:sanctum')->group( function () {

	Route::group(['middleware' => ['inputSanitation']], function () {

			//Dashboard
			Route::get('sidebarNotif', [DashboardController::class, 'dashboardCounter']);
			Route::get('getSidebarNotif', [DashboardController::class, 'getSidebarNotif']);

			//user
			Route::get('userroles', [UserController::class, 'getRoles']);
			Route::post('register', [UserController::class, 'register']);
			Route::get('users', [UserController::class, 'users']);
			Route::post('updateUser/{id}', [UserController::class, 'updateUser']);
			Route::post('createMatrix', [UserController::class, 'createApprovalMatrix']);
			Route::get('removeMatrix/{id}', [UserController::class, 'removeMatrix']);
			Route::get('getCurrentModule/{pagename}', [UserController::class, 'currentModule']);
			Route::get('getMyModules', [UserController::class, 'getAllModules']);
			Route::get('approverByPage/{moduleid}', [UserController::class, 'approverByPage']);
			Route::get('getAllNotification', [UserController::class, 'getAllNotification']);

			Route::post('changePassword', [UserController::class, 'changePassword']);
			Route::get('deactivateUser/{id}/{status}', [UserController::class, 'deactivateUser']);

			//brand
			Route::post('createBrand', [BrandController::class, 'createBrand']);
			Route::get('brands', [BrandController::class, 'brands']);
			Route::post('updateBrand/{id}', [BrandController::class, 'updateBrand']);

			//color
			Route::post('createColor', [ColorController::class, 'createColor']);
			Route::get('colors', [ColorController::class, 'colors']);
			Route::post('updateColor/{id}', [ColorController::class, 'updateColor']);

			//model
			Route::post('createModel', [ModelController::class, 'createModel']);
			Route::get('models', [ModelController::class, 'models']);
			Route::post('updateModel/{id}', [ModelController::class, 'updateModel']);
			Route::get('modelPerBrand/{id}', [ModelController::class, 'modelPerBrand']);
			Route::get('getMapColor', [ModelController::class, 'mapColors']);

			//partslistForSalesTagging
			Route::post('createParts', [PartsController::class, 'createParts']);
			Route::get('parts', [PartsController::class, 'parts']);
			Route::post('updateParts/{id}', [PartsController::class, 'updateParts']);
			Route::get('partsPerModel', [PartsController::class, 'partsPerModel']);
			Route::get('partsPrice/{parts_id}', [PartsController::class, 'partsPrice']);
			Route::get('deactivateParts/{id}/{status}', [PartsController::class, 'deactivateParts']);

			//parts
			Route::post('mapAging', [AgingController::class, 'mapAging']);
			Route::get('getAging', [AgingController::class, 'getAging']);
			Route::post('updateAging/{id}', [AgingController::class, 'updateAging']);

			//branch
			Route::post('createBranch', [BranchController::class, 'createBranch']);
			Route::get('branches', [BranchController::class, 'branches']);
			Route::post('updateBranch/{id}', [BranchController::class, 'updateBranch']);
			Route::get('deactivateBranch/{id}/{status}', [BranchController::class, 'deactivateBranch']);
			Route::post('createLocation', [BranchController::class, 'createLocation']);
			Route::post('updateLocation/{id}', [BranchController::class, 'updateLocation']);
			Route::get('locationList', [BranchController::class, 'locationList']);
			Route::get('deactivateLocation/{id}/{status}', [BranchController::class, 'deactivateLocation']);

			//customer profile
			Route::post('createCustomerProfile', [CustomerProfileController::class, 'createCustomerProfile']);
			Route::get('customerProfile', [CustomerProfileController::class, 'customerProfile']);
			Route::get('listOfCustomer', [CustomerProfileController::class, 'listOfCustomer']);
			Route::post('updateCustomerProfile/{id}', [CustomerProfileController::class, 'updateCustomerProfile']);
			Route::get('customerProfilePerId/{id}', [CustomerProfileController::class, 'customerProfilePerId']);
			//customer address
			Route::get('provinceList', [CustomerProfileController::class, 'provinceList']);
			Route::get('cityList/{provinceId}', [CustomerProfileController::class, 'cityList']);
			Route::get('brgyList/{cityId}', [CustomerProfileController::class, 'brgyList']);
			Route::get('customer/source_of_income', [CustomerProfileController::class, 'source_of_income']);
			Route::get('customer/nationality', [CustomerProfileController::class, 'nationality']);

			//repo detailsallReceivedUnit
			Route::post('createRepo', [RepoController::class, 'createRepo']);
			Route::get('repo', [RepoController::class, 'repo']);
			Route::get('repoDetailsPerId/{id}/{moduleid}', [RepoController::class, 'repoDetailsPerId']);
			Route::get('list_of_files', [RepoController::class, 'list_of_files']);
			Route::get('list_of_location', [RepoController::class, 'list_of_location']);
			Route::get('repoDeleteFiles/{deleted_id}', [RepoController::class, 'repoDeleteFiles']);
			Route::post('updateRepo/{id}', [RepoController::class, 'updateRepo']);
			Route::get('fetch_repo_approval/{moduleid}', [RepoController::class, 'fetch_repo_approval']);
			Route::post('repo_approver_decision', [RepoController::class, 'repo_approver_decision']);

			//receive unit
			Route::post('createReceiveUnit', [ReceiveUnitController::class, 'createReceiveUnit']);
			Route::get('receivedUnits', [ReceiveUnitController::class, 'receivedUnits']);
			Route::post('receivedUnitsPerId', [ReceiveUnitController::class, 'receivedUnitsPerId']);
			Route::post('updateReceiveUnit/{id}', [ReceiveUnitController::class, 'updateReceiveUnit']);
			Route::get('repoDeleteParts/{deleted_id}', [ReceiveUnitController::class, 'repoDeleteParts']);

			//apraisal
			Route::post('requestRepoPrice', [RequestApprovalController::class, 'requestRepoPriceApproval']);
			Route::get('repoSuggestedPrice/{modelid}/{datesold}', [RequestApprovalController::class, 'calculateSuggestedPrice']);
			Route::get('allReceivedUnit/{moduleid}', [RequestApprovalController::class, 'getAllReceivedUnit']);
			Route::post('submitDecision', [RequestApprovalController::class, 'submitRequestDecision']);
			Route::get('listReceivedUnit', [RequestApprovalController::class, 'listReceivedUnit']);
			Route::get('appraisalActivityLog/{requestid}', [RequestApprovalController::class, 'appraisalActivityLog']);
			Route::get('appraisalHistory', [RequestApprovalController::class, 'appraisalHistory']);

			//inventory
			Route::get('InventoryMasterList', [RequestApprovalController::class, 'UnitInventoryMasterList']);
			Route::get('SoldMasterList', [RequestApprovalController::class, 'SoldUnitMasterList']);
			Route::get('appraisedUnitList', [RequestApprovalController::class, 'appraisedUnitList']);
			Route::get('getListForApproval/{moduleid}', [RequestApprovalController::class, 'getListForApproval']);
			Route::get('UnitHistory/{repo_id}', [RequestApprovalController::class, 'UnitHistory']);

			//Refurbish
			Route::get('refurbishUnitList', [RequestRefurbishController::class, 'refurbishUnitList']);
			Route::get('listOfForRefurbish', [RequestRefurbishController::class, 'listOfForRefurbish']);
			Route::get('getMissingDamageParts/{received_id}', [RequestRefurbishController::class, 'getMissingDamageParts']);
			Route::get('getPartsForRefurbish', [RequestRefurbishController::class, 'getPartsForRefurbish']);
			Route::get('getRefurbishPartsTotalCost/{repo_id}', [RequestRefurbishController::class, 'getRefurbishPartsTotalCost']);
			Route::post('requestRefurbish', [RequestRefurbishController::class, 'requestRefurbish']);
			Route::post('updateRefurbish/{id}', [RequestRefurbishController::class, 'updateRefurbish']);
			Route::post('proceedRefurbish', [RequestRefurbishController::class, 'proceedRefurbish']);
			Route::post('updateRefurbishProcess/{id}', [RequestRefurbishController::class, 'updateRefurbishProcess']);
			Route::post('cancelRefurbish', [RequestRefurbishController::class, 'cancelRefurbish']);
			Route::get('getListForApprovalRefurbish/{module}', [RequestRefurbishController::class, 'getListForApprovalRefurbish']);
			Route::get('getListForRefurbishProcess/{module}', [RequestRefurbishController::class, 'listForRefurbishProcess']);
			Route::get('getRefurbishParts/{repo_id}', [RequestRefurbishController::class, 'getRefurbishParts']);
			Route::get('getUploadedDocuments/{refurbish_id}', [RequestRefurbishController::class, 'getUploadedDocuments']);
			Route::post('refurbishDecision', [RequestRefurbishController::class, 'refurbishDecision']);
			Route::post('refurbishProcessDecision', [RequestRefurbishController::class, 'refurbishProcessDecision']);
			Route::get('settledRefurbishAccounting', [RequestRefurbishController::class, 'settledRefurbishAccounting']);


			// stock transfer
			Route::get('modelList', [StockTransferContoller::class, 'ModelList']);
			Route::get('branchesList', [StockTransferContoller::class, 'branchesList']);
			Route::get('getAllForApprovals/{moduleid}', [StockTransferContoller::class, 'getAllForApprovals']);
			Route::get('getTransferUnits/{id}', [StockTransferContoller::class, 'getTransferUnits']);
			Route::post('createStockTransfer', [StockTransferContoller::class, 'createStockTransfer']);
			Route::post('submitApproverDecision', [StockTransferContoller::class, 'submitApproverDecision']);

			Route::get('getAllReceiveStockTransfer', [StockTransferContoller::class, 'getAllReceiveStockTransfer']);
			Route::post('getAllFileUploaded', [StockTransferContoller::class, 'getAllFileUploaded']);
			Route::post('receivedDesicion', [StockTransferContoller::class, 'receivedDesicion']);

			Route::get('getTransferredUnits', [StockTransferContoller::class, 'getTransferredUnits']);
			Route::get('getComparisionSpareParts', [StockTransferContoller::class, 'getComparisionSpareParts']);
			Route::get('fetch_stock_transfer_approved', [StockTransferContoller::class, 'fetch_stock_transfer_approved']);

			//tag unit
			Route::get('listForSalesTagging', [RequestApprovalController::class, 'listForSalesTagging']);
			Route::post('tagUnit', [RequestApprovalController::class, 'tagUnitSale']);
			Route::post('submitTagUnitDecision', [RequestApprovalController::class, 'submitTagUnitDecision']);
			Route::post('updateSaleTagging', [RequestApprovalController::class, 'updateSaleTagging']);
			Route::post('cancelSalesTag', [RequestApprovalController::class, 'cancelSalesTag']);
			Route::get('agentList', [RequestApprovalController::class, 'agentList']);

			// user role
			Route::get('userRole', [UserRoleController::class, 'userRole']);
			Route::post('createUserRole', [UserRoleController::class, 'createUserRole']);
			Route::post('updateUserRole/{id}', [UserRoleController::class, 'updateUserRole']);

			// system_menu
			Route::get('menu', [SystemMenuController::class, 'menu']);
			Route::get('menuList/{user_role_id}', [SystemMenuController::class, 'menuList']);
			Route::post('createSystemMenu', [SystemMenuController::class, 'createSystemMenu']);
			Route::post('createMenuMapping', [SystemMenuController::class, 'createMenuMapping']);
			Route::post('updateSystemMenu/{id}', [SystemMenuController::class, 'updateSystemMenu']);

			// filename
			Route::get('files', [AccessFileController::class, 'files']);
			Route::post('createFileUpload', [AccessFileController::class, 'createFileUpload']);
			Route::post('updateFileUpload/{id}', [AccessFileController::class, 'updateFileUpload']);
	});
});
