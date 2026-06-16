<?php
ini_set('serialize_precision', -1);

use App\Http\Controllers\Api\NotificationController;
use App\Models\Estimate;
use App\Notifications\FirebasePushNotification;
use App\Services\FirebaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\User;
use App\Models\JobTypes;
use App\Models\State;
use Stripe\Stripe;


Route::post('/test-notification', function (){
    $user = User::find(168);
    $user->notify(new FirebasePushNotification('Notification Title AAA',
        'Notification Body AAA'));
});

/*Route::post("/test-stripe-event",function(Request $request){
    \Log::info("======================================================");
    \Log::info($request->all());
    \Log::info("======================================================");
    return response()->json([]);
});*/

Route::post("/test-stripe-event",[\App\Http\Controllers\Api\StripeWebhookController::class, 'webhook']);


Route::get('/stripe-test', function(Request $request){

    $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));
    //$stripe->products->create(['name' => 'Estimate test ID 1']); //"id": "prod_QUB8TV1TxQtGFP",

    $price = $stripe->prices->create([
        'currency' => 'usd',
        'unit_amount' => (60 * 100),
        'product' => 'prod_QUB8TV1TxQtGFP',
      ]);

    //dd($price);  // "id" => "price_1PdCvmItMIhfPIHRS4F0duXB"
    $linkk = $stripe->paymentLinks->create([
        'line_items' => [
          [
            'price' => $price->id,
            'quantity' => 1,
          ],
        ],
        'metadata' => [
            'estiamte_id' => '122'
          ]
      ]);

Route::post("/test-stripe-event",function(Request $request){
    \Log::info("======================================================");
    \Log::info($request->all());
    \Log::info("======================================================");
    return response()->json([]);
});





    dd($linkk); // "url" => "https://buy.stripe.com/test_14k8wCbFvcQHcww9AA"
//url: "https://buy.stripe.com/test_00gbIO38Zg2T5448wx"

    return response()->json($stripe->products->all(['limit' => 5]));
});

Route::get('/export-doc/material/{estimate}', [\App\Http\Controllers\Api\ExcelExportController::class, 'downloadMaterialList'])->name('export');
Route::get('/export-doc/material/supplier/{estimate}', [\App\Http\Controllers\Api\ExcelExportController::class, 'materialListSupplier'])->name('exportSupplier');
Route::get('/export-doc/estimate/{estimate}', [\App\Http\Controllers\Api\ExcelExportController::class, 'downloadEstimate']);
Route::get('/export-doc/codebook', [\App\Http\Controllers\Api\ExcelExportController::class, 'downloadCodeBook']);
Route::get('/export-doc/sales-report', [\App\Http\Controllers\Api\ExcelExportController::class, 'salesReports']);

Route::get('/downloadtest', [App\Http\Controllers\Api\TestController::class, 'downloadTest']);
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
//use Illuminate\Support\Facades\Mail;
//Mail::raw('This is a plain text email body.', function ($message) {
//    $message->to('recipient@example.com')
//        ->subject('Mail from Laravel');
//});
/*//dump(\App\Models\EstimateSheet::with("Code.ProductGroup")->where("estimate_id",5)->get()->toArray());
$data = [];

$data = \App\Models\EstimateSheet::with("Code.ProductGroup")->where("material_cost","!=", 0)
                                                            ->where("estimate_id",5)
                                                            ->get()
                                                            ->groupBy("Code.product_group_id")
                                                            ->toArray();
            /*->each(function($estimateSheet) use (&$data){

                if(!empty($estimateSheet->material_cost) || !empty($estimateSheet->misc_cost)) {
                    $data[] = [
                        "category" => optional($estimateSheet->code->ProductGroup)->group_name,
                        "type" => "Material",
                        "budget" => $estimateSheet->total_material_cost,
                        "actual" => $estimateSheet->actual_material_cost,
                        "difference" => $estimateSheet->total_material_cost - $estimateSheet->actual_material_cost
                    ];
                }

            });
dd($data);
$colData = collect($data);
dump("Total sum " , \App\Models\EstimateSheet::with("Code.ProductGroup")->where("estimate_id",5)->get()->sum("row_total"));
dd($colData->sum("budget"),$colData->sum("actual"),$colData->sum("difference"));

*/


