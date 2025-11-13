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
use App\Http\Controllers\api_v1\PhysicalInventoryDocController;
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

Route::post('login', [UserController::class, 'login']);
Route::get('generateReport/{formtype}/{id}/{src}', [ReportController::class, 'generateReport']);

Route::middleware('auth:sanctum')->group( function () {

	Route::group(['middleware' => ['inputSanitation']], function () {

			//Dashboard
			Route::get('sidebarNotif', [DashboardController::class, 'dashboardCounter']);
			Route::get('getSidebarNotif', [DashboardController::class, 'getSidebarNotif']);

			//user
			Route::get('userroles', [UserController::class, 'getRoles']);
			Route::post('register', [UserController::class, 'register'])
                ->middleware('check.permission:add,15');
			Route::get('users', [UserController::class, 'users']);
			Route::get('roles', [UserController::class, 'roles']);
			Route::post('updateUser/{id}', [UserController::class, 'updateUser'])
                ->middleware('check.permission:update,15');
			Route::post('createMatrix', [UserController::class, 'createApprovalMatrix']);
			Route::get('removeMatrix/{id}', [UserController::class, 'removeMatrix']);
			Route::get('getCurrentModule/{pagename}', [UserController::class, 'currentModule']);
			Route::get('getMyModules', [UserController::class, 'getAllModules']);
			Route::get('approverByPage/{moduleid}', [UserController::class, 'approverByPage']);
			Route::get('getAllNotification', [UserController::class, 'getAllNotification']);
			Route::get('resetPassword/{id}', [UserController::class, 'getResetPassword']);

			Route::post('changePassword', [UserController::class, 'changePassword']);
			Route::get('deactivateUser/{id}/{status}', [UserController::class, 'deactivateUser']);

			//brand
			Route::post('createBrand', [BrandController::class, 'createBrand'])
                ->middleware('check.permission:add,11');
			Route::get('brands', [BrandController::class, 'brands']);
			Route::post('updateBrand/{id}', [BrandController::class, 'updateBrand'])
                ->middleware('check.permission:update,11');

			//color
			Route::post('createColor', [ColorController::class, 'createColor'])
                ->middleware('check.permission:add,13');
			Route::get('colors', [ColorController::class, 'colors']);
			Route::post('updateColor/{id}', [ColorController::class, 'updateColor'])
                ->middleware('check.permission:update,13');

			//model
			Route::post('createModel', [ModelController::class, 'createModel'])
                ->middleware('check.permission:add,12');
			Route::get('models', [ModelController::class, 'models']);
			Route::post('updateModel/{id}', [ModelController::class, 'updateModel'])
                ->middleware('check.permission:update,12');
			Route::get('modelPerBrand/{id}', [ModelController::class, 'modelPerBrand']);
			Route::get('getMapColor', [ModelController::class, 'mapColors']);

			//partslistForSalesTagging
			Route::post('createParts', [PartsController::class, 'createParts'])
                ->middleware('check.permission:add,14');
			Route::get('parts', [PartsController::class, 'parts']);
			Route::post('updateParts/{id}', [PartsController::class, 'updateParts'])
                ->middleware('check.permission:update,14');
			Route::get('partsPerModel', [PartsController::class, 'partsPerModel']);
			Route::get('partsPrice/{parts_id}', [PartsController::class, 'partsPrice']);
			Route::get('deactivateParts/{id}/{status}', [PartsController::class, 'deactivateParts']);

			//parts
			Route::post('mapAging', [AgingController::class, 'mapAging'])
                ->middleware('check.permission:add,34');
			Route::get('getAging', [AgingController::class, 'getAging']);
			Route::post('updateAging/{id}', [AgingController::class, 'updateAging'])
                ->middleware('check.permission:update,34');

			//branch
			Route::post('createBranch', [BranchController::class, 'createBranch'])
                ->middleware('check.permission:add,10');
			Route::get('branches', [BranchController::class, 'branches']);
			Route::post('updateBranch/{id}', [BranchController::class, 'updateBranch'])
                ->middleware('check.permission:update,10');
			Route::get('deactivateBranch/{id}/{status}', [BranchController::class, 'deactivateBranch']);
			Route::post('createLocation', [BranchController::class, 'createLocation'])
                ->middleware('check.permission:add,34');
			Route::post('updateLocation/{id}', [BranchController::class, 'updateLocation'])
                ->middleware('check.permission:update,34');
			Route::get('locationList', [BranchController::class, 'locationList']);
			Route::get('deactivateLocation/{id}/{status}', [BranchController::class, 'deactivateLocation']);

			//customer profile
			Route::post('createCustomerProfile', [CustomerProfileController::class, 'createCustomerProfile'])
                ->middleware('check.permission:add,2');
			Route::get('customerProfile', [CustomerProfileController::class, 'customerProfile']);
			Route::get('listOfCustomer', [CustomerProfileController::class, 'listOfCustomer']);
			Route::post('updateCustomerProfile/{id}', [CustomerProfileController::class, 'updateCustomerProfile'])
                ->middleware('check.permission:update,2');
			Route::get('customerProfilePerId/{id}', [CustomerProfileController::class, 'customerProfilePerId']);
			//customer address
			Route::get('provinceList', [CustomerProfileController::class, 'provinceList']);
			Route::get('cityList/{provinceId}', [CustomerProfileController::class, 'cityList']);
			Route::get('brgyList/{cityId}', [CustomerProfileController::class, 'brgyList']);
			Route::get('customer/source_of_income', [CustomerProfileController::class, 'source_of_income']);
			Route::get('customer/nationality', [CustomerProfileController::class, 'nationality']);

			//repo detailsallReceivedUnit
			Route::post('createRepo', [RepoController::class, 'createRepo'])
                ->middleware('check.permission:add,3');
			Route::get('repo', [RepoController::class, 'repo']);
			Route::get('repoDetailsPerId/{id}/{moduleid}', [RepoController::class, 'repoDetailsPerId']);
			Route::get('list_of_files', [RepoController::class, 'list_of_files']);
			Route::get('list_of_location', [RepoController::class, 'list_of_location']);
			Route::get('repoDeleteFiles/{deleted_id}', [RepoController::class, 'repoDeleteFiles']);
			Route::post('updateRepo/{id}', [RepoController::class, 'updateRepo'])
                ->middleware('check.permission:update,3');
			Route::get('fetch_repo_approval/{moduleid}', [RepoController::class, 'fetch_repo_approval']);
			Route::post('repo_approver_decision', [RepoController::class, 'repo_approver_decision']);
			Route::post('redemption', [RepoController::class, 'redemption']);

			//receive unit
			Route::post('createReceiveUnit', [ReceiveUnitController::class, 'createReceiveUnit']);
			Route::get('receivedUnits', [ReceiveUnitController::class, 'receivedUnits']);
			Route::post('receivedUnitsPerId', [ReceiveUnitController::class, 'receivedUnitsPerId']);
			Route::post('updateReceiveUnit/{id}', [ReceiveUnitController::class, 'updateReceiveUnit']);
			Route::get('repoDeleteParts/{deleted_id}', [ReceiveUnitController::class, 'repoDeleteParts']);

			//apraisal
			Route::post('requestRepoPrice', [RequestApprovalController::class, 'requestRepoPriceApproval'])
                ->middleware('check.permission:add,6');
			Route::get('repoSuggestedPrice/{modelid}/{datesold}', [RequestApprovalController::class, 'calculateSuggestedPrice']);
			Route::get('allReceivedUnit/{moduleid}', [RequestApprovalController::class, 'getAllReceivedUnit']);
			Route::post('submitDecision', [RequestApprovalController::class, 'submitRequestDecision']);
			Route::get('listReceivedUnit', [RequestApprovalController::class, 'listReceivedUnit']);
			Route::get('appraisalActivityLog/{requestid}', [RequestApprovalController::class, 'appraisalActivityLog']);
			Route::get('appraisalHistory', [RequestApprovalController::class, 'appraisalHistory']);

			//inventory
			Route::get('InventoryMasterList', [RequestApprovalController::class, 'UnitInventoryMasterList']);
			Route::get('SoldMasterList', [RequestApprovalController::class, 'SoldUnitMasterList']);
            Route::get('getAllSoldUnits', [RequestApprovalController::class, 'getAllSoldUnits']);
			Route::get('appraisedUnitList', [RequestApprovalController::class, 'appraisedUnitList']);
			Route::get('getListForApproval/{moduleid}', [RequestApprovalController::class, 'getListForApproval']);
			Route::get('UnitHistory/{repo_id}', [RequestApprovalController::class, 'UnitHistory']);

			//Refurbish
			Route::get('refurbishUnitList', [RequestRefurbishController::class, 'refurbishUnitList']);
			Route::get('listOfForRefurbish', [RequestRefurbishController::class, 'listOfForRefurbish']);
			Route::get('getMissingDamageParts/{received_id}', [RequestRefurbishController::class, 'getMissingDamageParts']);
			Route::get('getPartsForRefurbish', [RequestRefurbishController::class, 'getPartsForRefurbish']);
			Route::get('getRefurbishPartsTotalCost/{repo_id}', [RequestRefurbishController::class, 'getRefurbishPartsTotalCost']);
			Route::post('requestRefurbish', [RequestRefurbishController::class, 'requestRefurbish'])
                ->middleware('check.permission:add,23');
			Route::post('updateRefurbish/{id}', [RequestRefurbishController::class, 'updateRefurbish'])
                ->middleware('check.permission:update,23');
			Route::post('proceedRefurbish', [RequestRefurbishController::class, 'proceedRefurbish'])
                ->middleware('check.permission:add,26');
			Route::post('updateRefurbishProcess/{id}', [RequestRefurbishController::class, 'updateRefurbishProcess'])
                ->middleware('check.permission:update,26');
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
			Route::get('exportTransfersWithUnits', [StockTransferContoller::class, 'exportTransfersWithUnits']);
			Route::post('createStockTransfer', [StockTransferContoller::class, 'createStockTransfer'])
                ->middleware('check.permission:add,5');
			Route::post('transfer/submitApproverDecision', [StockTransferContoller::class, 'submitApproverDecision']);

			Route::get('getAllReceiveStockTransfer', [StockTransferContoller::class, 'getAllReceiveStockTransfer']);
			Route::post('getAllFileUploaded', [StockTransferContoller::class, 'getAllFileUploaded']);
			Route::post('receivedDesicion', [StockTransferContoller::class, 'receivedDesicion'])
                ->middleware('check.permission:add,22');

			Route::get('getTransferredUnits', [StockTransferContoller::class, 'getTransferredUnits']);
			Route::get('getComparisionSpareParts', [StockTransferContoller::class, 'getComparisionSpareParts']);
			Route::get('fetch_stock_transfer_approved', [StockTransferContoller::class, 'fetch_stock_transfer_approved']);

			//tag unit
			Route::get('listForSalesTagging', [RequestApprovalController::class, 'listForSalesTagging']);
			Route::post('tagUnit', [RequestApprovalController::class, 'tagUnitSale'])
                ->middleware('check.permission:add,19');
			Route::post('submitTagUnitDecision', [RequestApprovalController::class, 'submitTagUnitDecision']);
			Route::post('updateSaleTagging', [RequestApprovalController::class, 'updateSaleTagging']);
			Route::post('cancelSalesTag', [RequestApprovalController::class, 'cancelSalesTag']);
			Route::get('agentList', [RequestApprovalController::class, 'agentList']);

			// user role
			Route::get('userRole', [UserRoleController::class, 'userRole']);
			Route::post('createUserRole', [UserRoleController::class, 'createUserRole'])
                ->middleware('check.permission:add,16');
			Route::post('updateUserRole/{id}', [UserRoleController::class, 'updateUserRole'])
                ->middleware('check.permission:update,16');

			// system_menu
			Route::get('menu', [SystemMenuController::class, 'menu']);
			Route::get('menuList/{user_role_id}', [SystemMenuController::class, 'menuList']);
			Route::post('createSystemMenu', [SystemMenuController::class, 'createSystemMenu'])
                ->middleware('check.permission:add,17');
			Route::post('createMenuMapping', [SystemMenuController::class, 'createMenuMapping']);
			Route::post('updateSystemMenu/{id}', [SystemMenuController::class, 'updateSystemMenu'])
                ->middleware('check.permission:update,17');
			Route::get('getUserMenus/{userId}/{roleId}', [SystemMenuController::class, 'getUserMenus']);
			Route::post('saveUserAccess', [SystemMenuController::class, 'saveUserAccess']);

			// filename
			Route::get('files', [AccessFileController::class, 'files']);
			Route::post('createFileUpload', [AccessFileController::class, 'createFileUpload'])
                ->middleware('check.permission:add,21');
			Route::post('updateFileUpload/{id}', [AccessFileController::class, 'updateFileUpload'])
                ->middleware('check.permission:update,21');

            // physical inventory documents
            Route::post('createPhysicalInventoryDoc', [PhysicalInventoryDocController::class, 'createPhysicalInventoryDoc'])
                ->middleware('check.permission:add,38');
            Route::get('getPhysicalInventoryDocs', [PhysicalInventoryDocController::class, 'getPhysicalInventoryDocs']);
            Route::get('getPhysicalInventoryFiles', [PhysicalInventoryDocController::class, 'getPhysicalInventoryFiles']);
            Route::post('downloadPhysicalInventory', [PhysicalInventoryDocController::class, 'downloadPhysicalInventory']);
            Route::post('submitApproverDecision', [PhysicalInventoryDocController::class, 'submitApproverDecision']);
	});
});
