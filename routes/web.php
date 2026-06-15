<?php
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\HomeController;
use App\Models\Subscription;
use App\Http\Controllers\Api\GlobalSettingController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Route::get('/', 'HomeController@index')->name('home');

// Authentication routes




// Routes for the admin dashboard (Voyager)
Route::domain('admin.ezestimater.com')->group(function () {
    Route::group(['prefix' => 'dashboard'], function () {
        Voyager::routes();
    });
    // Any admin-only web routes
    Route::get('/get-revenue-data', [HomeController::class, 'getRevenueData']);
    Route::get('/get-subscription-count', [HomeController::class, 'getSubscriptionCount']);
    Route::get('/contractors/filter', [HomeController::class, 'getContractors'])->name('contractors.filter');
    Route::get('/contractors/filterchart', [HomeController::class, 'getContractorsChart'])->name('contractors.filterchart');

});

// Routes for the public site (api.ezestimater.com)
Route::domain('api.ezestimater.com')->group(function () {
    Route::get('/payment', function () {
        return view('payment-test');
    });

    Route::get('/privacy-policy', function () {
        return view('privacy-policy');
    });

    Route::get('/terms-and-condtions', function () {
        return view('terms-and-condtions');
    });

    Route::get('/preview-email-verification', function () {
        return view('emails.emailVerificationEmail');
    });

    Route::get('/preview-password-verification', function () {
        return view('emails.forgotPasswordVerificationEmail');
    });

    Auth::routes(); // Login, Register, etc.
});

if (App::environment('local')) {
    Route::get('/', function () {
        return redirect('/login');
    });

    Auth::routes();

    Route::group(['prefix' => 'dashboard'], function () {
        Voyager::routes();
    });
}


// Route::get('/dashboard', 'HomeController@index')->name('dashboard');
// Route::get('/logout', 'HomeController@logout');


// /*Route::group(['prefix'=>'/module','as'=>'module.'],function (){
//     Route::group(['prefix'=>'/vendors','as'=>'vendors.'],function (){
//         Route::get('/','Modules\Vendors\VendorsController@index')->name('home');

//         Route::get('/add','Modules\Vendors\VendorsController@add')->name('add');
//         Route::post('/','Modules\Vendors\VendorsController@create')->name('create');

//         Route::get('/{id}','Modules\Vendors\VendorsController@edit')->name('edit');
//         Route::put('/','Modules\Vendors\VendorsController@update')->name('update');

//         Route::delete('/{id}','Modules\Vendors\VendorsController@delete')->name('delete');
//     });
// });*/
// Route::get('storage/app/{dir}/{filename}', function ($dir,$filename)
// {
//     $path = storage_path('app/'.$dir.'/' . $filename);
//     /*
//     if (!File::exists($path)) {
//         abort(404);
//     }

//     $file = File::get($path);
//     $type = File::mimeType($path);

//     $response = Response::make($file, 200);
//     $response->header("Content-Type", $type);
//     */
//     return response()->file($path);
// });

