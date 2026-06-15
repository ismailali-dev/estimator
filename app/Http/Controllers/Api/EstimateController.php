<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\EstimateMail;
use App\Mail\EstimatePayMail;
use App\Mail\EstimateSaleReportMail;
use App\Models\Estimate;
use App\Models\EstimateSheet;
use App\Models\EstimateType;
use App\Models\Template;
use App\Models\TemplateCode;
use App\Models\User;
use App\Models\City;
use App\Models\ProductGroup;
use App\Models\Code;
use App\Models\UserInvoiceSetting;
use App\Models\UserSetting;
use App\Services\DownloadEmailService;
use App\Services\EstimateService;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\TemplateProcessor;
use ZipStream\File;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\Style\Table as TableStyle;
use PhpOffice\PhpWord\SimpleType\TblWidth;
use Illuminate\Support\Facades\Cache;
use App\Models\EstimateSignature;



class EstimateController extends ResponseController
{
  
    public function index(Request $request): JsonResponse
    {
       
        $currentUser = $request->user(); // Get the current user
    
        if ($currentUser->hasFullModuleAccess()) {
           
            // Determine which user's subscriptions to fetch
            $currentUser = $currentUser->is_admin ? $currentUser : User::find($currentUser->parent_id);
    
        } 
         

        // Start the query for estimates
        $query = Estimate::with(['customer', 'estimateType', 'user', 'sheet']) // Include related customer, estimateType, user, and sheet
            ->withCount([
                'sheet as non_zero_quantity_count' => function ($query) {
                    $query->where('quantity', '>', 0); // Count sheets with quantity > 0
                },
                'sheet' // Adds a sheet_count attribute with the total count of sheets
            ])->orderBy('created_at', 'asc');
    
        if ($currentUser->is_admin == 1) {
            // Admin: Check if the user has the 'multi_user_access' subscription
            if (auth()->user()->hasSubscription('multi_user_access')) {
                // Include soft-deleted users
                $childUserIds = User::withTrashed() // Include soft-deleted users
                    ->where('parent_id', $currentUser->id)
                    ->pluck('id')
                    ->toArray();
            } else {
                // Only include active users (non-soft-deleted)
                $childUserIds = User::where('parent_id', $currentUser->id)
                    ->pluck('id')
                    ->toArray();
            }
    
            // Include estimates for both the admin and their child users
            $userIds = array_merge([$currentUser->id], $childUserIds);
            $query->whereIn('user_id', $userIds);
        } else {
            // Non-admin: Show only the estimates created by the current user
            $query->where('user_id', '=', $currentUser->id);
        }
    
        // Apply company filter for all cases
        $query->where('company_id', '=', $currentUser->company_id);
    
        // Execute the query
        $estimates = $query->get()->map(function ($estimate) {
            // Add the estimated_by information from the creator of each estimate
            $estimate->estimated_by = $estimate->user ? $estimate->user->first_name . ' ' . $estimate->user->last_name : null;
    
            // Check if the creator has the 'global_setting_support' subscription
            $estimate->has_active_subscription = $estimate->user && $estimate->user->hasSubscription('global_setting_support') ? true : false;
    
            // Include the count of estimate sheets directly in the estimate
            $estimate->sheet_count = $estimate->sheet_count; // This will add the total count of sheets
            // dump($estimate->sheet_count);
            
    
            // Set the isProfitBudget flag based on if there is more than one sheet with quantity > 0
            $estimate->isProfitBudget = $estimate->non_zero_quantity_count >= 1;
    
            // Hide the user relationship from the response
            $estimate->makeHidden(['user', 'sheet']);
    
            return $estimate;
        });
    
        // Prepare the response data
        $this->response_data['status'] = true;
        $this->response_data['data'] = [
            'estimate' => $estimates,
            'count' => $estimates->count(),
        ];
    
        return $this->sendJsonResponse();
    }





    
    // public function getSheetByEstimateId(Request $request, $estimateId): JsonResponse
    // {
    //     $currentUser = $request->user(); // Get the current user
    
    //     $sheets = EstimateSheet::with(['code.productGroup'])
    //         ->where('estimate_id', $estimateId)
    //         ->join('codes', 'estimate_sheets.code_id', '=', 'codes.id')
    //         ->join('product_groups', 'codes.product_group_id', '=', 'product_groups.id')
    //         ->select('estimate_sheets.*')
    //         ->orderBy('product_groups.group_order') // Sort by group order first
    //         ->orderBy('codes.product_group_id') // Then by category
    //         ->orderBy('codes.code_name') // Finally, alphabetically within category
    //         ->get();
    
    //     // Check if sheets exist
    //     if ($sheets->isEmpty()) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'No sheets found for this estimate.'
    //         ], 200);
    //     }
    
    //     // Filter out 'productGroup' from the sheets collection
    //     $filteredSheets = $sheets->map(function ($sheet) {
    //         unset($sheet->code->productGroup); // Unset the 'productGroup' relationship from each sheet
    //         return $sheet;
    //     });
    
    //     // Add global key for active subscription (only once)
    //     $hasActiveSubscription = $currentUser->hasSubscription('global_setting_support');
    
    //     // Prepare the response data
    //     $this->response_data["status"] = true;
    //     $this->response_data["data"] = [
    //         "sheets" => $filteredSheets,
    //         "count" => $sheets->count(),
    //         "has_active_subscription" => $hasActiveSubscription // Global key added once
    //     ];
    