/*Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});*/
Route::post('/test-notification', function (FirebaseService $firebaseService){
    $user = User::find(119);
    if ($user) {
        $user->notify(new FirebasePushNotification('Notification Title A', 'Notification Body A'));
        return response()->json(['success' => true]);
    }
});
Route::post('/login', [App\Http\Controllers\Api\LoginController::class, 'login']);
Route::prefix("on-boarding")->group(function(){
    Route::post('/user-registration', [App\Http\Controllers\Api\LoginController::class, 'signup']);
    Route::post('/user-email-verification', [App\Http\Controllers\Api\LoginController::class, 'register_email']);
    Route::post('/user-email-resend-verification-code', [App\Http\Controllers\Api\LoginController::class, 'verification_code_resend']);
    Route::post('/user-email-code-verification', [App\Http\Controllers\Api\LoginController::class, 'verify_code']);
    Route::post('/user-password-save', [App\Http\Controllers\Api\LoginController::class, 'save_password']);
});
Route::prefix('/forgot-password')->group(function(){
    Route::post('/', [App\Http\Controllers\Api\LoginController::class, 'forgot_password']);
    Route::post('/email-verify', [App\Http\Controllers\Api\LoginController::class, 'forget_email_verified']);
    Route::post('/change-password', [App\Http\Controllers\Api\LoginController::class, 'change_password']);
});
Route::get('/view-email', [App\Http\Controllers\Api\LoginController::class, 'emailPreview']);
Route::get('/tc', [App\Http\Controllers\Api\EstimateController::class, 'test_report']);
Route::get('/global-setting/upload-documents/{document}/preview', [App\Http\Controllers\Api\GlobalSettingController::class, 'preview_setting_document']);