function createRoutes($moduleName,$routePath = ""){
    $routePath = empty($routePath) ? $moduleName : $routePath;
    return Route::group(['prefix'=>'/'.$routePath,'as'=>$moduleName.'.'],function () use($moduleName){
        Route::get('/','Modules\\'.ucfirst($moduleName).'\\'.ucfirst($moduleName).'Controller@index')->name('home');
        Route::get('/get','Modules\\'.ucfirst($moduleName).'\\'.ucfirst($moduleName).'Controller@datatable')->name('datatable');
        Route::get('/add','Modules\\'.ucfirst($moduleName).'\\'.ucfirst($moduleName).'Controller@add')->name('add');
        Route::post('/','Modules\\'.ucfirst($moduleName).'\\'.ucfirst($moduleName).'Controller@create')->name('create');

        Route::get('/{id}','Modules\\'.ucfirst($moduleName).'\\'.ucfirst($moduleName).'Controller@edit')->name('edit');
        Route::put('/','Modules\\'.ucfirst($moduleName).'\\'.ucfirst($moduleName).'Controller@update')->name('update');

        Route::delete('/{id}','Modules\\'.ucfirst($moduleName).'\\'.ucfirst($moduleName).'Controller@delete')->name('delete');
        Route::delete('/{id}/{field}','Modules\\'.ucfirst($moduleName).'\\'.ucfirst($moduleName).'Controller@deleteFile')->name('deleteFile');
        Route::put('/{id}/{field}','Modules\\'.ucfirst($moduleName).'\\'.ucfirst($moduleName).'Controller@status')->name('status');
    });
}
// function createOrderRoutes($moduleName,$routePath = ""){
//     $routePath = empty($routePath) ? $moduleName : $routePath;
//     return Route::group(['prefix'=>'/'.$routePath,'as'=>$moduleName.'.'],function () use($moduleName){
//         Route::get('/','Modules\\'.ucfirst($moduleName).'\\'.ucfirst($moduleName).'Controller@index')->name('home');
//         Route::get('/get','Modules\\'.ucfirst($moduleName).'\\'.ucfirst($moduleName).'Controller@datatable')->name('datatable');
//         Route::get('/add','Modules\\'.ucfirst($moduleName).'\\'.ucfirst($moduleName).'Controller@add')->name('add');
//         Route::post('/','Modules\\'.ucfirst($moduleName).'\\'.ucfirst($moduleName).'Controller@create')->name('create');

//         Route::get('/{id}','Modules\\'.ucfirst($moduleName).'\\'.ucfirst($moduleName).'Controller@viewOrder')->name('viewOrder');
//         Route::get('/invoice/{id}','Modules\\'.ucfirst($moduleName).'\\'.ucfirst($moduleName).'Controller@invoice')->name('invoice');

//         Route::delete('/{id}','Modules\\'.ucfirst($moduleName).'\\'.ucfirst($moduleName).'Controller@delete')->name('delete');
//         Route::put('/{id}/{field}','Modules\\'.ucfirst($moduleName).'\\'.ucfirst($moduleName).'Controller@status')->name('status');
//     });
// }
// function createReportRoutes($controller,$routePath = ""){
//     $moduleName = 'reports';
//     $routePath = empty($routePath) ? $controller : $routePath;
//     return Route::group(['prefix'=>'/'.$routePath,'as'=>$controller.'.'],function () use($moduleName,$controller){
//         Route::get('/','Modules\\'.ucfirst($moduleName).'\\'.$controller.'@index')->name('home');
//         Route::get('/get','Modules\\'.ucfirst($moduleName).'\\'.$controller.'@datatable')->name('datatable');
//         Route::post('/','Modules\\'.ucfirst($moduleName).'\\'.$controller.'@search')->name('home.search');
//     });
// }
// function createAccountRoutes($moduleName,$routePath = ""){
//     $routePath = empty($routePath) ? $moduleName : $routePath;
//     return Route::group(['prefix'=>'/'.$routePath,'as'=>$moduleName.'.'],function () use ($moduleName){
//         Route::get('/','Modules\\'.ucfirst($moduleName).'\\'.ucfirst($moduleName).'Controller@index')->name('home');
//         Route::get('/get','Modules\\'.ucfirst($moduleName).'\\'.ucfirst($moduleName).'Controller@datatable')->name('datatable');
//         Route::get('/add','Modules\\'.ucfirst($moduleName).'\\'.ucfirst($moduleName).'Controller@add')->name('add');
//         Route::post('/','Modules\\'.ucfirst($moduleName).'\\'.ucfirst($moduleName).'Controller@create')->name('create');
//         Route::post('/search','Modules\\'.ucfirst($moduleName).'\\'.ucfirst($moduleName).'Controller@search')->name('home.search');
//     });
// }