    //     return $this->sendJsonResponse();
    // }

        
    public function getSheetByEstimateId(Request $request, $estimateId): JsonResponse
    {
        $currentUser = $request->user(); // Get the current user
    
        $sheets = EstimateSheet::with(['code.productGroup'])
            ->where('estimate_id', $estimateId)
            ->join('codes', 'estimate_sheets.code_id', '=', 'codes.id')
            ->join('product_groups', 'codes.product_group_id', '=', 'product_groups.id')
            ->select('estimate_sheets.*')
            ->orderBy('product_groups.group_order')
            ->orderBy('codes.product_group_id')
            ->orderBy('codes.code_name')
            ->get();
    
        if ($sheets->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No sheets found for this estimate.'
            ], 200);
        }
    
        $filteredSheets = $sheets->map(function ($sheet) {
            if ($sheet->quantity == 0 && $sheet->unit !== $sheet->code->unit_of_measure) {
                $sheet->unit = $sheet->code->unit_of_measure;
                $sheet->labor_cost = $sheet->code->labor_cost;
                $sheet->material_cost = $sheet->code->material_cost;
                $sheet->misc_cost = $sheet->code->misc_cost;
            }
            
            $sheet->operator      = $sheet->code->operator;
            $sheet->adjust_num    = $sheet->code->adjust_num;
            $sheet->adjusted_cost = $sheet->code->adjusted_cost;
        
        
            unset($sheet->code->productGroup);
            return $sheet;
        });
    
        $hasActiveSubscription = $currentUser->hasSubscription('global_setting_support');
    
        $this->response_data["status"] = true;
        $this->response_data["data"] = [
            "sheets" => $filteredSheets,
            "count" => $sheets->count(),
            "has_active_subscription" => $hasActiveSubscription
        ];
    
        return $this->sendJsonResponse();
    }

    
   

    public function create(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => [
                'required',
                'numeric',
                'min:1'
            ],
            'estimate_scope' => 'required|string',
            'type' => 'required|in:1,2,3',
        ]);
    
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $msg) {
                if (strlen(trim($msg)) > 1) {
                    $this->response_data["message"] = $msg;
                    return $this->sendJsonResponse();
                }
            }
        }
    
        // Find the estimate type
        $estimateType = EstimateType::find($request->input("type"));
    
        // Count the original estimates (without duplicate suffixes) for this user and company
        $estimateCount = Estimate::where('company_id', $request->user()->company_id)
                         ->where('key', 'NOT LIKE', '%-%') // Exclude duplicates like key-1, key-2, etc.
                         ->count();

        // Increment the count by 1 to generate the new key
        $estimateNumber = $estimateCount + 1;
        
        // dd($estimateNumber);
    
        // Create a new estimate
        $estimate = new Estimate();
        $estimate->customer_id = $request->input("customer_id");
        $estimate->user_id = $request->user()->id;
        $estimate->company_id = $request->user()->company_id;
        $estimate->type = $request->input("type");
        $estimate->estimate_scope = $request->input("estimate_scope");
        $estimate->estimate_date_time = Carbon::now();
    
        // Set the key using the initials and the new estimate number
        $estimate->key = $estimateType->initials . $estimateNumber;
    
        // Save the new estimate
        $estimate->save();
    
        // Return a successful response
        $this->response_data["status"] = true;
        $this->response_data["message"] = "Estimate saved successfully.";
        $this->response_data["data"] = ["estimate" => $estimate->refresh()];
        return $this->sendJsonResponse();
    }
    


    public function editEstimate(Request $request, $id):JsonResponse
    {
        $estimate = Estimate::with(["customer"])
                            ->whereHas("customer")
                            ->where("id", "=", $id)
                            ->where("company_id", "=", $request->user()->company_id)
                            ->first();
        $this->response_data["message"] = "Data not found.";
        if (!empty($estimate)){
            $this->response_data["message"] = "";
            $this->response_data["status"] = true;
            $this->response_data["data"] = [ "estimate"=> $estimate->makeHidden(['contract_price','extra_work_orders']) ];
        }
        return $this->sendJsonResponse();
    }

    public function editEstimateSheet(Estimate $estimate){
        $estimate = Estimate::with(["customer","sheet"])
                            ->whereHas("customer")
                            ->where("id", "=", $estimate->id)
                            ->first();


        $data = [
            "estimate" => $estimate->makeHidden(['contract_price','extra_work_orders']),
        ];
        $this->response_data["message"] = "";
        $this->response_data["status"] = true;
        $this->response_data["data"] = $data;
        return $this->sendJsonResponse();
    }

    public function update(Request $request, Estimate $estimate):JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => [
                'required',
                'numeric',
                'min:1'
            ],
            'estimate_scope' => 'required|string',
            'type' => 'required|in:1,2,3',
        ]);
        if ($validator->fails()){
            foreach ($validator->errors()->all() as $msg) {
                if (strlen(trim($msg)) > 1){
                    $this->response_data["message"] = $msg;
                    return $this->sendJsonResponse();
                }
            }
        }
        $estimateType = EstimateType::find($request->input("type"));
        $estimate->update([
            "customer_id" => $request->input("customer_id"),
            "type" => $request->input("type"),
            "estimate_scope" => $request->input("estimate_scope"),
            "key" => $estimateType->initials . $estimate->id
        ]);

        $this->response_data["data"] = ["estimate" => $estimate->refresh()];
        return $this->sendJsonResponse();
    }


    public function updateEstimateSheet(EstimateService $estimateService,Estimate $estimate,EstimateSheet $estimateSheet,Request $request){
        $validator = Validator::make($request->all(), [
            'quantity' => 'required|numeric|min:0',
        ]);
        if($estimateSheet->estimate_id != $estimate->id){
            $this->response_data["status"] = false;
            $this->response_data["message"] = "Estimate Sheet Not Found";
            return $this->sendJsonResponse();
        }
        if ($validator->fails()){
            foreach ($validator->errors()->all() as $msg) {
                if (strlen(trim($msg)) > 1){
                    $this->response_data["message"] = $msg;
                    return $this->sendJsonResponse();
                }
            }
        }
        $updated = $estimateService->updateQuantity($estimateSheet,$request->quantity);
        if($updated){
            $this->response_data["data"] = $estimateSheet->refresh();
            $this->response_data["status"] = true;
            $this->response_data["message"] = "Code saved successfully.";
        }else{
            $this->response_data["status"] = false;
            $this->response_data["message"] = "Something went wrong.";
        }
        return $this->sendJsonResponse();

    }
    
    public function updateEstimateSheetsBulk(EstimateService $estimateService, Estimate $estimate, Request $request)
    {
       
        // Validate the request, ensuring 'sheets' is an array
        $validator = Validator::make($request->all(), [
            'sheets' => 'required|array',
            'sheets.*.estimate_sheet_id' => 'required|integer|exists:estimate_sheets,id',
            'sheets.*.quantity' => 'required|numeric|min:0',
        ]);
    
        if ($validator->fails()) {
            $this->response_data["status"] = false;
            $this->response_data["message"] = $validator->errors()->first();
            return $this->sendJsonResponse();
        }
    
        // Iterate over each estimate sheet and update the quantity
        $updatedSheets = [];
        foreach ($request->sheets as $sheetData) {
            $estimateSheet = EstimateSheet::where('id', $sheetData['estimate_sheet_id'])
                                          ->where('estimate_id', $estimate->id)
                                          ->first();
    
            if (!$estimateSheet) {
                $this->response_data["status"] = false;
                $this->response_data["message"] = "Estimate Sheet not found for ID: " . $sheetData['estimate_sheet_id'];
                return $this->sendJsonResponse();
            }
    
            // Update the quantity using the service method
            $updated = $estimateService->updateQuantity($estimateSheet, $sheetData['quantity']);
    
            if ($updated) {
                $updatedSheets[] = $estimateSheet->refresh();
            }
        }
    
        // Return response
        $this->response_data["status"] = true;
        $this->response_data["data"] = ["updated_sheets" => $updatedSheets];
        $this->response_data["message"] = "Sheets updated successfully.";
        return $this->sendJsonResponse();
    }

    public function delete(Request $request, Estimate $estimate): JsonResponse
{
    $currentUser = $request->user();

    // Determine allowed user IDs
    if ($currentUser->is_admin == 1) {
        if ($currentUser->hasSubscription('multi_user_access')) {
            $childUserIds = User::withTrashed()
                ->where('parent_id', $currentUser->id)
                ->pluck('id')
                ->toArray();
        } else {
            $childUserIds = User::where('parent_id', $currentUser->id)
                ->pluck('id')
                ->toArray();
        }

        $allowedUserIds = array_merge([$currentUser->id], $childUserIds);
    } else {
        $allowedUserIds = [$currentUser->id];
    }

    // Find the estimate owned by allowed user IDs and same company
    $estimate = Estimate::where("id", "=", $estimate->id)
        ->whereIn("user_id", $allowedUserIds)
        ->where("company_id", "=", $currentUser->company_id)
        ->first();

    $this->response_data["message"] = "Data not found.";

    if (!empty($estimate)) {
        EstimateSheet::where("estimate_id", $estimate->id)
            ->whereIn("user_id", $allowedUserIds)
            ->delete();

        $estimate->delete();

        $this->response_data["message"] = "Estimate deleted successfully.";
        $this->response_data["status"] = true;
    }

    return $this->sendJsonResponse();
}

   public function duplicate(Request $request): JsonResponse
{
    $validator = Validator::make($request->all(), [
        'estimate_id' => ['required', 'numeric', 'min:1'],
    ]);

    if ($validator->fails()) {
        foreach ($validator->errors()->all() as $msg) {
            if (strlen(trim($msg)) > 1) {
                $this->response_data["message"] = $msg;
                return $this->sendJsonResponse();
            }
        }
    }

    $currentUser = $request->user();

    // Get allowed user IDs (either self or children if admin)
    if ($currentUser->is_admin == 1) {
        if ($currentUser->hasSubscription('multi_user_access')) {
            $childUserIds = User::withTrashed()
                ->where('parent_id', $currentUser->id)
                ->pluck('id')
                ->toArray();
        } else {
            $childUserIds = User::where('parent_id', $currentUser->id)
                ->pluck('id')
                ->toArray();
        }

        $allowedUserIds = array_merge([$currentUser->id], $childUserIds);
    } else {
        $allowedUserIds = [$currentUser->id];
    }

    // Retrieve estimate only if user is allowed
    $estimate = Estimate::with('sheet')
        ->where('id', $request->input('estimate_id'))
        ->whereIn('user_id', $allowedUserIds)
        ->where('company_id', $currentUser->company_id)
        ->first();

    if (!empty($estimate)) {
        $copy = $estimate->replicate();
        $copy->estimate_date_time = Carbon::now();

        $baseKey = explode('-', $estimate->key)[0];

        $estimateCount = Estimate::where('user_id', $estimate->user_id) // keep same user_id as original
            ->where('company_id', $currentUser->company_id)
            ->where('key', 'LIKE', $baseKey . '-%')
            ->count();

        $newSuffix = $estimateCount + 1;

        $copy->key = $baseKey . '-' . $newSuffix;

        $copy->save();

        if ($copy->id) {
            foreach ($estimate->sheet as $item) {
                $newSheet = $item->replicate();
                $newSheet->estimate_id = $copy->id;
                $newSheet->save();
            }
        }

        $newEstimate = Estimate::find($copy->id);
        $newEstimate->load('sheet');
        $this->response_data["status"] = true;
        $this->response_data["message"] = "Estimate copied successfully.";
        $this->response_data["data"] = ["estimate" => $newEstimate];
    } else {
        $this->response_data["message"] = "Estimate not found or not authorized.";
    }

    return $this->sendJsonResponse();
}





    public function report_garbage(Request $request, $id)
    {
        $estimate = Estimate::with(["customer", "Sheet"])->where("user_id", "=", $request->user()->id)->where("id", "=", $id)->first();
        if(empty($estimate)){
            $this->response_data["message"] = "Data not found.";
            return $this->sendJsonResponse();
        }
        $user = User::find($request->user()->id);
        $stateCity = City::find($estimate->customer->city);
        $customer = [
            "full_name" => $estimate->customer->full_name,
            "phone" => $estimate->customer->phone,
            "email" => $estimate->customer->email,
            "address" => $estimate->customer->address,
            "zip_code" => $estimate->customer->zip_code,
            "state_name" => $stateCity->state->state_name,
            "city_name" => $stateCity->city_name,
        ];
        $sheetGroups = [];
        $tmpProductGroups = EstimateSheet::select("product_group_id")->with('ProductGroup')->where("estimate_id", "=", $estimate->id)->groupBy("product_group_id")->get()->sortBy('ProductGroup.group_name');
        if (!empty($tmpProductGroups)){
            foreach ($tmpProductGroups as $pg){
                $sheetGroups[] = [
                    "id"=>$pg->ProductGroup->id,
                    "group_name"=>$pg->ProductGroup->group_name,
                    "group_items"=>EstimateSheet::with('Code')->where("estimate_id", "=", $estimate->id)->where("product_group_id", "=", $pg->ProductGroup->id)->get()->sortBy("Code.sort_order")
                ];
            }
        }
        $path = storage_path("app/public/pdf");
        $image = public_path("assets/img/logo-old.png");
        $ext = pathinfo($image)["extension"];
        $base64 = base64_encode(file_get_contents($image));
        $htmlData = [
            'logo' => "",//"data:image/".$ext.";base64,".$base64,
            'report' => [
                "date" => date("d-M-y"),
                "page" => "",
                "company_name" => $user->company_name,
                "company_address" => $user->company_address,
                "report_by" => $user->full_name,
                "phone" => $user->phone,
                "customer" => $customer,
                "invoice" =>$estimate,
                "invoice_detail" => $sheetGroups,
            ]
        ];


        //$html = view('reports.estimate', $htmlData)->render();
        //echo $html;
        $reportName = 'estimate-sheet-'.$estimate->user_id."-".str_replace("/", "-", $estimate->estimate_date).'.pdf';
        //$reportName = Str::random().".pdf";
        $pdf = PDF::loadView('reports.estimate', $htmlData);
        $pdf->output();
        $dom_pdf = $pdf->getDomPDF();
        $canvas = $dom_pdf->get_canvas();

        $pageWidth = $canvas->get_width();
        $pageHeight = $canvas->get_height();
        $canvas->page_text($pageWidth - 85, 48, " {PAGE_NUM} of {PAGE_COUNT}", null, 10, array(0, 0, 0));
        /*
         * $pdf = App::make('dompdf');
        $pdf->loadView('whatever', $data);
        $pdf->output();
        $dom_pdf = $pdf->getDomPDF();
        
        $canvas = $dom_pdf ->get_canvas();
        $canvas->page_text(0, 0, "Page {PAGE_NUM} of {PAGE_COUNT}", null, 10, array(0, 0, 0));
                 * */
        $content = $pdf->download()->getOriginalContent();
        if (Storage::exists('reports/'.$reportName)){
            Storage::delete("reports/".$reportName);
        }
        Storage::put("reports/".$reportName,$content);

        $this->response_data["status"] = true;
        $this->response_data["data"] = ["path"=>asset("storage/reports/"), "file"=>$reportName, "report"=>asset("storage/reports/".$reportName)];
        $this->response_data["message"] = "Report generate successfully.";

        return $this->sendJsonResponse();

        /*$html = view('reports.estimate')->render();
        echo $html;
        dd();*/

        /*$pdf = PDF::loadView('reports.estimate');
        return $pdf->download("test.pdf");*/

    }

    public function worddoc(Request $request)
    {
        $id = 10;

        $estimate = Estimate::with(["customer", "Sheet"])->where("user_id", "=", 4)->where("id", "=", $id)->first();
        if(empty($estimate)){

        }
        $user = User::find(4);
        $stateCity = City::find($estimate->customer->city);
        $customer = [
            "full_name" => $estimate->customer->full_name,
            "phone" => $estimate->customer->phone,
            "email" => $estimate->customer->email,
            "address" => $estimate->customer->address,
            "zip_code" => $estimate->customer->zip_code,
            "state_name" => $stateCity->state->state_name,
            "city_name" => $stateCity->city_name,
        ];
        $sheetGroups = [];
        $tmpProductGroups = EstimateSheet::select("product_group_id")->with('ProductGroup')->where("estimate_id", "=", $estimate->id)->groupBy("product_group_id")->get()->sortBy('ProductGroup.group_name');
        if (!empty($tmpProductGroups)){
            foreach ($tmpProductGroups as $pg){
                //$sheets = EstimateSheet::with('Code')->where("estimate_id", "=", $estimate->id)->where("product_group_id", "=", $pg->ProductGroup->id)->get();
                $sheetGroups[] = [
                    "id"=>$pg->ProductGroup->id,
                    "group_name"=>$pg->ProductGroup->group_name,
                    "group_items"=>EstimateSheet::with('Code')->where("estimate_id", "=", $estimate->id)->where("product_group_id", "=", $pg->ProductGroup->id)->get()->sortBy("Code.sort_order")
                ];
            }
        }
        $path = storage_path("app/public/pdf");
        $image = public_path("assets/img/logo-old.png");
        $ext = pathinfo($image)["extension"];
        $base64 = base64_encode(file_get_contents($image));
        $htmlData = [
            'logo' => "data:image/".$ext.";base64,".$base64,
            'report' => [
                "date" => date("d-M-y"),
                "page" => "",
                "company_name" => $user->company_name,
                "company_address" => $user->company_address,
                "report_by" => $user->full_name,
                "phone" => $user->phone,
                "customer" => $customer,
                "invoice" =>$estimate,
                "invoice_detail" => $sheetGroups,
            ]
        ];


        $html = view('reports.estimate-doc', $htmlData)->render();

        $templateProcessor = new TemplateProcessor(storage_path("template.docx"));
        $templateProcessor->setValue("full_name", "Mohammad Imran Alam");
        $templateProcessor->setValue("location", "Karachi, Pakistan");
        $templateProcessor->saveAs(storage_path("Result.docx"));
        return $this->sendJsonResponse();

        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $textAlign = new \PhpOffice\PhpWord\SimpleType\TextAlignment();
        /*$section = $phpWord->addSection();
        $header = $section->addHeader();*/

        $headerImageSource = $image;
        $headerSection = $phpWord->addSection();
        $header = $headerSection->addHeader();

        $table = $header->addTable(array('cellMargin' => 0));
        $table->addRow();
        $table->addCell()->addImage($headerImageSource, array('width' => 200, 'height'=> 100, 'marginTop'=> -1, 'marginLeft'=> -1, 'wrappingStyle'=> 'behind', 'align'=> 'center'));

        $companyCell = $table->addCell()->addText("Second Cell");

        $table->addCell()->addText("Third Cell");

        //$header->addImage($headerImageSource, array('width' => 250, 'height'=> 50, 'marginTop'=> -1, 'marginLeft'=> -1, 'wrappingStyle'=> 'behind', 'align'=> 'center'));
        $date= date("F jS, Y", time());
        $dateSection = $phpWord->addSection();
        $dateSection->addText('Date: '.$date, array('name'=> 'Arial', 'size'=> '11'), ['align'=> $textAlign::CENTER]);
        $patientSection = $phpWord->addSection();
        $tableStyle = array(
            'cellMargin' => 50
        );
        $textStyle = array('name'=> 'Arial', 'size'=> '10');
        $table = $patientSection->addTable($tableStyle);
//creatTableRowsAndCells($table, 2, 3, 3000);
        $patientName = "Test";
        $DOB = "6th July 1978";
        $gender = "Male";
        $table->addRow();
        $table->addCell(3000)->addText('Patient Name: '.$patientName, $textStyle);
        $table->addCell(3000)->addText('DOB: '.$DOB, $textStyle);
        $table->addCell(3000)->addText('Gender: '.$gender, $textStyle);

        //\PhpOffice\PhpWord\Shared\Html::addHtml($section, $html);



// Adding Text element to the Section having font styled by default...


        $phpWord->save(storage_path('new.docx'));
        return $this->sendJsonResponse();
       


        $html = view('reports.estimate-doc', $htmlData)->render();
        //return $html;
        $htd = new WordDoc();
        $htd->createDoc($html, "test-doc", 1);


        $reportName = 'estimate-sheet-'.$estimate->user_id."-".str_replace("/", "-", $estimate->estimate_date).'.pdf';

        /*$phpWord = new \PhpOffice\PhpWord\PhpWord();
        $section = $phpWord->addSection();
        \PhpOffice\PhpWord\Shared\Html::addHtml($section, $html);
        //$objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $phpWord->save(storage_path('helloWorld.docx'));
        dd();*/


        $view = view('reports.estimate', $htmlData)->render();




        $file_name = strtotime(date('Y-m-d H:i:s')) . '_advertisement_template.doc';
        $headers = array(
            "Content-type"=>"application/vnd.openxmlformats-officedocument.wordprocessingml.document",
            "Content-Disposition"=>"attachment;Filename=$file_name"
        );
        return \Response::make($view,200, $headers);


        $content = $pdf->download()->getOriginalContent();
        if (Storage::exists('reports/'.$reportName)){
            Storage::delete("reports/".$reportName);
        }
        Storage::put("reports/".$reportName,$content);

        $this->response_data["status"] = true;
        $this->response_data["data"] = ["path"=>asset("storage/reports/"), "file"=>$reportName, "report"=>asset("storage/reports/".$reportName)];
        $this->response_data["message"] = "Report generate successfully.";

        return $this->sendJsonResponse();

        /*$html = view('reports.estimate')->render();
        echo $html;
        dd();*/

        /*$pdf = PDF::loadView('reports.estimate');
        return $pdf->download("test.pdf");*/

    }

    public function test(Request $request):JsonResponse
    {
        $data = UserInvoiceSetting::find(1);
        $this->response_data["data"] = $data->full_logo;

        return  $this->sendJsonResponse();
    }

    public function report(Request $request, $id)
    {
        $estimate = Estimate::with(["customer", "Sheet"])->where("user_id", "=", $request->user()->id)->where("id", "=", $id)->first();
        if(empty($estimate)){
            $this->response_data["message"] = "Data not found.";
            return $this->sendJsonResponse();
        }
        $user = User::find($request->user()->id);
        $stateCity = City::find($estimate->customer->city);
        $customer = [
            "full_name" => $estimate->customer->full_name,
            "phone" => $estimate->customer->phone,
            "email" => $estimate->customer->email,
            "address" => $estimate->customer->address,
            "zip_code" => $estimate->customer->zip_code,
            "state_name" => $stateCity->state->state_name,
            "city_name" => $stateCity->city_name,
        ];
        $sheetGroups = [];
        $tmpProductGroups = EstimateSheet::select("product_group_id")->with('ProductGroup')->where("estimate_id", "=", $estimate->id)->groupBy("product_group_id")->get()->sortBy('ProductGroup.group_name');
        if (!empty($tmpProductGroups)){
            foreach ($tmpProductGroups as $pg){
                $sheetGroups[] = [
                    "id"=>$pg->ProductGroup->id,
                    "group_name"=>$pg->ProductGroup->group_name,
                    "group_items"=>EstimateSheet::with('Code')->where("estimate_id", "=", $estimate->id)->where("product_group_id", "=", $pg->ProductGroup->id)->get()->sortBy("Code.sort_order")
                ];
            }
        }
        $invoiceSetting = UserInvoiceSetting::where("user_id", "=", $request->user()->id)->first();
        $invoice_logo = "";
        $invoice_color = "#f1f1f1";
        if (!empty($invoiceSetting)){
            if ($user->payment_type){
                $path = str_ireplace("storage/app/", "", $invoiceSetting->logo);
                if (Storage::exists($path)){
                    $file = Storage::get($path);
                    $ext = pathinfo($path)["extension"];
                    $base64 = base64_encode($file);
                    $invoice_logo = "data:image/".$ext.";base64,".$base64;
                }
            }
            $invoice_color = $invoiceSetting->color;
        }
        $htmlData = [
            'logo' => $invoice_logo,
            'color' => $invoice_color,
            'report' => [
                "date" => date("d-M-y"),
                "page" => "",
                "company_name" => $user->company_name,
                "company_address" => $user->company_address,
                "report_by" => $user->full_name,
                "phone" => $user->phone,
                "customer" => $customer,
                "invoice" =>$estimate,
                "invoice_detail" => $sheetGroups,
            ]
        ];
        $reportName = 'estimate-sheet-'.$estimate->user_id."-".str_replace("/", "-", $estimate->estimate_date).'.pdf';
        $pdf = PDF::loadView('reports.estimate', $htmlData);
        $pdf->output();
        $dom_pdf = $pdf->getDomPDF();
        $canvas = $dom_pdf->get_canvas();

        $pageWidth = $canvas->get_width();
        $pageHeight = $canvas->get_height();
        $canvas->page_text($pageWidth - 85, 48, " {PAGE_NUM} of {PAGE_COUNT}", null, 10, array(0, 0, 0));

        $content = $pdf->download()->getOriginalContent();
        if (Storage::exists('reports/'.$reportName)){
            Storage::delete("reports/".$reportName);
        }
        Storage::put("reports/".$reportName,$content);
        $this->response_data["status"] = true;
        $this->response_data["data"] = ["path"=>asset("storage/reports/"), "file"=>$reportName, "report"=>asset("storage/reports/".$reportName)];
        $this->response_data["message"] = "Report generate successfully.";
        return $this->sendJsonResponse();
    }


    // function addCodes(EstimateService $estimateService,Estimate $estimate,Request $request){
    //     $validator = Validator::make($request->all(), [
    //         'codes' => ['required', 'array'],
    //         // 'codes.*' => 'distinct'
    //     ]);
    //     if ($validator->fails()){
    //         foreach ($validator->errors()->all() as $msg) {
    //             if (strlen(trim($msg)) > 1){
    //                 $this->response_data["message"] = $msg;
    //                 return $this->sendJsonResponse();
    //             }
    //         }
    //     }
    //     try{
    //         $codes = Code::whereIn("id",$request->codes)->get();
    //         if(count($codes) == 0) {
    //             $this->response_data["status"] = false;
    //             $this->response_data["message"] = "No Code Available.";
    //         }else{
    //             $estimateService->addCodesOrTemplateCodes($estimate,$codes);
    //             $this->response_data["status"] = true;
    //             $this->response_data["message"] = "Code saved successfully.";
    //         }
    //     }catch (\Exception $e){
    //         $this->response_data["status"] = false;
    //         $this->response_data["message"] = $e->getMessage();
    //     }
    //     return $this->sendJsonResponse();

    // }


        public function addCodes(EstimateService $estimateService, Estimate $estimate, Request $request) {
           
           
            $validator = Validator::make($request->all(), [
                'codes' => ['sometimes', 'required_without:templates', 'array'],
                // 'codes.*' => 'distinct',
                'templates' => ['sometimes', 'array'],
                'templates.*.template_id' => ['required_with:templates', 'integer'],
                'templates.*.codes' => ['required_with:templates', 'array'],
                'templates.*.codes.*' => ['integer'],
            ]);
        
            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $msg) {
                    if (strlen(trim($msg)) > 1) {
                        $this->response_data["message"] = $msg;
                        return $this->sendJsonResponse();
                    }
                }
            }    
            
            
            try {
                // Process standalone codes
                if ($request->has('codes')) {
                    $standaloneCodes = Code::whereIn("id", $request->codes)->get();
                    if ($standaloneCodes->isNotEmpty()) {
                        $estimateService->addCodesOrTemplateCodes($estimate, $standaloneCodes);
                    }
                }
        
                // Process templates
                if ($request->has('templates')) {
                    foreach ($request->templates as $template) {
                        if (isset($template['template_id']) && isset($template['codes'])) {
                            $templateCodes = Code::whereIn("id", $template['codes'])->get();
                            if ($templateCodes->isNotEmpty()) {
                                $estimateService->addCodesOrTemplateCodes($estimate, $templateCodes, $template['template_id']);
                            }
                        }
                    }
                }
        
                $this->response_data["status"] = true;
                $this->response_data["message"] = "Codes saved successfully.";
            } catch (\Exception $e) {
                
                $this->response_data["status"] = false;
                $this->response_data["message"] = $e->getMessage();
            }
        
            return $this->sendJsonResponse();
        }




    public function estimateSheetCodeSwap(EstimateService $estimateService,Estimate $estimate,EstimateSheet $estimateSheet,Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code_id' => [
                   'required',
                  'numeric',
                  Rule::exists("codes","id")
            ],
        ]);
        if($estimateSheet->estimate_id != $estimate->id){
            $this->response_data["status"] = false;
            $this->response_data["message"] = "Estimate Sheet Not Found";
            return $this->sendJsonResponse();
        }
        if ($validator->fails()){
            foreach ($validator->errors()->all() as $msg) {
                if (strlen(trim($msg)) > 1){
                    $this->response_data["message"] = $msg;
                    return $this->sendJsonResponse();
                }
            }
        }
        try{
            $code = Code::where("id",$request->code_id)->first();
            if($code) {
                $estimateSheet->update([
                  "code_id" => $code->id
                ]);
                if($estimateSheet->quantity){
                    $estimateService->updateQuantity($estimateSheet->refresh(),$estimateSheet->quantity);
                }
                $this->response_data["status"] = true;
                $this->response_data["message"] = "";
                $this->response_data["data"] = $estimateSheet->refresh();
            }
        }catch (\Exception $e){
            $this->response_data["status"] = false;
            $this->response_data["message"] = $e->getMessage();
        }
        return $this->sendJsonResponse();

    }


    function addTemplates(EstimateService $estimateService,Estimate $estimate,Request $request){
        $validator = Validator::make($request->all(), [
            'templates' => ['required', 'array'],
            'templates.*' => 'distinct'
        ]);
        if ($validator->fails()){
            foreach ($validator->errors()->all() as $msg) {
                if (strlen(trim($msg)) > 1){
                    $this->response_data["message"] = $msg;
                    return $this->sendJsonResponse();
                }
            }
        }
        try{
            $templates = Template::with('codes')->whereIn("id",$request->templates)->get();
            foreach ($templates as $template){
                $estimateService->addCodesOrTemplateCodes($estimate,$template->codes,$template);
            }
            $this->response_data["status"] = true;
            $this->response_data["message"] = "Code saved successfully.";
        }catch (\Exception $e){
            $this->response_data["status"] = false;
            $this->response_data["message"] = $e->getMessage();
        }
        return $this->sendJsonResponse();

    }

    function save(Estimate $estimate,EstimateService $estimateService){
        
        $estimate->update([
            "is_saved" => 1
        ]);
        $this->response_data["data"] = $estimateService->getDetail($estimate,auth()->user());
        $this->response_data["status"] = true;
        $this->response_data["message"] = "Estimate Detail";
        return $this->sendJsonResponse();
    }

    function saveProfitBudget(EstimateSheet $estimateSheet,Request $request){
        $this->response_data["status"] = false;
        $this->response_data["message"] = "Something went wrong";
        $validator = Validator::make($request->all(), [
            'actual' => ['required', 'numeric'],
        ]);
        if ($validator->fails()){
            foreach ($validator->errors()->all() as $msg) {
                if (strlen(trim($msg)) > 1){
                    $this->response_data["message"] = $msg;
                    return $this->sendJsonResponse();
                }
            }
        }
        $updated = $estimateSheet->update([
            "actual_profit_budget" => $request->actual
        ]);

        if($updated){
            $this->response_data["status"] = true;
            $this->response_data["message"] = "Profit Budget saved successfully.";
        }

        return $this->sendJsonResponse();
    }

    function showProfitBudget(Estimate $estimate){
        $estimateSheet  = $estimate->sheet;

        $budget = $estimateSheet->sum('row_total');
        $actualProfitBudget = $estimateSheet->sum('actual_profit_budget');
        $diff =  $budget - $actualProfitBudget;

        dump($budget,$actualProfitBudget,$diff);
    }

    function profitBudgetList(Estimate $estimate){
        $profitBudget = $estimate->sheet->map(function($estimateSheet){
            $estimateSheet["difference"] = ($estimateSheet->row_total - $estimateSheet->actual_profit_budget);
            return $estimateSheet;
        });
        $this->response_data["status"] = true;
        $this->response_data["data"] = $profitBudget;
        return $this->sendJsonResponse();
    }

    function total(Estimate $estimate){
        $profitBudget = $estimate->sheet->sum("row_total");
        $this->response_data["status"] = true;
        $this->response_data["data"] = $profitBudget;
        return $this->sendJsonResponse();
    }

    function deleteEstimateSheet(Estimate $estimate,EstimateSheet $estimateSheet){
        if($estimateSheet->estimate_id != $estimate->id){
            $this->response_data["status"] = false;
            $this->response_data["message"] = "Estimate Sheet Not Found";
            return $this->sendJsonResponse();
        }
        $estimateSheet->delete();
        $this->response_data["status"] = true;
        $this->response_data["data"] = $estimate->sheet->toArray();
        return $this->sendJsonResponse();
    }

    function sendMail(Estimate $estimate,EstimateService $estimateService){

        $sheets = $estimate->sheet()->with([
            "Code.ProductGroup"
        ])->get();
        if(count($sheets) == 0){
            $this->response_data["data"] = "No Items to download";
            return $this->sendJsonResponse();
        }
        $downloadEmailService = new DownloadEmailService();
        $resp = $downloadEmailService->createEstimateDocFile($estimate, $estimateService,auth()->user());

        dd(Mail::to('recipient@example.com')
            ->send(new EstimateMail($estimate,$resp['filepath'])));
    }

