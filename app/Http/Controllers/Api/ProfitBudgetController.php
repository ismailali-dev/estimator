<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\EstimateMail;
use App\Models\Estimate;
use App\Models\EstimateSheet;
use App\Models\ProductGroup;
use App\Models\SettingType;
use App\Models\Setting;
use App\Models\Supplier;
use App\Models\EstimateSheetSupplier;
use App\Services\DownloadEmailService;
use App\Services\EstimateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Models\ProfitBudgetEstimateSetting;
use Illuminate\Support\Str;
use App\Models\User;

class ProfitBudgetController extends ResponseController
{
    //

    public function index(Estimate $estimate){
        
        if(empty($estimate->contract_price)){
            $this->response_data["message"] = "Estimate is not saved yet";
        }else{
            $this->response_data["status"] = true;
            $data = $estimate->only(["contract_price","extra_work_orders","id","pb_number"]);
            
            
            $this->response_data["data"] = $data;

        }
        return  $this->sendJsonResponse();
    }

    public function setExtraWorkOrders(Estimate $estimate,Request $request){
        $validator = Validator::make($request->all(), [
            'contract_price' => 'required|numeric',
            'extra_work_order' => 'sometimes|numeric',
        ]);
        if ($validator->fails()){
            foreach ($validator->errors()->all() as $msg) {
                if (strlen(trim($msg)) > 1){
                    $this->response_data["message"] = $msg;
                    return $this->sendJsonResponse();
                }
            }
        }
        $updated = $estimate->update([
            "contract_price" => $request->contract_price,
            "extra_work_orders" => $request->extra_work_order
        ]);
        if($updated){
            $estimate->refresh();
            $this->response_data["status"] = true;
            $this->response_data["data"] = $estimate->only(["contract_price","extra_work_orders","id","pb_number"]);
        }else{
            $this->response_data["message"] = "Something went wrong";
        }
        return $this->sendJsonResponse();
    }

   
   