Route::group(['prefix'=>'/','as'=>'module.'],function (){
    createRoutes('cards');
    createRoutes('contentPages');
    //createRoutes('contractor', 'contractor');
    createRoutes('pushNotification');
    //Route::get("/notifications", 'Modules\Notification\NotificationController@index')->name("notification.home");

    // Route::get("/contractor/suppliers/{userId}", 'Modules\Contractor\ContractorController@suppliers')->name("contractor.suppliers");
    // Route::get("/contractor/customers/{userId}", 'Modules\Contractor\ContractorController@customers')->name("contractor.customers");
    // Route::get("/contractor/estimates/{userId}", 'Modules\Contractor\ContractorController@estimates')->name("contractor.estimates");
    // Route::get("/contractor/estimates/report/{userId}/{estimateId}", 'Modules\Contractor\ContractorController@estimate_report')->name("contractor.estimate.report");
    // Route::get("/contractor/estimates/{userId}/{estimateId}", 'Modules\Contractor\ContractorController@estimate_edit')->name("contractor.estimate.edit");
    // Route::put("/contractor/estimates/{userId}/{estimateId}", 'Modules\Contractor\ContractorController@estimate_update')->name("contractor.estimate.update");
    // Route::put("/contractor/estimate/code/{userId}/{codeId}", 'Modules\Contractor\ContractorController@estimate_code_update')->name("contractor.estimate.code_update");
    // Route::delete("/contractor/estimates/{userId}/{estimateId}", 'Modules\Contractor\ContractorController@estimate_delete')->name("contractor.estimate.delete");
    // Route::delete("/contractor/estimates/sheet/item/delete/{userId}/{estimateId}", 'Modules\Contractor\ContractorController@estimate_sheet_item_delete')->name("contractor.estimate.sheet.item.delete");
    //createRoutes('content_pages');
    /*createRoutes('inventory');
    createRoutes('companySettings');
    createRoutes('profileSettings');
    createRoutes('companies');
    createRoutes('users');*/
});



// Route::group(['prefix'=>'/contractor','as'=>'module.'],function () {
//     //createRoutes('contractor', 'contractor');


// });

/*Route::prefix('/contractors')->group(function(){
    Route::get('/','Modules\contractor\ContractorController@index')->name('module.contractor.home');
    Route::get('/','Modules\contractor\ContractorController@datatable')->name('module.contractor.datatable');
});*/

/*Route::group(['prefix'=>'/accounts','as'=>'module.'],function () {
    createAccountRoutes('suppliersAccount','Supplier Account');
    createAccountRoutes('customersAccount','Customer Account');
});

Route::group(['prefix'=>'/expenses','as'=>'module.'],function () {
    createRoutes('expenseCategories','Categories');
    createRoutes('expenses','History');
});

Route::group(['prefix'=>'/purchases','as'=>'module.'],function (){
    createOrderRoutes('purchaseOrders');
});

Route::group(['prefix'=>'/orders','as'=>'module.'],function (){
    createOrderRoutes('saleOrders','Sales Orders');
});

Route::group(['prefix'=>'/products','as'=>'module.'],function (){
    createRoutes('categories');
    createRoutes('items','listing');
});*/

/*Route::group(['prefix'=>'/reports','as'=>'module.'],function (){
    createReportRoutes('PurchaseOrderReport','Purchase Order Report');
    createReportRoutes('SaleOrderReport','Sales Order Report');
    createReportRoutes('ExpenseReport','Expense Report');
    createReportRoutes('ProfitLossReport','Profit & Loss Report');
});*/

Route::get('/admin/get-user-subscriptions/{id}', function ($id) {
     $subs = Subscription::where('user_id', $id)->get();
    return view('admin.subscription_history', compact('subs'));
});


 ////////////////////////// Document signing routes for public access (no auth) - these are the links that go in the email to the signer
 
Route::get('/document/sign/{token}', [GlobalSettingController::class, 'showSignDocument']);
Route::post('/document/sign/{token}', [GlobalSettingController::class, 'submitSignature']);
Route::get('/document/sign/{token}/file/{document}', [GlobalSettingController::class, 'viewSigningSourceDocument'])
    ->name('document.sign.file');
Route::get('/document/sign/{token}/preview/{document}', [GlobalSettingController::class, 'previewSigningDocument'])
    ->name('document.sign.preview');
Route::get('/document/sign/{token}/view', [GlobalSettingController::class, 'viewSignedDocument']);
Route::get('/document/sign/{token}/download', [GlobalSettingController::class, 'downloadSignedDocument']);
Route::get('/storage/{path}', [GlobalSettingController::class, 'servePublicStorageFile'])
    ->where('path', '.*');

