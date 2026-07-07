<?php

namespace App\Http\Controllers\Api;

use App\Models\Estimate;
use App\Models\EstimateType;
use App\Models\Supplier;
use App\Models\User;
use App\Services\DownloadEmailService;
use App\Services\EstimateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Models\Membership;
use App\Models\UserInvoiceSetting;
use App\Models\Setting;
use App\Models\SettingType;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\EstimateSignature;

class ExcelExportController extends ResponseController
{
    public function index()
    {
        return view('export-excel');
    }

    public function download(Estimate $estimate){
        $user_id = \request()->get('user_id');
        if($user_id) {
            $user = User::find($user_id);
            if($user){
                $downloadEmailService = new DownloadEmailService();
                $resp = $downloadEmailService->materialListFile($estimate,$user);
                $headers = [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'Content-Disposition' => 'attachment; filename="' . $resp['filename'] . '"',
                ];
                return response()->download($resp['filepath'], $resp['filename'], $headers)->deleteFileAfterSend(true);

            }
        }
        abort(403);
    }


    public function downloadMaterialList(Estimate $estimate, $user_id = null)
    {
        return $this->materialListPdfResponse($estimate, $user_id, 'attachment');
    }

    public function previewMaterialList(Estimate $estimate, $user_id = null)
    {
        return $this->materialListPdfResponse($estimate, $user_id, 'inline');
    }

    private function materialListPdfResponse(Estimate $estimate, $user_id = null, string $disposition = 'attachment')
    {
        $user_id = \request()->get('user_id', $user_id);

        if (is_string($user_id) && str_starts_with($user_id, 'user_id=')) {
            $user_id = substr($user_id, strlen('user_id='));
        }

        if ($user_id) {
            $user = User::find($user_id);

            if ($user) {
                $downloadEmailService = new DownloadEmailService();
                $resp = $downloadEmailService->downloadMaterialListData($estimate, $user);

                $companyModel = $user->company;
                $logoAndFont = $this->getCompanyLogoAndFontColor($companyModel, $user);

                $company = [
                    'name' => $companyModel->name,
                    'address' => $companyModel->address,
                    'phone' => $user->phone,
                    'email' => $user->email,
                ];

                $pdf = Pdf::loadView('reports.customer_material_list', [
                    'user' => $user,
                    'company' => $company,
                    'color' => $logoAndFont['fontColor'],
                    'logoPath' => $logoAndFont['logoPath'],
                    'pageNumber' => 1,
                    'groupCodes' => $resp,
                ]);

                $pdfContent = $pdf->output();
                $fileSize = strlen($pdfContent);

                return response($pdfContent, 200, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => $disposition . '; filename="customer_material_list.pdf"',
                    'Content-Length' => $fileSize,
                    'X-File-Size' => $fileSize,
                        'Cache-Control' => 'no-store, no-cache, must-revalidate',
                ]);
            }
        }