/*    function createEstimateDocFile(Estimate $estimate,EstimateService $estimateService){
        $sheets = $estimate->sheet()->with([
            "Code.ProductGroup"
        ])->get();
        $centeredParagraphStyle = ['align' => 'center'];
        $company = $estimate->company;
        $user = $estimate->user;
        $customer = $estimate->customer;
        //\PhpOffice\PhpWord\Settings::setZipClass(Settings::PCLZIP);
        \PhpOffice\PhpWord\Settings::setOutputEscapingEnabled(true);

        $detail = $estimateService->getDetail($estimate);

        $templatePath = storage_path('downloadable-formats/Estimate-doc-format.docx');

        $templateProcessor = new TemplateProcessor($templatePath);

        $templateProcessor->setValue('company_name', $company->name);
        $templateProcessor->setValue("date", date('F j, Y'));
        $templateProcessor->setValue("company_address", $company->address);
        $templateProcessor->setValue("company_phone",$user->phone);
        $templateProcessor->setValue("company_email", $user->email);
        $templateProcessor->setValue("customer_name", $customer->name);
        $templateProcessor->setValue("customer_address", $customer->address);
        $templateProcessor->setValue("qoute_date", $estimate->created_at->format('F j, Y'));
        $templateProcessor->setValue("scope", $estimate->estimate_scope);
        $templateProcessor->setValue("grand_total", $detail["grand_total"]);

        $table = new Table(array('width' => 10000, 'unit' => TblWidth::TWIP));

        for ($i = 0;$i < count($sheets);$i++) {
            $sno = $i;
            $table->addRow();
            $cell = $table->addCell(null, array('gridSpan' => 3));
            $cell->addText(optional($sheets[$i]->code->ProductGroup)->group_name,array('bold' => true),$centeredParagraphStyle);
            $table->addRow();
            $table->addCell(10)->addText(++$sno);
            $table->addCell(150)->addText(optional($sheets[$i]->code)->description);
            $table->addCell(50)->addText(optional($sheets[$i])->quantity . " " .optional($sheets[$i])->unit);

        }
        $templateProcessor->setComplexBlock('table', $table);
        $fileName = 'estimate'.time().'.docx';

        $filePath = storage_path($fileName);
        $templateProcessor->saveAs($filePath);

        return [
            "filename" => $fileName,
            "filepath" => $filePath,
        ];
    }
*/