Route::prefix("/general")->group(function(){
    Route::get("/", function (Request $request){
        $unitTypes = [
            "Sqft",
            "Ft",
            "Inch",
        ];
        $data = [
            "unit_types" => $unitTypes,
            "job_types" => JobTypes::where("status", "=", 1)->get(["id", "title"]),
            "states_cities" => State::stateCities(),
        ];

        return response()->json(["status"=>true, "message"=>"", "data" => $data]);
    });
    Route::get("/job-types", [App\Http\Controllers\Api\JobTypeController::class, 'index']);
    Route::get("/cities-and-states", [App\Http\Controllers\Api\CityStateController::class, 'index']);
    Route::get("/unit-types", function (Request $request){
        $unitTypes = [
            "Sqft",
            "Ft",
            "Inch",
        ];
       return response()->json(["status"=>true, "message"=>"", "data"=>$unitTypes]);
    });
});




  Route::group(['middleware' => ['auth:app-api', \App\Http\Middleware\UserAccessPermissionMiddleware::class]], function () {
    Route::prefix("/me")->group(function(){
        
        Route::get('/app-update', [App\Http\Controllers\Api\UserController::class, 'getLatestUpdate']);
        Route::get('/profile', [App\Http\Controllers\Api\UserController::class, 'profile']);
        Route::post('/delete-my-account', [App\Http\Controllers\Api\UserController::class, 'deleteMyAccount']);
        Route::post('/update', [App\Http\Controllers\Api\UserController::class, 'update']);
        Route::post('/invoice-setting', [App\Http\Controllers\Api\UserController::class, 'invoice_setting']);
        //Route::post('/global-setting', [App\Http\Controllers\Api\UserController::class, 'global_setting']);
        Route::post('/device-token-update', [App\Http\Controllers\Api\UserController::class, 'device_update']);
        Route::get('/stripe', [App\Http\Controllers\Api\UserController::class, 'stripeDetail']);

    });

    Route::post('/sales-report-email', [App\Http\Controllers\Api\EstimateController::class, 'salesReportEmail']);

    //Route::prefix("/users")->middleware(['users.permission:1'])->group(function(){
    Route::prefix("/users")->group(function(){
        
        Route::get('/permissions-module', [\App\Http\Controllers\Api\UserController::class, 'getPermissionsModules']);
        Route::get('/', [App\Http\Controllers\Api\UserController::class, 'companyUser']);
        Route::get('/edit/{user}', [App\Http\Controllers\Api\UserController::class, 'editCompanyUser']);
        Route::post('/create-user', [App\Http\Controllers\Api\UserController::class, 'createCompanyUser']);
        Route::post('/update/{user}', [App\Http\Controllers\Api\UserController::class, 'updateCompanyUser']);
        Route::post('/delete/{user}', [App\Http\Controllers\Api\UserController::class, 'deleteCompanyUser']);
        
    });

    Route::prefix("/global-setting")->group(function(){
        Route::get('/types', [App\Http\Controllers\Api\GlobalSettingController::class, 'types']);
        Route::get('/types/children/{parentId}', [App\Http\Controllers\Api\GlobalSettingController::class, 'fetchChildCategories']);
        Route::post('/user/update-annual-job-cost', [App\Http\Controllers\Api\GlobalSettingController::class, 'updateAnnualJobCost']);
        Route::get('/type/{settingType}', [App\Http\Controllers\Api\GlobalSettingController::class, 'settingOfType']);
        Route::post('/type/{settingType}', [App\Http\Controllers\Api\GlobalSettingController::class, 'addSetting']);
        Route::post('/type/{settingType}/reorder', [App\Http\Controllers\Api\GlobalSettingController::class, 'reorderSetting']);
        Route::post('/delete/{setting}', [App\Http\Controllers\Api\GlobalSettingController::class, 'delete']);
        Route::get('/setting/{setting}', [App\Http\Controllers\Api\GlobalSettingController::class, 'edit']);
        Route::post('/setting/{setting}', [App\Http\Controllers\Api\GlobalSettingController::class, 'update']);
        
         Route::post('/upload-documents', [App\Http\Controllers\Api\GlobalSettingController::class, 'upload_setting_documents']);
         Route::get('/upload-documents', [App\Http\Controllers\Api\GlobalSettingController::class, 'get_setting_documents']);
         Route::delete('/upload-documents/{document}', [App\Http\Controllers\Api\GlobalSettingController::class, 'delete_setting_document']);
        //  Route::get('/merge-documents', [App\Http\Controllers\Api\GlobalSettingController::class, 'mergeDocuments']);
         Route::put('/setting-documents/update', [App\Http\Controllers\Api\GlobalSettingController::class, 'update_setting_documents']);
         
        Route::post('/send-documents-email', [App\Http\Controllers\Api\GlobalSettingController::class, 'sendDocumentsByEmail']);

         
         

    });


    Route::prefix('/dashboard')->group(function(){
        Route::get('/', [App\Http\Controllers\Api\UserController::class, 'user_dashboard']);
        Route::get('home-cards', [App\Http\Controllers\Api\HomeCardController::class, 'index']);
    });
    Route::prefix('/supplier')->group(function (){
        Route::get('/', [App\Http\Controllers\Api\SupplierController::class, 'index']);
        Route::post('/create', [App\Http\Controllers\Api\SupplierController::class, 'create']);
        Route::get('/edit/{id}', [App\Http\Controllers\Api\SupplierController::class, 'edit']);
        Route::post('/update/{id}', [App\Http\Controllers\Api\SupplierController::class, 'update']);
        Route::delete('/delete/{id}', [App\Http\Controllers\Api\SupplierController::class, 'delete']);
        Route::get('/search', [App\Http\Controllers\Api\SupplierController::class, 'search']);
    });
    Route::prefix('/customer')->group(function (){
        Route::get('/', [App\Http\Controllers\Api\CustomerController::class, 'index']);
        Route::post('/create', [App\Http\Controllers\Api\CustomerController::class, 'create']);
        Route::post('/update-note', [App\Http\Controllers\Api\CustomerController::class, 'updateNote']);
        Route::get('/edit/{id}', [App\Http\Controllers\Api\CustomerController::class, 'edit']);
        Route::post('/update/{id}', [App\Http\Controllers\Api\CustomerController::class, 'update']);
        Route::delete('/delete/{id}', [App\Http\Controllers\Api\CustomerController::class, 'delete']);
        Route::get('/search', [App\Http\Controllers\Api\CustomerController::class, 'search']);
    });
    Route::prefix("/code")->group(function(){
        Route::get('/product-groups', [App\Http\Controllers\Api\ProductGroupController::class, 'index']);
        Route::get('/', [App\Http\Controllers\Api\CodeController::class, 'index']);
        Route::get('/check/supplier-support/{supplier}', [App\Http\Controllers\Api\CodeController::class, 'isSupplierSupport']);
        Route::get('/check/hd-support', [App\Http\Controllers\Api\CodeController::class, 'checkHomeDepotSupport']);
        Route::post('/create', [App\Http\Controllers\Api\CodeController::class, 'create']);//->middleware(['users.permission:1']);
        Route::get('/edit/{id}', [App\Http\Controllers\Api\CodeController::class, 'edit']);
        Route::post('/update/{id}', [App\Http\Controllers\Api\CodeController::class, 'update']);//->middleware(['users.permission:1']);
        Route::delete('/delete/{id}', [App\Http\Controllers\Api\CodeController::class, 'delete']);//->middleware(['users.permission:1']);
        //
        Route::get('/search', [App\Http\Controllers\Api\CodeController::class, 'search']);

    });
    Route::prefix("/template")->group(function(){
        Route::get("/", [App\Http\Controllers\Api\TemplateController::class, "index"]);
        Route::get('/allow/creation', [App\Http\Controllers\Api\TemplateController::class, 'checkTemplateCreation']);
        Route::post('/create', [App\Http\Controllers\Api\TemplateController::class, 'create']);//->middleware(["users.permission:1"]);
        Route::get('/edit/{id}', [App\Http\Controllers\Api\TemplateController::class, 'edit']);
        Route::post('/update/{id}', [App\Http\Controllers\Api\TemplateController::class, 'update']);//->middleware(["users.permission:1"]);
        Route::delete('/delete/{id}', [App\Http\Controllers\Api\TemplateController::class, 'delete']);
        Route::post('/reorder', [App\Http\Controllers\Api\TemplateController::class, 'reorder']);
        
        Route::get('/downloadable-templates', [App\Http\Controllers\Api\TemplateController::class, 'publicTemplates']); // all templates from master user
        // Route::get('/downloadable-templates2', [App\Http\Controllers\Api\TemplateController::class, 'publicTemplates2']); // all templates from master user
        Route::post('/sync/{id}', [App\Http\Controllers\Api\TemplateController::class, 'syncTemplate']); // download/sync
        
    
    
    });
    Route::prefix("/estimate")->group(function(){
        Route::get("/", [App\Http\Controllers\Api\EstimateController::class, 'index']);
        Route::post("/create", [App\Http\Controllers\Api\EstimateController::class, 'create']);
        Route::get("/edit/{id}", [App\Http\Controllers\Api\EstimateController::class, 'editEstimate']);
        Route::get("/edit/{estimate}/sheet", [App\Http\Controllers\Api\EstimateController::class, 'editEstimateSheet']);
        Route::post("/update/{estimate}", [App\Http\Controllers\Api\EstimateController::class, 'update']);//->middleware("users.permission");
        Route::delete("/delete/{estimate}", [App\Http\Controllers\Api\EstimateController::class, 'delete']);//->middleware("users.permission");
        //Route::post("/download/{estimate}", [App\Http\Controllers\Api\EstimateController::class, 'download']);
        // Route::post("/download/{estimate}", [App\Http\Controllers\Api\EstimateController::class, 'createDocx']);
        Route::get("/download/{estimate}", [App\Http\Controllers\Api\EstimateController::class, 'downloadTest']);
        Route::post("/pay/{estimate}", [App\Http\Controllers\Api\EstimateController::class, 'payEstimate']);

        //added by ashok
        Route::get("/{estimateId}/sheet", [App\Http\Controllers\Api\EstimateController::class, 'getSheetByEstimateId']);
        Route::post('/{estimateId}/add-edit-signature', [App\Http\Controllers\Api\EstimateController::class, 'addOrEditSignature']);

        Route::post("/duplicate", [App\Http\Controllers\Api\EstimateController::class, 'duplicate']);
        Route::get('/report/{id}', [App\Http\Controllers\Api\EstimateController::class, 'report']);
        Route::get('/types', [App\Http\Controllers\Api\EstimateTypeController::class, 'index']);
        Route::post('/{estimate}/email', [App\Http\Controllers\Api\EstimateController::class, 'sendMail']);

        //
        Route::post("/{estimate}/code/add", [App\Http\Controllers\Api\EstimateController::class, 'addCodes']);
        Route::post("/{estimate}/code/delete/{estimateSheet}", [App\Http\Controllers\Api\EstimateController::class, 'deleteEstimateSheet']);//->middleware("users.permission");
        Route::post("/{estimate}/templates/add", [App\Http\Controllers\Api\EstimateController::class, 'addTemplates']);


        Route::get("/{estimate}/total", [App\Http\Controllers\Api\EstimateController::class, 'total']);
        Route::post("/update/bulk/{estimate}", [App\Http\Controllers\Api\EstimateController::class, 'updateEstimateSheetsBulk']);//->middleware("users.permission");
        Route::post("/update/{estimate}/{estimateSheet}", [App\Http\Controllers\Api\EstimateController::class, 'updateEstimateSheet']);//->middleware("users.permission");
        //Route::post("/update/bulk/{estimate}", [App\Http\Controllers\Api\EstimateController::class, 'updateEstimateSheetsBulk']);//->middleware("users.permission");
        Route::post("/update/{estimate}/{estimateSheet}/code-swap", [App\Http\Controllers\Api\EstimateController::class, 'estimateSheetCodeSwap']);//->middleware("users.permission");



        Route::get("/save/{estimate}", [App\Http\Controllers\Api\EstimateController::class, 'save']);
        Route::post("/save-profit/{estimateSheet}", [App\Http\Controllers\Api\EstimateController::class, 'saveProfitBudget']);

        Route::get("/show/save-profit/{estimate}", [App\Http\Controllers\Api\EstimateController::class, 'showProfitBudget']);
        Route::get("/list/save-profit/{estimate}", [App\Http\Controllers\Api\EstimateController::class, 'profitBudgetList']);
    });

    //Route::prefix("/profit-budget")->middleware(['users.permission:1'])->group(function() {
    Route::prefix("/profit-budget")->group(function() {
        Route::get("/{estimate}", [App\Http\Controllers\Api\ProfitBudgetController::class, 'index']);
        Route::get("/{estimate}/total", [App\Http\Controllers\Api\ProfitBudgetController::class, 'viewTotal']);
        Route::post("/{estimate}/eow", [App\Http\Controllers\Api\ProfitBudgetController::class, 'setExtraWorkOrders']);//->middleware("users.permission");;
        Route::get("/{estimate}/list", [App\Http\Controllers\Api\ProfitBudgetController::class, 'list']);
        Route::post("/{estimate}/estimate-sheet/{estimateSheet}", [App\Http\Controllers\Api\ProfitBudgetController::class, 'setActualAmountEstimateSheet']);;//->middleware("users.permission");
        
        Route::get("/{estimate}/{type}/settings", [App\Http\Controllers\Api\ProfitBudgetController::class, 'getAllProfitBudgetEstimateSettings']);
        Route::post("/{estimate}/update-actual-costs", [App\Http\Controllers\Api\ProfitBudgetController::class, 'updatemergedActualCost']);
        Route::post("/{estimate}/update-actual-costs", [App\Http\Controllers\Api\ProfitBudgetController::class, 'updatemergedActualCost']);
        Route::post("update-profit-budget-settings-actual-costs", [App\Http\Controllers\Api\ProfitBudgetController::class, 'updateProfitBudgetSettingsActualCost']);
        
    });

    //Route::prefix("/material-list")->middleware(['users.permission:1'])->group(function() {
    Route::prefix("/material-list")->group(function() {
       
        Route::get("/{estimate}", [App\Http\Controllers\Api\ProfitBudgetController::class, 'materialList']);
        Route::get("/{estimate}/total", [App\Http\Controllers\Api\ProfitBudgetController::class, 'materialTotal']);
        Route::post("/{estimate}/email", [App\Http\Controllers\Api\ProfitBudgetController::class, 'emailMaterialListDoc']);
        Route::get("/{estimate}/supplier", [App\Http\Controllers\Api\ProfitBudgetController::class, 'materialListSupplier']);
        Route::get("/{estimate}/{group}", [App\Http\Controllers\Api\ProfitBudgetController::class, 'materialDetail']);
        Route::post("/{estimate}/update-purchasing-cost-by-group", [App\Http\Controllers\Api\ProfitBudgetController::class, 'updateActualCost']);
        
         
    });





    Route::prefix("/product-group")->group(function(){
        Route::get('/', [App\Http\Controllers\Api\ProductGroupController::class, 'index']);
        Route::post('/create', [App\Http\Controllers\Api\ProductGroupController::class, 'create']);//->middleware(["users.permission:1"]);
        Route::get('/edit/{id}', [App\Http\Controllers\Api\ProductGroupController::class, 'edit']);
        Route::post('/update/{id}', [App\Http\Controllers\Api\ProductGroupController::class, 'update']);//->middleware(["users.permission:1"]);
        Route::delete('/delete/{id}', [App\Http\Controllers\Api\ProductGroupController::class, 'delete']);//->middleware(["users.permission:1"]);
        Route::get('/search', [App\Http\Controllers\Api\ProductGroupController::class, 'search']);
        // Re-order API
        Route::post('/reorder', [App\Http\Controllers\Api\ProductGroupController::class, 'reorder']);

    });

    Route::prefix("/subscriptions")->group(function(){
        
        Route::get('/check', [App\Http\Controllers\Api\SubscriptionController::class, 'check']);
        Route::get('/status', [App\Http\Controllers\Api\SubscriptionController::class, 'checkStatus']);
        Route::get('/manage', [App\Http\Controllers\Api\SubscriptionController::class, 'manage']);
        Route::post('/pay-now', [App\Http\Controllers\Api\SubscriptionController::class, 'payNow']);
        Route::post('/cancel/{subscription}', [App\Http\Controllers\Api\SubscriptionController::class, 'cancel']);
        Route::post('/reactivate/{subscription}', [App\Http\Controllers\Api\SubscriptionController::class, 'reactivate']);
        
        
        Route::get('/default-payment-method', [App\Http\Controllers\Api\SubscriptionController::class, 'getDefaultPaymentMethod']);
        
        
        Route::post('/payment-method', [App\Http\Controllers\Api\SubscriptionController::class, 'createPaymentMethod']);
        
        

        // Route to get all payment methods
        Route::get('/payment-methods', [App\Http\Controllers\Api\SubscriptionController::class, 'getPaymentMethods']);
    
        // Route to set a payment method as default
        Route::post('/payment-method/set-default', [App\Http\Controllers\Api\SubscriptionController::class, 'setDefaultPaymentMethod']);
        
        
        
        
    });
    
    // Route::prefix("/card-processing")->group(function(){
    //     Route::post('contractor/stripe/create', [App\Http\Controllers\Api\StripeCreditCardProcessingController::class, 'getOnboardingLink']);
    //     Route::get('contractor/stripe/callback', [App\Http\Controllers\Api\StripeCreditCardProcessingController::class, 'stripeCallback'])->name('contractor.stripe.callback');
    //     Route::get('contractor/stripe/refresh', [App\Http\Controllers\Api\StripeCreditCardProcessingController::class, 'refreshOnboardingLink'])->name('contractor.stripe.refresh');
    // });
    
        

    Route::prefix("/code-book")->group(function(){
        Route::get('/', [App\Http\Controllers\Api\CodeBookController::class, 'index']);
        Route::get('/download', [App\Http\Controllers\Api\CodeBookController::class, 'download']);

        Route::get('/subscription', [App\Http\Controllers\Api\CodeBookController::class, 'subscription']);
        Route::get('/search', [App\Http\Controllers\Api\CodeBookController::class, 'search']);
        Route::post('/email', [App\Http\Controllers\Api\CodeBookController::class, 'email']);
    });


    Route::prefix("/memberships")->group(function(){
        Route::get('/', [App\Http\Controllers\Api\MembershipController::class, 'index']);
        
        Route::post('/total', [App\Http\Controllers\Api\MembershipController::class, 'membershipById']);
        Route::post('/test-stripe', [App\Http\Controllers\Api\MembershipController::class, 'testStripe']);
        Route::post('/check-subscriptions', [App\Http\Controllers\Api\MembershipController::class, 'checkSubscriptions']);

    });

    Route::prefix("/products")->group(function(){
        Route::get('/', [App\Http\Controllers\Api\ProductController::class, 'index']);
        Route::get('/search', [App\Http\Controllers\Api\ProductController::class, 'getProducts']);
        Route::get('/search-products', [App\Http\Controllers\Api\ProductController::class, 'search']);
    });

    Route::get('/notifications', [NotificationController::class, 'index']);

    // Route to mark notifications as read
    Route::post('/notifications/mark-as-read', [NotificationController::class, 'markAsRead']);



    Route::post('/logout', [App\Http\Controllers\Api\LoginController::class, 'logout']);
});