   public function list(Estimate $estimate)
{
    $grouped = [];

    $estimateSheets = EstimateSheet::with(["Code.ProductGroup"])
        ->where("estimate_id", $estimate->id)
        ->get();

    foreach ($estimateSheets as $sheet) {
        if ($sheet->quantity == 0) continue;

        $category = optional($sheet->Code->ProductGroup)->group_name ?? 'Unknown';
        $sheetId = $sheet->id;

        $materialSupplier = optional($sheet->supplierByType('Material'))->supplier;
        $laborSupplier = optional($sheet->supplierByType('Labor'))->supplier;

        if (!isset($grouped[$category])) {
            $grouped[$category] = [
                'category' => $category,
                'Material' => null,
                'Labor' => null
            ];
        }

        $updateRow = function (&$group, $type, $sheet, $supplier) use ($sheetId) {
            $budgetKey = $type === 'Material' ? 'actual_material_cost' : 'actual_labor_cost';
            $actualKey = $type === 'Material' ? 'actual_user_input_material_cost' : 'actual_user_input_labor_cost';

            if (!empty($sheet->{$budgetKey})) {
                if (!$group[$type]) {
                    $group[$type] = [
                        "estimate_sheet_id" => $sheetId,
                        "type" => $type,
                        "supplier_id" => $supplier->id ?? null,
                        "supplier_name" => $supplier->full_name ?? null,
                        "estimate_sheet_ids" => [],
                        "budget" => 0,
                        "actual" => 0,
                        "difference" => 0,
                        "max_purchasing_cost" => 0,
                    ];
                }

                $group[$type]["estimate_sheet_ids"][] = $sheetId;
                $group[$type]["budget"] += $sheet->{$budgetKey};
                $group[$type]["actual"] += $sheet->{$actualKey};
                $group[$type]["difference"] = $group[$type]["budget"] - $group[$type]["actual"];
                $group[$type]["max_purchasing_cost"] = max(
                    $group[$type]["max_purchasing_cost"],
                    $sheet->purchasing_cost
                );
            }
        };

        $updateRow($grouped[$category], 'Material', $sheet, $materialSupplier);
        $updateRow($grouped[$category], 'Labor', $sheet, $laborSupplier);
    }

    // 🔁 Final loop: If actual = 0, assign budget as fallback
    foreach ($grouped as &$group) {
        foreach (['Material', 'Labor'] as $type) {
            if (!empty($group[$type]) && $group[$type]['actual'] == 0) {
                $group[$type]['actual'] = $group[$type]['budget'];
                $group[$type]['difference'] = 0;
            }
        }
    }

    $this->response_data["status"] = true;
    $this->response_data["data"] = [
        "profit_budget" => $estimate->only([
            "contract_price", "extra_work_orders", "key", "pb_number", "id"
        ]),
        "total" => round($estimate->total, 2),
        "sheets" => array_values($grouped)
    ];

    return $this->sendJsonResponse();
}

    
    public function updatemergedActualCost(Request $request, Estimate $estimate)
    {
        $request->validate([
            'records' => 'required|array',
            'records.*.estimate_sheet_id' => 'required|integer|exists:estimate_sheets,id',
            'records.*.estimate_sheet_ids' => 'required|array',
            'records.*.estimate_sheet_ids.*' => 'required|integer|exists:estimate_sheets,id',
            'records.*.actual_amount' => 'required|numeric',
            'records.*.type' => 'required|in:Material,Labor',
            'records.*.supplier_id' => 'nullable|integer|exists:suppliers,id',
            'records.*.supplier_name' => 'nullable|string|max:255',
        ]);
    
        $currentUser = $request->user();
    
        foreach ($request->records as $record) {
            $actualAmount = $record['actual_amount'];
            $type = $record['type'];
            $supplierId = $record['supplier_id'] ?? null;
            $supplierName = $record['supplier_name'] ?? null;
            $mainEstimateSheetId = $record['estimate_sheet_id'];
    
            // Supplier logic
            if (!$supplierId && $supplierName) {
                $existingSupplier = \App\Models\Supplier::withTrashed()
                    ->where('full_name', $supplierName)
                    ->where('user_id', $currentUser->id)
                    ->first();
    
                if ($existingSupplier) {
                    if ($existingSupplier->trashed()) {
                        $existingSupplier->restore();
                    }
                    $supplierId = $existingSupplier->id;
                } else {
                    $supplier = new \App\Models\Supplier();
                    $supplier->full_name = $supplierName;
                    $supplier->user_id = $currentUser->id;
                    $supplier->save();
                    $supplierId = $supplier->id;
                }
            }
    
            // Update the main estimate sheet
            $mainEstimateSheet = \App\Models\EstimateSheet::find($mainEstimateSheetId);
            if ($mainEstimateSheet) {
                if ($type === 'Material') {
                    $mainEstimateSheet->actual_user_input_material_cost = $actualAmount;
                } elseif ($type === 'Labor') {
                    $mainEstimateSheet->actual_user_input_labor_cost = $actualAmount;
                }
                $mainEstimateSheet->save();
            }
    
            // Update all other estimate_sheet_ids (including supplier if available)
            foreach ($record['estimate_sheet_ids'] as $estimateSheetId) {
                $estimateSheet = \App\Models\EstimateSheet::find($estimateSheetId);
    
                if ($estimateSheet) {
                    if ($supplierId) {
                        \App\Models\EstimateSheetSupplier::updateOrCreate(
                            [
                                'estimate_sheet_id' => $estimateSheetId,
                                'type' => $type,
                            ],
                            [
                                'supplier_id' => $supplierId,
                                'user_id' => $currentUser->id,
                            ]
                        );
                    }
                }
            }
        }
    
        $this->response_data["status"] = true;
        $this->response_data["message"] = "Actual costs and supplier updated successfully.";
        $this->response_data["data"] = [];
    
        return $this->sendJsonResponse();
    }


 

public function getAllProfitBudgetEstimateSettings(Request $request,Estimate $estimate, SettingType $type)
{
       $currentUser = $request->user(); // Get the current user

        // Determine which user ID to use for settings
        if ($currentUser->hasFullModuleAccess()) {
            $userForSettings = $currentUser->is_admin 
                ? $currentUser 
                : User::find($currentUser->parent_id);
        } else {
            $userForSettings = $currentUser;
        }
        
        // Now continue with your existing logic
        $company = $userForSettings->company;
        $companyId = $company->id;
        
        $globalSettings = Setting::where('user_id', $userForSettings->id)
            ->where('company_id', $companyId)
            ->where('type', $type->id)
            ->orderBy('sort_order')
            ->get();
            
            
    
        if ($globalSettings->isEmpty()) {
            $defaults = $this->defaultSettingsForType($type->id);
            $newGlobalSettings = [];
    
            foreach ($defaults as $index => $value) {
                if (is_int($index)) {
                    $title = $value;
                    $slug = Str::slug($value);
                    $sortOrder = $index + 1;
                } else {
                    $title = $index;
                    $slug = $value;
                    $sortOrder = array_search($index, array_keys($defaults)) + 1;
                }
    
                $newGlobalSettings[] = [
                    'user_id' => $user->id,
                    'company_id' => $companyId,
                    'title' => $title,
                    'slug' => $slug,
                    'value' => 0,
                    'type' => $type->id,
                    'sort_order' => $sortOrder,
                    'is_edit' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
    
            Setting::insert($newGlobalSettings);
    
            $globalSettings = Setting::where('user_id', $user->id)
                ->where('company_id', $companyId)
                ->where('type', $type->id)
                ->orderBy('sort_order')
                ->get();
        }
    
        // Step 1.5: Cleanup ProfitBudgetEstimateSettings not in global settings
        $validSlugs = $globalSettings->pluck('slug')->toArray();
    
        ProfitBudgetEstimateSetting::where('estimate_id', $estimate->id)
            ->where('type', $type->id)
            ->whereNotIn('slug', $validSlugs)
            ->delete();
    
        // Step 2: Get already inserted slugs for this estimate
        $existingSlugs = ProfitBudgetEstimateSetting::where('estimate_id', $estimate->id)
            ->where('type', $type->id)
            ->pluck('slug')
            ->toArray();
    
        $contractTotal = $estimate->contract_price + $estimate->extra_work_orders;
        $estimatedAnnualJobCost = optional($company->userEstimatedAnnualJobCosts->first())->estimated_annual_job_cost;
    
        // Step 3: Insert only new and non-zero settings
        $filteredGlobalSettings = $globalSettings->filter(function ($setting) use ($existingSlugs) {
            return $setting->value != 0 && !in_array($setting->slug, $existingSlugs);
        });
    
        if ($filteredGlobalSettings->isNotEmpty()) {
            $profitSettings = $filteredGlobalSettings->map(function ($setting) use ($estimate, $contractTotal, $estimatedAnnualJobCost) {
                $percentage = $estimatedAnnualJobCost > 0 ? ($setting->value / $estimatedAnnualJobCost) : 0;
                $budgetedValue = round($percentage * $contractTotal, 2);
    
                return [
                    'estimate_id' => $estimate->id,
                    'user_id' => $setting->user_id,
                    'company_id' => $setting->company_id,
                    'title' => $setting->title,
                    'slug' => $setting->slug,
                    'type' => $setting->type,
                    'actual_cost' => $budgetedValue,
                    'difference' => 0,
                    'sort_order' => $setting->sort_order,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })->toArray();
    
            ProfitBudgetEstimateSetting::insert($profitSettings);
        }
    
        // Step 4: Final Fetch
        $settings = ProfitBudgetEstimateSetting::where('estimate_id', $estimate->id)
            ->where('type', $type->id)
            ->get();
    
        $globalSettingsMap = $globalSettings->keyBy('slug');
    
        $totalActual = 0;
        $totalBudget = 0;
    
        $settings->transform(function ($setting) use ($globalSettingsMap, $contractTotal, $estimatedAnnualJobCost, &$totalActual, &$totalBudget) {
            $settingValue = optional($globalSettingsMap->get($setting->slug))->value ?? 0;
    
            $percentage = $estimatedAnnualJobCost > 0 ? ($settingValue / $estimatedAnnualJobCost) : 0;
            $budgetedValue = $percentage * $contractTotal;
            $budgetedValue = round($budgetedValue, 2);
    
            $setting->budgeted_value = $budgetedValue;
            $setting->sort_order = optional($globalSettingsMap->get($setting->slug))->sort_order ?? $setting->sort_order;
    
            $totalActual += $setting->actual_cost;
            $totalBudget += $budgetedValue;
    
            return $setting;
        });
    
        // Sort again by sort_order
        $settings = $settings->sortBy('sort_order')->values();
    
        return response()->json([
            'status' => true,
            'data' => [
                'type_id' => $type->id,
                'type_title' => $type->title,
                'total_actual_cost' => round($totalActual, 2),
                'total_budgeted_value' => round($totalBudget, 2),
                'settings' => $settings,
            ]
        ]);
    }




private function defaultSettingsForType($typeId)
{
    return [
        1 => [ // Global Estimate Settings
            'Material Markup' => 'material_markup',
            'Labor Markup' => 'labour_markup',
            'Tax' => 'tax',
        ],
        3 => [ // Direct Annual Job Cost
            'Plans',
            'Permits',
            'Engineering',
            'Portapoty',
            'Fencing',
            'Geological',
            'Aqmd',
            'Fees',
            'Commission',
            'AQMD',
            'Misc',
        ],
        4 => [ // In Direct Annual Job Cost
            'Office Rent',
            'Utilities',
            'Internet',
            'Legal',
            'Advertising',
            'Vehicle Cost',
            'Telephone',
            'Stationary',
            'Workers Comp',
            'Liability',
            'Misc',
        ],
        5 => [ // In Direct Annual Employee Cost
            'Secretary',
            'Sales Manager',
            'Office Manager',
            'Project Manager',
            'Superintendent',
        ],
        // 6=>[
        //     "Journey Man",
        //     "Apprentice", 
        //     "Helper",
        //     "Crew 1",
        //     "Apprentice", 
        //     "Helper" 
        //     ]
    ][$typeId] ?? [];
}



    function setActualAmountEstimateSheet(Estimate $estimate,EstimateSheet $estimateSheet,Request $request){
        $updated = false;
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:material,labor',
            'actual' => 'required',
        ]);
        if ($validator->fails()){
            foreach ($validator->errors()->all() as $msg) {
                if (strlen(trim($msg)) > 1){
                    $this->response_data["message"] = $msg;
                    return $this->sendJsonResponse();
                }
            }
        }
        if($request->type == "material"){
            $updated = $estimateSheet->update([
                "actual_material_cost" => $request->actual
            ]);
        }
        if($request->type == "labor"){
            $updated = $estimateSheet->update([
                "actual_labor_cost" => $request->actual
            ]);
        }

        $this->response_data["status"] = true;
        $this->response_data["data"] = $estimateSheet->refresh();
        return $this->sendJsonResponse();

    }
    
    
    public function updateProfitBudgetSettingsActualCost(Request $request)
    {
          
        $request->validate([
            'settings' => 'required|array',
            'settings.*.id' => 'required|exists:profit_budget_estimate_settings,id',
            'settings.*.actual_cost' => 'required|numeric|min:0',
        ]);
 
        foreach ($request->settings as $item) {
            ProfitBudgetEstimateSetting::where('id', $item['id'])->update([
                'actual_cost' => $item['actual_cost'],
            ]);
        }
    
        return response()->json([
            'status' => true,
            'message' => 'Actual costs updated successfully.',
        ]);
    }
    
    
    
private function initializeProfitBudgetSettings(Estimate $estimate, $typeId)
{
    $user = auth()->user();
    $company = $user->company;
    $companyId = $company->id;

    $globalSettings = Setting::where('user_id', $user->id)
        ->where('company_id', $companyId)
        ->where('type', $typeId)
        ->orderBy('sort_order')
        ->get();

    if ($globalSettings->isEmpty()) {
        $defaults = $this->defaultSettingsForType($typeId);
        $newGlobalSettings = [];

        foreach ($defaults as $index => $value) {
            $title = is_int($index) ? $value : $index;
            $slug = is_int($index) ? Str::slug($value) : $value;
            $sortOrder = $index + 1;

            $newGlobalSettings[] = [
                'user_id' => $user->id,
                'company_id' => $companyId,
                'title' => $title,
                'slug' => $slug,
                'value' => 0,
                'type' => $typeId,
                'sort_order' => $sortOrder,
                'is_edit' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        Setting::insert($newGlobalSettings);

        $globalSettings = Setting::where('user_id', $user->id)
            ->where('company_id', $companyId)
            ->where('type', $typeId)
            ->orderBy('sort_order')
            ->get();
    }

    $existingSlugs = ProfitBudgetEstimateSetting::where('estimate_id', $estimate->id)
        ->where('type', $typeId)
        ->pluck('slug')
        ->toArray();

    $contractTotal = $estimate->contract_price + $estimate->extra_work_orders;
    $estimatedAnnualJobCost = optional($company->userEstimatedAnnualJobCosts->first())->estimated_annual_job_cost;

    $filteredGlobalSettings = $globalSettings->filter(function ($setting) use ($existingSlugs) {
        return $setting->value != 0 && !in_array($setting->slug, $existingSlugs);
    });

    $totalBudget = 0;

    if ($filteredGlobalSettings->isNotEmpty()) {
        $profitSettings = $filteredGlobalSettings->map(function ($setting) use ($estimate, $contractTotal, $estimatedAnnualJobCost, &$totalBudget) {
            $percentage = $estimatedAnnualJobCost > 0 ? ($setting->value / $estimatedAnnualJobCost) : 0;
            $budgetedValue = round($percentage * $contractTotal, 2);
            $totalBudget += $budgetedValue;

            return [
                'estimate_id' => $estimate->id,
                'user_id' => $setting->user_id,
                'company_id' => $setting->company_id,
                'title' => $setting->title,
                'slug' => $setting->slug,
                'type' => $setting->type,
                'actual_cost' => $budgetedValue,
                'difference' => 0,
                'sort_order' => $setting->sort_order,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        })->toArray();

        ProfitBudgetEstimateSetting::insert($profitSettings);
    }

    // Calculate total actual cost (from DB)
    $totalActual = ProfitBudgetEstimateSetting::where('estimate_id', $estimate->id)
        ->where('type', $typeId)
        ->sum('actual_cost');

    return [
        'total_budget' => $totalBudget,
        'total_actual' => $totalActual,
    ];
}


    public function viewTotal(Estimate $estimate)
    {
        $this->response_data["status"] = true;

        $user = auth()->user();
        $company = $user->company;
        $companyId = $company->id;
    
        $totalBudget = 0;
        $totalActual = 0;
        
        // Types you want to loop over
        $types = [3, 4, 5];
        
        foreach ($types as $typeId) {
            $result = $this->initializeProfitBudgetSettings($estimate, $typeId);
        
            $totalBudget += $result['total_budget'] ?? 0;
            $totalActual += $result['total_actual'] ?? 0;
        }
        
      
        $contractTotal = $estimate->contract_price + $estimate->extra_work_orders;
        
        $budgetIndirect = round($totalActual, 2);
        
        $actualIndirect = $budgetIndirect;
        if( $budgetIndirect == 0 ){
            $actualIndirect = round($totalActual, 2);
        }
        
       
       
       
        $diffIndirect = $budgetIndirect-$actualIndirect;
        
     
    
       $estimateSheets = EstimateSheet::with(["Code.ProductGroup"])
        ->where("estimate_id", $estimate->id)
        ->get();

        $grouped = [];
        $projectCostBudget = 0;
        $projectCostActual = 0;
        
        foreach ($estimateSheets as $sheet) {
            $category = optional($sheet->Code->ProductGroup)->group_name ?? 'Unknown';
            $sheetId = $sheet->id;
        
            // Supplier objects
            $materialSupplierObj = optional($sheet->supplierByType('Material'))->supplier;
            $laborSupplierObj = optional($sheet->supplierByType('Labor'))->supplier;
        
            // Group index for this category
            $index = collect($grouped)->search(fn($g) => $g["category"] === $category);
            if ($index === false) {
                $grouped[] = [
                    "category" => $category,
                    "Material" => null,
                    "Labor" => null
                ];
                $index = count($grouped) - 1;
            }
        
            // Material Row
            if (!empty($sheet->material_cost)) {
                if (!$grouped[$index]["Material"]) {
                    $grouped[$index]["Material"] = [
                        "estimate_sheet_id" => $sheetId,
                        "type" => "Material",
                        "supplier_id" => $materialSupplierObj->id ?? null,
                        "supplier_name" => $materialSupplierObj->full_name ?? null,
                        "estimate_sheet_ids" => [],
                        "budget" => 0,
                        "actual" => 0,
                        "difference" => 0,
                        "max_purchasing_cost" => 0,
                    ];
                }
        
                $grouped[$index]["Material"]["estimate_sheet_ids"][] = $sheetId;
                $grouped[$index]["Material"]["budget"] += $sheet->actual_material_cost;
                $grouped[$index]["Material"]["actual"] += $sheet->actual_user_input_material_cost;
                $grouped[$index]["Material"]["difference"] =
                    $grouped[$index]["Material"]["budget"] - $grouped[$index]["Material"]["actual"];
                $grouped[$index]["Material"]["max_purchasing_cost"] = max(
                    $grouped[$index]["Material"]["max_purchasing_cost"],
                    $sheet->purchasing_cost
                );
            }
        
            // Labor Row
            if (!empty($sheet->labor_cost)) {
                if (!$grouped[$index]["Labor"]) {
                    $grouped[$index]["Labor"] = [
                        "estimate_sheet_id" => $sheetId,
                        "type" => "Labor",
                        "supplier_id" => $laborSupplierObj->id ?? null,
                        "supplier_name" => $laborSupplierObj->full_name ?? null,
                        "estimate_sheet_ids" => [],
                        "budget" => 0,
                        "actual" => 0,
                        "difference" => 0,
                        "max_purchasing_cost" => 0,
                    ];
                }
        
                $grouped[$index]["Labor"]["estimate_sheet_ids"][] = $sheetId;
                $grouped[$index]["Labor"]["budget"] += $sheet->actual_labor_cost;
                $grouped[$index]["Labor"]["actual"] += $sheet->actual_user_input_labor_cost;
                $grouped[$index]["Labor"]["difference"] =
                    $grouped[$index]["Labor"]["budget"] - $grouped[$index]["Labor"]["actual"];
                $grouped[$index]["Labor"]["max_purchasing_cost"] = max(
                    $grouped[$index]["Labor"]["max_purchasing_cost"],
                    $sheet->purchasing_cost
                );
            }
        }
        
        // Update actual = budget where actual is zero
        foreach ($grouped as &$group) {
            if (!empty($group['Material']) && $group['Material']['actual'] == 0) {
                $group['Material']['actual'] = $group['Material']['budget'];
                $group['Material']['difference'] = 0;
            }
        
            if (!empty($group['Labor']) && $group['Labor']['actual'] == 0) {
                $group['Labor']['actual'] = $group['Labor']['budget'];
                $group['Labor']['difference'] = 0;
            }
        }
        unset($group); // break reference
        
        // Calculate totals
        foreach ($grouped as $group) {
            if (!empty($group["Material"])) {
                $projectCostBudget += $group["Material"]["budget"];
                $projectCostActual += $group["Material"]["actual"];
            }
        
            if (!empty($group["Labor"])) {
                $projectCostBudget += $group["Labor"]["budget"];
                $projectCostActual += $group["Labor"]["actual"];
            }
        }
    
       
    
    
        $projectCostBudget = round($projectCostBudget, 2);
        $projectCostActual = round($projectCostActual, 2);
        $diffProjectCost = $projectCostBudget - $projectCostActual;
    
        // Total Job Cost Expense
        $jobCostBudget = $budgetIndirect + $projectCostBudget;
        $jobCostActual = $actualIndirect + $projectCostActual;
        $diffJobCost = $jobCostBudget - $jobCostActual;
    
        // Profit Calculations
        $profitBudget = $contractTotal - $jobCostBudget;
        $profitActual = $contractTotal - $jobCostActual;
    
        $profitBudgetPercentage = $contractTotal > 0 ? ($profitBudget / $contractTotal) * 100 : 0;
        $profitActualPercentage = $contractTotal > 0 ? ($profitActual / $contractTotal) * 100 : 0;
        $diffProfitPercentage =  $profitActualPercentage-$profitBudgetPercentage;
    
        $this->response_data["data"] = [
          
                "budget_direct_indirect" => $budgetIndirect,
                "actual_direct_indirect" => $actualIndirect,
                "diff_btween_direct_indirect_budget" => $diffIndirect,
    
                "project_cost_budget" => $projectCostBudget,
                "project_cost_actual" => $projectCostActual,
                "diff_btween_project_cost_budget_project_cost_actual" => $diffProjectCost,
    
                "job_cost_expense_budget" => $jobCostBudget,
                "job_cost_expense_actual" => $jobCostActual,
                "diff_between_job_cost_expense_budget_job_cost_expense_actual" => $diffJobCost,
    
                "profit_total_budget" => $profitBudget,
                "profit_total_actual" => $profitActual,
                "profit_total_budget_percentage" => round($profitBudgetPercentage, 2),
                "profit_total_actual_percentage" => round($profitActualPercentage, 2),
                "diff_between_profit_total_budget_percentage_profit_total_actual_percentage" => round($diffProfitPercentage, 2)
            
        ];
    
        return $this->sendJsonResponse();
    }


       public function materialList(Estimate $estimate) {
        
        $this->response_data["status"] = true;
    
        // Fetch the data with relationships
        $data = EstimateSheet::with(["Code.ProductGroup", "Code.supplier"])
            ->where("material_cost", "!=", 0)
            ->where("estimate_id", $estimate->id)
            ->where("quantity", ">", 0) // Exclude records with quantity 0
            ->get()
            ->groupBy("Code.product_group_id")
            ->sortBy(function ($group) {
                // Sort by Code.ProductGroup.group_order in each group
                return optional($group->first()->Code->ProductGroup)->group_order;
            })
            ->map(function($group) {
                $totalMatCost = $group->sum(function ($item) {
                    return ($item->material_cost * $item->quantity);
                });
                $actualMatCost = $group->where('quantity', '>', 0)->sum('actual_material_cost');
    
                return [
                    "estimate_sheet_id" => optional($group->first())->id,
                    "product_group_id" => optional($group->first()->Code)->product_group_id,
                    "category" => optional($group->first()->Code->ProductGroup)->group_name,
                    "supplier" => optional($group->first()->Code->supplier)->full_name,
                    "codes" => $group->where('quantity', '>', 0)->pluck("code_id")->count(),
                    "allowance" => round($totalMatCost, 2),
                    "total" => round($actualMatCost, 2),
                    "difference" => round(($totalMatCost - $actualMatCost), 2),
                ];
            });
    
        $this->response_data["data"] = [
            "key" => $estimate->key,
            "list" => $data->values()
        ];
    
        return $this->sendJsonResponse();
    }



    function materialDetail(Estimate $estimate,ProductGroup $group){
        
        
        $data = [];
        $data["title"] = $group->group_name;
        $allowance = 0;
        $total = 0;
       
        $estimateSheets = EstimateSheet::with(["Code", "Code.ProductGroup", "Code.supplier"])
        ->where("material_cost", "!=", 0)
        ->where("estimate_id", $estimate->id)
        ->where("quantity", ">", 0) // Exclude records with quantity of 0
        ->whereHas('Code', function ($query) use ($group) {
            $query->where('product_group_id', $group->id);
        })
        ->get();
       

        $data["data"] = $estimateSheets->map(function($estimateSheet) use(&$allowance,&$total){
                $allowance += $estimateSheet->total_material_cost_without_misc;
                $total += $estimateSheet->actual_material_cost;
                return [
                    "estimate_sheet_id" => $estimateSheet->id,
                    "id" => $estimateSheet->code_id,
                    "code" => $estimateSheet->Code->code_name,
                    "qty" => $estimateSheet->quantity,
                    "units" => $estimateSheet->Code->unit_of_measure,
                    "unit_price" => round($estimateSheet->material_cost,2),
                    "allowance" => round($estimateSheet->total_material_cost_without_misc,2),
                    "total" => round($estimateSheet->actual_material_cost,2),
                    // "purchasing_cost" => round($estimateSheet->purchasing_cost,2),
                    "difference" => round(($estimateSheet->total_material_cost_without_misc - $estimateSheet->actual_material_cost),2),
                    "description" => optional($estimateSheet->Code)->description,
                ];

            });

        $data["allowance"] = round($allowance,2);
        $data["total"] = round($total,2);
        $data["difference"] = round(($allowance - $total),2);
        $this->response_data["data"] = $data;
        $this->response_data["status"] = true;
        return $this->sendJsonResponse();
    }
    
   public function updateActualCost(Request $request, Estimate $estimate)
    {
        // Validate the request data
        $validator = Validator::make($request->all(), [
            'codes' => 'required|array', // Ensure codes is an array
            'codes.*.estimate_sheet_id' => 'required|exists:estimate_sheets,id', // Each estimate_sheet_id must exist in the estimate_sheets table
            'codes.*.code_id' => 'required|exists:codes,id', // Each code_id must exist
            'codes.*.purchasing_cost' => 'required|numeric|min:0', // Each purchasing cost must be a non-negative number
            'codes.*.type' => 'required|in:material,labor' // Ensure type is either material or labor
        ]);
    
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $msg) {
                if (strlen(trim($msg)) > 1) {
                    $this->response_data["message"] = $msg;
                    return $this->sendJsonResponse();
                }
            }
        }
    
        $codes = $request->input('codes'); // Get the array of codes
    
        foreach ($codes as $code) {
            // Find the specific EstimateSheet using estimate_sheet_id
            $estimateSheet = EstimateSheet::where('id', $code['estimate_sheet_id'])
                ->where('estimate_id', $estimate->id)
                ->where('code_id', $code['code_id'])
                ->first();
    
            if (!$estimateSheet) {
                return response()->json([
                    'status' => false,
                    'message' => 'EstimateSheet not found for estimate_sheet_id: ' . $code['estimate_sheet_id'],
                ], 404);
            }
    
            // Update the purchasing cost based on the type
            if ($code['type'] === 'material') {
                $estimateSheet->actual_material_cost = $code['purchasing_cost'];
            } elseif ($code['type'] === 'labor') {
                $estimateSheet->actual_labor_cost = $code['purchasing_cost'];
            }
    
            // Save the updated EstimateSheet
            $estimateSheet->save();
        }
    
        $this->response_data["status"] = true;
        $this->response_data["data"] = "Purchasing costs updated successfully.";
        return $this->sendJsonResponse();
    }


    



    function materialTotal(Estimate $estimate){
        $actualMaterialCost = EstimateSheet::where("material_cost", "!=", 0)
            ->where("estimate_id", $estimate->id)
            ->sum('actual_material_cost');

        $totalMaterialCost = EstimateSheet::where("material_cost", "!=", 0)
            ->where("estimate_id", $estimate->id)
            ->sum(\DB::raw('(quantity * material_cost)'));

        $this->response_data["data"] = [
            "actualMaterialCost" => round($actualMaterialCost,2),
            "totalMaterialCost" => round($totalMaterialCost,2),
                "difference" => round(($totalMaterialCost - $actualMaterialCost),2)
        ];
        $this->response_data["status"] = true;

        return $this->sendJsonResponse();

    }

    public function materialListSupplier(Estimate $estimate,EstimateService $estimateService){
        $data = $estimateService->materialList($estimate);
        $supplierId = ($data->values()->map(function ($core){
            return $core['supplier_id'];
        }));
        $this->response_data["data"]  = Supplier::whereIn("id",$supplierId)->get();
        $this->response_data["status"] = true;
        return $this->sendJsonResponse();
    }


    public function emailMaterialListDoc(Request $request,Estimate $estimate){
        $validator = Validator::make($request->all(), [
            'is_customer' => 'required|boolean',
            'supplier' => 'required|array',
        ]);
        $isSent = false;
//        $email = [];
        if ($validator->fails()){
            foreach ($validator->errors()->all() as $msg) {
                if (strlen(trim($msg)) > 1){
                    $this->response_data["message"] = $msg;
                    return $this->sendJsonResponse();
                }
            }
        }
        try{
            $downloadEmailService = new DownloadEmailService();
            if($request->is_customer){
                $email = $estimate->customer->email;
                $resp = $downloadEmailService->downloadMaterialList($estimate,auth()->user());
                Mail::to($email)
                    ->send(new EstimateMail($estimate,$resp['filepath']));
                $isSent = true;

            }
            if(count($request->supplier) > 0){

                if(Supplier::whereIn("id",$request->supplier)->count() == 0){
                    $this->response_data["no_supplier_found"] = true;
                }else {
                    Supplier::whereIn("id",$request->supplier)->get()->map(function($supplier) use($downloadEmailService,$estimate,$isSent){
                        $resp = $downloadEmailService->materialListSupplier($estimate,auth()->user(),$supplier);
                        Mail::to($supplier->email)
                            ->send(new EstimateMail($estimate,$resp['filepath']));
                        $isSent = true;
                    });
                }



            }
            $this->response_data["status"] = true;
            $this->response_data["data"] = "Email will send to the selected customer/supplier";

            return $this->sendJsonResponse();
        }catch (\Exception $exp){
            $this->response_data["data"] = $exp->getMessage();
            return $this->sendJsonResponse();
        }

    }

}