/*    public function downloadTest(Estimate $estimate,EstimateService $estimateService)
    {
        $sheets = $estimate->sheet()->with([
            "Code.ProductGroup"
        ])->get();
        if(count($sheets) == 0){
            $this->response_data["data"] = "No Items to download";
            return $this->sendJsonResponse();
        }
        $resp = $this->createEstimateDocFile($estimate,$estimateService);
        $headers = [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'Content-Disposition' => 'attachment; filename="'.$resp['filename'].'"',
        ];
        // Return the file as a download response
        return response()->download($resp['filepath'],$resp['filename'],$headers);//->deleteFileAfterSend(true);
    }*/



    public function payEstimate(Estimate $estimate,EstimateService $estimateService,Request $request){

        try{
            $validator = Validator::make($request->all(), [
                'amount' => 'required|numeric|between:0,9999999999.99'
            ]);
            if ($validator->fails()){
                foreach ($validator->errors()->all() as $msg) {
                    if (strlen(trim($msg)) > 1){
                        $this->response_data["message"] = $msg;
                        return $this->sendJsonResponse();
                    }
                }
            }

            $estimateDetail = $estimateService->getDetail($estimate);

            if($request->amount > $estimateDetail['grand_total']){
                $this->response_data["status"] = false;
                $this->response_data["message"] = "Amount cannot be greater then estimate's grand total";
                return  $this->sendJsonResponse();
            }

            $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));
            $price = $stripe->prices->create([
                'currency' => 'usd',
                'unit_amount' => ($request->amount * 100),
                'product' => 'prod_QUB8TV1TxQtGFP',
                'metadata' => [
                    'purpose' => 'for estimate',
                    'estiamte_id' => $estimate->id
                  ]
              ]);

              $link = $stripe->paymentLinks->create([
                'line_items' => [
                  [
                    'price' => $price->id,
                    'quantity' => 1,
                  ],
                ],
                'metadata' => [
                    'purpose' => 'for estimate',
                    'estiamte_id' => $estimate->id
                  ]
              ]);

              Mail::to(auth()->user()->email)
                ->send(new EstimatePayMail($estimate,$link->url));

             $this->response_data["status"] = true;
             $this->response_data["message"] = "Email of estimate payment link has been sent successfully.";
             $this->response_data["data"] = $link->url;

             return  $this->sendJsonResponse();
        }catch(\Exception $e){
            $this->response_data["status"] = false;
            $this->response_data["message"] = $e->getMessage();
            return  $this->sendJsonResponse();
        }


    }



    public function salesReportEmail(Request $request)
    {
        $user =  auth()->user();
        $validator = Validator::make($request->all(), [
            'from' => ['required', 'date', 'date_format:Y-m-d', 'before_or_equal:to'],
            'to' => ['required', 'date', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        if ($validator->fails()){
            foreach ($validator->errors()->all() as $msg) {
                if (strlen(trim($msg)) > 1){
                    $this->response_data["message"] = $msg;
                    return $this->sendJsonResponse();
                }
            }
        }

        try{
            $estimateService = new EstimateService();
            $count = $estimateService->getSalesReportCount($user,$request->from,$request->to);
            if($count > 0){
                $downloadEmailService = new DownloadEmailService();
                $resp = $downloadEmailService->createSalesReport($user,$request->from,$request->to);

                Mail::to($user->email)
                    ->send(new EstimateSaleReportMail($resp['filepath']));
                $this->response_data["status"] = true;
                $this->response_data["data"] = "Email send successfully";

            }else{
                $this->response_data["status"] = false;
                $this->response_data["data"] = "No data exists";
            }
            return $this->sendJsonResponse();
        }catch (\Exception $exp){
            $this->response_data["data"] = $exp->getMessage();
            return $this->sendJsonResponse();
        }

    }


//    public function download(Estimate $estimate){
//        $phpWord = new \PhpOffice\PhpWord\PhpWord();
//        \PhpOffice\PhpWord\Settings::setZipClass(Settings::PCLZIP);
//        /* Note: any element you append to a document must reside inside of a Section. */
//
//// Adding an empty Section to the document...
//        $section = $phpWord->addSection();
//// Adding Text element to the Section having font styled by default...
//        $section->addText(
//            '"Learn from yesterday, live for today, hope for tomorrow. '
//            . 'The important thing is not to stop questioning." '
//            . '(Albert Einstein)'
//        );
//
//        /*
//         * Note: it's possible to customize font style of the Text element you add in three ways:
//         * - inline;
//         * - using named font style (new font style object will be implicitly created);
//         * - using explicitly created font style object.
//         */
//
//// Adding Text element with font customized inline...
//        $section->addText(
//            '"Great achievement is usually born of great sacrifice, '
//            . 'and is never the result of selfishness." '
//            . '(Napoleon Hill)',
//            array('name' => 'Tahoma', 'size' => 10)
//        );
//
//// Adding Text element with font customized using named font style...
//        $fontStyleName = 'oneUserDefinedStyle';
//        $phpWord->addFontStyle(
//            $fontStyleName,
//            array('name' => 'Tahoma', 'size' => 10, 'color' => '1B2232', 'bold' => true)
//        );
//        $section->addText(
//            '"The greatest accomplishment is not in never falling, '
//            . 'but in rising again after you fall." '
//            . '(Vince Lombardi)',
//            $fontStyleName
//        );
//
//// Adding Text element with font customized using explicitly created font style object...
//        $fontStyle = new \PhpOffice\PhpWord\Style\Font();
//        $fontStyle->setBold(true);
//        $fontStyle->setName('Tahoma');
//        $fontStyle->setSize(13);
//        $myTextElement = $section->addText('"Believe you can and you\'re halfway there." (Theodor Roosevelt)');
//        $myTextElement->setFontStyle($fontStyle);
//
//// Saving the document as OOXML file...
//        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
//        $objWriter->save('helloWorld.docx');
//
//        dd("ok");
//        // Return the document as a download response
//        //return response()->download($tempFile, 'helloWorld.docx')->deleteFileAfterSend(true);
//    }

/*    public function createDocx(Estimate $estimate)
    {
        // Create a new PHPWord object
        $phpWord = new PhpWord();

        \PhpOffice\PhpWord\Settings::setZipClass(Settings::PCLZIP);

        // Add a new section to the document
        $section = $phpWord->addSection();

        // Add title
        $section->addTitle('Estimate', 1);



        $centeredParagraphStyle = ['align' => 'center'];
        $rightAlignedParagraphStyle = ['align' => 'right'];

        $section->addText("Date : " . date('F j, Y'), [], $rightAlignedParagraphStyle);
        $section->addText('Company Name', ['bold' => true, 'size' => 16],$centeredParagraphStyle);


        // Add a table with two columns
        // Add date in the second cell, right-aligned




        $section->addTextBreak(1, null, $centeredParagraphStyle);
        $section->addText('Company Address',null,$centeredParagraphStyle);
        $section->addText('City, State, ZIP Code',null,$centeredParagraphStyle);
        $section->addText('Phone Number',null,$centeredParagraphStyle);
        $section->addText('Email Address',null,$centeredParagraphStyle);
        $section->addTextBreak(1);

        // Add table
        $table = $section->addTable();




        // Add table
        $table = $section->addTable();

        // Add row
        $table->addRow();
        $table->addCell(2000)->addText('Item');
        $table->addCell(2000)->addText('Description');
        $table->addCell(2000)->addText('Quantity');
        $table->addCell(2000)->addText('Unit Price');
        $table->addCell(2000)->addText('Total');

        // Add more rows with data (Example data)
        for ($i = 1; $i <= 5; $i++) {
            $table->addRow();
            $table->addCell(2000)->addText("Item $i");
            $table->addCell(2000)->addText("Description $i");
            $table->addCell(2000)->addText(rand(1, 10));
            $table->addCell(2000)->addText('$' . rand(10, 100));
            $table->addCell(2000)->addText('$' . rand(10, 1000));
        }

        // Save the document
        $fileName = 'estimate.docx';
        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save(storage_path($fileName));

        // Return the file as a download response
        dd("ok");
        //return response()->download(storage_path($fileName))->deleteFileAfterSend(true);
    }*/
    

    public function addOrEditSignature(Request $request, $estimateId)
    {
        // Validate incoming request with a custom rule to ensure at least one signature is provided
        $request->validate([
            'estimator_signature' => 'nullable|string', // Base64 encoded signature
            'customer_signature' => 'nullable|string',  // Base64 encoded signature
        ], [
            'required_without_all' => 'At least one signature is required.', // Custom error message
        ]);

        // Check that at least one signature is provided
        if (!$request->has('estimator_signature') && !$request->has('customer_signature')) {
            $this->response_data["message"] = "At least one signature is required.";
            return $this->sendJsonResponse();
        }

        // Get the current user ID
        $userId = auth()->id();

        // Check if the estimate belongs to the current user
        $estimate = Estimate::find($estimateId);
        if (!$estimate || $estimate->user_id !== $userId) {
            
            $this->response_data["message"] = "Unauthorized. You do not own this estimate.";
            return $this->sendJsonResponse();
        }

        // Find or create the estimate signature entry
        $estimateSignature = EstimateSignature::firstOrCreate(
            ['user_id' => $userId, 'estimate_id' => $estimateId],
            [
                'estimator_signature' => null,
                'customer_signature' => null,
                'estimator_signed_at' => null,
                'customer_signed_at' => null,
            ]
        );

        // Update estimator's signature if provided
        if ($request->has('estimator_signature')) {
            $estimateSignature->estimator_signature = $request->estimator_signature;
            $estimateSignature->estimator_signed_at = Carbon::now(); // Set the current date/time
        }

        // Update customer's signature if provided
        if ($request->has('customer_signature')) {
            $estimateSignature->customer_signature = $request->customer_signature;
            $estimateSignature->customer_signed_at = Carbon::now(); // Set the current date/time
        }

        // Save the updated signature entry
        $estimateSignature->save();

        // Return success response
       
        $this->response_data["status"] = true;
        $this->response_data["data"] = "Signature added or updated successfully";
        return $this->sendJsonResponse();
    }

    
}