Route::prefix('card-processing')->group(function () {
    // Authenticated routes
    Route::middleware(['auth:app-api'])->group(function () {
        
        
        Route::post('stripe/onboard', [App\Http\Controllers\Api\StripeCreditCardProcessingController::class, 'onBoardStripe']);
        Route::post('stripe/connect', [App\Http\Controllers\Api\StripeCreditCardProcessingController::class, 'connectStripe']);
        
        
        Route::post('stripe/connect/create/intent', [App\Http\Controllers\Api\StripeCreditCardProcessingController::class, 'createPaymentIntent']);
        Route::post('stripe/connect/pay', [App\Http\Controllers\Api\StripeCreditCardProcessingController::class, 'payContractor']);
        
        
        Route::post('stripe/disconnect', [App\Http\Controllers\Api\StripeCreditCardProcessingController::class, 'disconnectStripeAccount']);
        // Route::post('stripe/connect/update/status', [App\Http\Controllers\Api\StripeCreditCardProcessingController::class, 'updateStatus']);
        
        Route::post('stripe/connect/generate-invoice-link', [App\Http\Controllers\Api\StripeCreditCardProcessingController::class, 'generateInvoiceLink']);
        
        Route::get('stripe/connect/status', [App\Http\Controllers\Api\StripeCreditCardProcessingController::class, 'checkConnectStatus']);
        
        Route::get('customer/transactions', [App\Http\Controllers\Api\StripeCreditCardProcessingController::class, 'getTransactions']);
        
        
    });
    
    // Route::get('customer/transactions', [App\Http\Controllers\Api\StripeCreditCardProcessingController::class, 'getTransactions']);
    //onboarding  
    Route::get('stripe/onboard/refreshLink', [App\Http\Controllers\Api\StripeCreditCardProcessingController::class, 'onBoardRefreshLink'])->name('stripe.onboard.refreshLink'); 
        
       

    Route::get('stripe/onboard/callback', [App\Http\Controllers\Api\StripeCreditCardProcessingController::class, 'onBoardCallback'])->name('stripe.onboard.callback');
    
     Route::post('stripe/onboard/webhook', [App\Http\Controllers\Api\StripeCreditCardProcessingController::class, 'handleonBoardWebhook']);
   

    //stripe connect
    Route::get('stripe/connect/callback', [App\Http\Controllers\Api\StripeCreditCardProcessingController::class, 'connectCallback'])->name('stripe.connect.callback');
    

    
    
});


//revenuecat web hook public route
Route::post('subscriptions/update-subscription-status', [App\Http\Controllers\Api\SubscriptionController::class, 'handleRevenueCatWebhook']);