        abort(403);
    }


    public function materialListSupplier(Supplier $supplier, Estimate $estimate)
    {
        $user_id = \request()->get('user_id');
        
        if ($user_id) {
            $user = User::find($user_id);
            
            if ($user) {
                $downloadEmailService = new DownloadEmailService();
                $resp = $downloadEmailService->materialListSupplierData($estimate);
                
                $company = $user->company;
                $logoAndFont = $this->getCompanyLogoAndFontColor($company, $user);
                
                $company = [
                    'name' => $company->name,
                    'address' => $company->address,
                    'phone' => $user->phone,
                    'email' => $user->email,
                ];
    
                // Data to pass into the view
                $data = [
                    'user' => $user,
                    'company' => $company,
                    'logoPath' => $logoAndFont['logoPath'],
                    'color'=>$logoAndFont['fontColor'],
                    'pageNumber' => 1,
                    'groupCodes' => $resp
                ];
    
                // Load the view and generate the PDF
                $pdf = Pdf::loadView('reports.supplier_material_list', $data); // Fix typo in view name
                
               
                // Generate the PDF content as a string
                $pdfContent = $pdf->output();
                
                // Get the file size (in bytes)
                $fileSize = strlen($pdfContent);
                
                // Define headers including the file size
                $headers = [
                    'Content-Type' => 'application/pdf', // MIME type for PDF files
                    'Content-Disposition' => 'attachment; filename="supplier_material_list.pdf"', // Force download and specify the filename
                    'Content-Length' => $fileSize, // Add the file size to the headers
                    'X-File-Size' => $fileSize, // Optional custom header for file size (if needed for tracking)
                    'Cache-Control' => 'no-store, no-cache, must-revalidate', // Optional cache control
                ];
                
                // Return the PDF with headers
                return response($pdfContent, 200, $headers);
            }
        }
    
        abort(403); // If no user found, return 403
    }


    public function downloadEstimate(Estimate $estimate,EstimateService $estimateService)
    {

    // dd('this api is for estimate download');
        $user_id = \request()->get('user_id');
        
        
        $action = \request()->get('action'); // 'preview' or 'download'
        
        if($user_id) {
            
            $user = User::find($user_id);
            if($user) {
                $sheets = $estimate->sheet()->with([
                    "Code.ProductGroup"
                ])->get();
                if (count($sheets) == 0) {
                    $this->response_data["data"] = "No Items to download";
                    return $this->sendJsonResponse();
                }
                
                
                
                $estimateSignature = EstimateSignature::where('estimate_id', $estimate->id)
                ->where('user_id', $user_id) // Filter by current user
                ->first();
               
                
                 $signatureData = [
                    'estimator_signature' => optional($estimateSignature)->estimator_signature,
                    'customer_signature' => optional($estimateSignature)->customer_signature,
                    'estimator_signed_at' => optional($estimateSignature)->estimator_signed_at,
                    'customer_signed_at' => optional($estimateSignature)->customer_signed_at,
                ];
                
                if ($action === 'preview') {

                //   $sheets = $estimate->sheet()->with([
                //         "Code.ProductGroup"
                //     ])->get();
                    
                    $sheets = $estimate->sheet()
                    ->join('codes', 'estimate_sheets.code_id', '=', 'codes.id')
                    ->join('product_groups', 'codes.product_group_id', '=', 'product_groups.id')
                    ->where('estimate_sheets.quantity', '>', 0) // Exclude records with quantity 0
                    ->select('estimate_sheets.*')
                    ->orderBy('product_groups.group_order')
                    ->get();
        
        
                    $company = $estimate->company;
                    // $user = $estimate->user;
                    $customer = $estimate->customer;
                
                    $detail = $estimateService->getDetail($estimate, $user);
                    
                    $company = $user->company;
                    
                    $logoAndFont = $this->getCompanyLogoAndFontColor($company, $user);
                    
                    // dd($logoAndFont);
                    
                     $detail = [];
                     $detail['material'] = $sheets->map(function($sheet){
                        return $sheet->total_material_cost != 0 ? round($sheet->total_material_cost, 2) : null;
                    })->filter()->values();
                    
                   $detail = [];
                    
                    
                    // Calculate material costs
                    $detail['material'] = $sheets->map(function($sheet){
                        return $sheet->total_material_cost != 0 ? round($sheet->total_material_cost, 2) : null;
                    })->filter()->values();
                    
                    // Calculate labor costs
                    $detail['labor_cost'] = $sheets->map(function($sheet){
                        return $sheet->total_labor_cost != 0 ? round($sheet->total_labor_cost, 2) : null;
                    })->filter()->values();
                    
                    // Calculate the subtotals (before tax but with markup)
                    $detail['material_sub_total'] = round(collect($detail['material'])->sum(), 2);  // Subtotal for materials
                    $detail['labor_sub_total'] = round(collect($detail['labor_cost'])->sum(), 2);   // Subtotal for labor
                    
                    // Fetch settings for the estimate
                    $settingCollection = Setting::where("company_id", $user->company_id)
                        ->where("type", SettingType::SETTING_TYPE_FOR_ESTIMATE)
                        ->get();
                    
                    // Step 1: Calculate "material markup" and add to material subtotal
                    $settingMaterialMarkup = $settingCollection->where('slug', "material_markup")->first();
                    $detail['settings']["material_markup"] = round((($settingMaterialMarkup->value ?? 0) / 100) * $detail['material_sub_total'],2);
                    
                    $detail['material_sub_total'] = round($detail['material_sub_total'] + $detail['settings']["material_markup"], 2);
                    
                    // Step 2: Calculate "labor markup" and add to labor subtotal
                    $settingLaborMarkup = $settingCollection->first(function ($setting) {
                        return in_array($setting->slug, ['labour_markup', 'labor_markup']);
                    });
                    
                    
                    $detail['settings']["labour_markup"] = (($settingLaborMarkup->value ?? 0) / 100) * $detail['labor_sub_total'];
                    
                    $detail['labor_sub_total'] = round($detail['labor_sub_total'] + $detail['settings']["labour_markup"], 2);
                    
                    // Step 3: Calculate "tax" on material after adding markup
                    $settingTax = $settingCollection->where('slug', "tax")->first();
                    $detail['settings']["tax"] = ($settingTax->value / 100) * $detail['material_sub_total'];
                    $detail['material_total'] = round($detail['material_sub_total'] + $detail['settings']["tax"], 2);
                    $detail['settings']["tax"] = round($detail['settings']["tax"],2);
                    // Step 4: Calculate the total (material + labor after markup and tax)
                    $detail['labor_total'] = $detail['labor_sub_total'];  // labor total remains same as labor_sub_total since no tax on labor
                    $detail['total'] = round($detail['material_total'] + $detail['labor_total'], 2);
                    
                    // Step 5: Initialize grand total
                    $detail['grand_total'] = floatval($detail['total']);
        
                    // Store the base cost (initial total) to apply taxes on it
                    $base_cost = $detail['total'];
                    
                    // Initialize total other taxes
                    $total_other_taxes = 0;
                    
                    // Calculate other taxes based on the base cost (detail['total'])
                    $detail["settings"]["other_tax"] = $settingCollection->whereNotIn('slug', ["material_markup", "labor_markup", "labour_markup", "tax"])
                        ->map(function($setting) use (&$detail, $base_cost, &$total_other_taxes) {
                            // Calculate each tax based on the base cost
                            $setting_tax = round(($setting->value / 100) * $base_cost, 2);
                    
                            // Add this tax to the total other taxes
                            $total_other_taxes += $setting_tax;
                    
                            // Return the formatted tax amount for display
                            return number_format($setting_tax, 2, '.', '');
                        })->values();
                    
                    // Add the total other taxes to the grand total
                    $detail['grand_total'] += $total_other_taxes;
                    
                    
                    // Round the grand total and total
                    $detail['grand_total'] = number_format(round($detail['grand_total'], 2), 2, '.', ',');
                   
                    return response()->view('reports.estimates.preview', [
                        'color'=>$logoAndFont['fontColor'],
                        'logoPath'=>$logoAndFont['logoPath'],
                        'company'=>$company,
                        'estimate' => $estimate,
                        'sheets' => $sheets,
                        'company' => $company,
                        'user' => $user,
                        'customer' => $customer,
                        'detail' => $detail,
                        'grand_total'=>$detail['grand_total'],
                        'signatureData' => $signatureData // Pass signature data to view
                    ])->header('Cache-Control', 'no-transform, no-store, no-cache, must-revalidate');
                                
                }
                
               $downloadEmailService = new DownloadEmailService();
                $resp = $downloadEmailService->createEstimateDocFile($estimate, $estimateService, $user, $action);
                
                // Check if the file exists before getting its size
                if (!file_exists($resp['filepath'])) {
                    return response()->json(['error' => 'File not found.'], 404);
                }
                
                // Get the file size
                $fileSize = filesize($resp['filepath']);
                
                $headers = [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'Content-Disposition' => 'attachment; filename="' . $resp['filename'] . '"',
                    'X-File-Size' => $fileSize, // Add the file size in bytes
                ];
                
                // Return the file as a download response with the specified headers
                $response = response()->download($resp['filepath'], $resp['filename'], $headers);
                
                // Add any additional headers here if needed
                $response->headers->set('X-File-Size', $fileSize); // Ensure the file size is set
                
                return $response->deleteFileAfterSend(true);

            }
        }
        abort(403);
    }
    
    

    public function downloadCodeBook()
    {
        
        $user_id = \request()->get('user_id');
        if ($user_id) {
            $user = User::find($user_id);
            if ($user) {
                $company = $user->company;
                
                 $logoAndFont = $this->getCompanyLogoAndFontColor($company, $user);
               
               
                $codes = $company->codes()->with(["supplier", "productGroup"])->whereNull('deleted_at')->get();
                // $codes = $company->codes()->with(["supplier", "productGroup"])->get();
                $groupCodes = $codes->groupBy("productGroup.group_name");
        
               
                // if (count($codes) == 0) {
                //     $this->response_data["message"] = "No codes to download";
                //     $this->response_data["data"] = "No codes to download";
                //     return $this->sendJsonResponse();
                // }
            
    
                $totalPages = 1; 
                // Prepare data for the PDF
                $data = [
                    'user' => $user,
                    'company' => $company, // Pass company to the view
                    'codes' => $codes,
                    'color' => $logoAndFont['fontColor'],
                    'groupCodes'=>$groupCodes,
                    'logoPath' => $logoAndFont['logoPath'], // Pass logo path if needed in the view
                    'pageNumber' => 1,
                    'totalPages' => $totalPages // Pass the total pages
                ];
                
              
                $pdf = Pdf::loadView('reports.codebook', $data);
            
                // Generate the PDF content as a string
                $pdfContent = $pdf->output();
                
                // Get the file size (in bytes)
                $fileSize = strlen($pdfContent);
                
                // Define headers including the file size
                $headers = [
                    'Content-Type' => 'application/pdf', // MIME type for PDF files
                    'Content-Disposition' => 'attachment; filename="Codebook.pdf"', // Force download and specify the filename
                    'Content-Length' => $fileSize, // Add the file size to the headers
                    'X-File-Size' => $fileSize, // Optional custom header for file size (if needed for tracking)
                    'Cache-Control' => 'no-store, no-cache, must-revalidate', // Optional cache control
                ];
                
                // Return the PDF with headers
                return response($pdfContent, 200, $headers);


            }
        }
    
        abort(403);
    }

    // public function salesReports(Request $request)
    // {
    //     $user_id = \request()->get('user_id');
    //     if($user_id) {
    //         $user = User::find($user_id);
    //         if ($user) {
    //             $validator = Validator::make($request->all(), [
    //                 'from' => ['required', 'date', 'date_format:Y-m-d', 'before_or_equal:to'],
    //                 'to' => ['required', 'date', 'date_format:Y-m-d', 'after_or_equal:from'],
    //             ]);

    //             if ($validator->fails()){
    //                 foreach ($validator->errors()->all() as $msg) {
    //                     if (strlen(trim($msg)) > 1){
    //                         $this->response_data["message"] = $msg;
    //                         return $this->sendJsonResponse();
    //                     }
    //                 }
    //             }
    //             $estimateService = new EstimateService();
    //             $count = $estimateService->getSalesReportCount($user, $request->from, $request->to);
    //             if($count > 0) {
    //                 $downloadEmailService = new DownloadEmailService();
    //                 $resp = $downloadEmailService->createSalesReport($user, $request->from, $request->to);
    //                 $headers = [
    //                     'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    //                     'Content-Disposition' => 'attachment; filename="' . $resp['filename'] . '"',
    //                 ];
    //                 // Return the file as a download response
    //                 return response()->download($resp['filepath'], $resp['filename'], $headers)->deleteFileAfterSend(true);
    //             }else{
    //                 $this->response_data["message"] = "No data exists";
    //                 return $this->sendJsonResponse();
    //             }
    //         }
    //     }
    //     abort(403);
    // }
    
    public function salesReports(Request $request)
    {
        $user_id = \request()->get('user_id');
      
        if($user_id) {
            $user = User::find($user_id);
            if ($user) {
                $validator = Validator::make($request->all(), [
                    'from' => ['required', 'date', 'date_format:Y-m-d', 'before_or_equal:to'],
                    'to' => ['required', 'date', 'date_format:Y-m-d', 'after_or_equal:from'],
                ]);
    
                if ($validator->fails()) {
                    foreach ($validator->errors()->all() as $msg) {
                        if (strlen(trim($msg)) > 1) {
                            $this->response_data["message"] = $msg;
                            return $this->sendJsonResponse();
                        }
                    }
                }
    
                $estimateService = new EstimateService();
                $count = $estimateService->getSalesReportCount($user, $request->from, $request->to);
       
                    // Generate the report content (replace with actual report data logic)
                     $downloadEmailService = new DownloadEmailService();
                    $reportData = $downloadEmailService->getSalesReportData($user, $request->from, $request->to);
                     
                   
                     $company = $user->company;
                     $logoAndFont = $this->getCompanyLogoAndFontColor($company, $user);
                     
                    // Generate the PDF
                    $pdf = PDF::loadView('reports.sales_report', [
                        'company' => $reportData['company'],
                        'sales' => $reportData['sales'],
                        'fromDate' => $request->from,
                        'toDate' => $request->to,
                        'estimator' => @$user->first_name . ' ' . @$user->last_name,
                        'color' => $logoAndFont['fontColor'],
                        'user' => $user,
                    ]);
                    
                    // Add page numbering to the PDF
                    $dom_pdf = $pdf->getDomPDF();
                    $canvas = $dom_pdf->get_canvas();
                    $pageWidth = $canvas->get_width();
                    $pageHeight = $canvas->get_height();
                    $canvas->page_text($pageWidth - 85, 48, " {PAGE_NUM} of {PAGE_COUNT}", null, 10, array(0, 0, 0));
                    
                    // Generate the PDF content as a string
                    $pdfContent = $pdf->output();
                    
                    // Calculate the file size
                    $fileSize = strlen($pdfContent);
                    
                    // Set the file name for the PDF
                    $filename = 'sales_report_' . $user->id . '_' . now()->format('Y-m-d') . '.pdf';
                    
                    // Define headers including file size
                    $headers = [
                        'Content-Type' => 'application/pdf', // MIME type for PDF files
                        'Content-Disposition' => 'attachment; filename="' . $filename . '"', // Force download with the filename
                        'Content-Length' => $fileSize, // Include file size in the headers
                        'X-File-Size' => $fileSize, // Optional custom header for file size tracking
                        'Cache-Control' => 'no-store, no-cache, must-revalidate', // Optional cache control
                    ];

                        // Return the PDF with headers
                        return response($pdfContent, 200, $headers);

                    
                    
                
                
            }
        }
        abort(403);
    }
    
    
    // protected function getCompanyLogoAndFontColor($company, $user)
    // {
    //     // Default values
    //     $fontColor = '000000'; // Default font color
    //     $logoPath = asset('assets/img/logo-old.png'); // Default logo path
    
    //     // Fetch invoice settings for the company
    //     $invoiceSetting = UserInvoiceSetting::where("company_id", $company->id)->first();
    
    //     // If the user has a subscription and invoice settings exist
    //     if ($invoiceSetting && $user->hasSubscription(Membership::SUBSCRIPTION_INVOICE)) {
    //         // Check for a custom logo
    //         if ($invoiceSetting->logo) {
    //             $logoPath = asset($invoiceSetting->logo); // Use custom logo
    //         }
    //         // Check for a custom font color
    //         if ($invoiceSetting->color) {
    //             $fontColor = $invoiceSetting->color; // Use custom font color
    //         }
    //     }
    
    //     // Return both values
    //     return [
    //         'fontColor' => $fontColor,
    //         'logoPath' => $logoPath,
    //     ];
    // }

    protected function getCompanyLogoAndFontColor($company, $user)
{
    // Default values
    $fontColor = '000000'; // Default font color
    $logoPath = asset('assets/img/logo-old.png'); // Default logo path

    // Fetch invoice settings for the company
    $invoiceSetting = UserInvoiceSetting::where("company_id", $company->id)->first();

    // If the user has a subscription and invoice settings exist
    if ($invoiceSetting && $user->hasSubscription(Membership::SUBSCRIPTION_INVOICE)) {
        // Check for a custom logo
        if ($invoiceSetting->logo) {
            $file = str_ireplace("app/", "", $invoiceSetting->logo);
            $logoPath = asset($file); // Use custom logo
        }
        // Check for a custom font color
        if ($invoiceSetting->color) {
            $fontColor = $invoiceSetting->color; // Use custom font color
        }
    }

    // Return both values
    return [
        'fontColor' => $fontColor,
        'logoPath' => $logoPath,
    ];
}


}
