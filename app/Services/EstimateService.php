<?php

namespace App\Services;

use App\Models\Estimate;
use App\Models\EstimateSheet;
use App\Models\EstimateType;
use App\Models\Setting;
use App\Models\SettingType;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class EstimateService
{

    // public function addCodesOrTemplateCodes(Estimate $estimate,$codes,$template = null){
    //     DB::beginTransaction();
    //     try {
    //         foreach ($codes as $index => $code){
    //             $quantity = 0;
    //             $sheet = $estimate->sheet()->where("code_id",$code->id)->first();
    //             if($sheet){
    //                 continue; // already exists
    //             }
    //             $estimate->sheet()->create([
    //                 "estimate_id" =>  $estimate->id,
    //                 "user_id" => auth()->user()->id,
    //                 "code_id" => $code->id,
    //                 "quantity" => $quantity,
    //                 "unit" => $code->unit_of_measure,
    //                 "labor_cost" => $code->labor_cost,
    //                 'template_id' => $template->id ?? null,
    //                 "material_cost" => $code->material_cost,
    //                 "misc_cost" => $code->misc_cost,
    //                 "row_total" => 0
    //             ]);
    //         }
    //         DB::commit();
    //     } catch (\Exception $e) {
    //         DB::rollback();
    //         throw new \Exception($e->getMessage());
    //     }
    // }


 public function addCodesOrTemplateCodes(Estimate $estimate, $codes, $templateId = null) {
    DB::beginTransaction();
    try {
        
        
        foreach ($codes as $code) {
            $quantity = 0; // Set the quantity to 0 initially
            
            // Check if the code is already associated with the estimate
            $existingSheet = $estimate->sheet()->where("code_id", $code->id)->first();
            // if ($existingSheet) {
            //     continue; // Skip if the code is already added
            // }

            // Determine the template ID based on the type of $templateId
            $templateIdToUse = null;

            if ($templateId instanceof Template) {
                // If $templateId is a Template model instance, use its ID
                $templateIdToUse = $templateId->id;
            } elseif (is_array($templateId)) {
                // If $templateId is an array, handle accordingly (assuming you want the first id)
                $templateIdToUse = $templateId[0] ?? null; // Use the first ID or null if empty
            } elseif (is_numeric($templateId)) {
                // If $templateId is numeric, use it directly
                $templateIdToUse = $templateId;
            }
            
           

            // Create a new estimate sheet entry for the code
            $estimate->sheet()->create([
                "estimate_id" => $estimate->id,
                "user_id" => auth()->user()->id,
                "code_id" => $code->id,
                "quantity" => $quantity,
                "unit" => $code->unit_of_measure,
                "labor_cost" => $code->labor_cost,
                "template_id" => $templateIdToUse, // Use the determined template ID
                "material_cost" => $code->material_cost,
                "misc_cost" => $code->misc_cost,
                "row_total" => 0 // Set initial row total to 0
            ]);
        }
        DB::commit();
    } catch (\Exception $e) {
        DB::rollback();
        throw new \Exception($e->getMessage()); // Rethrow the exception to handle it in the controller
    }
}



    
    
   function updateQuantity(EstimateSheet $estimateSheet, $quantity)
{
    $code = $estimateSheet->Code;

    $labor_cost_total    = $quantity * $estimateSheet->labor_cost;
    $material_cost_total = $quantity * $estimateSheet->material_cost;
    $misc_cost           = $quantity * $estimateSheet->misc_cost;

    // ✅ Step 1: calculate per-unit adjust cost
    $per_unit_adjust_cost = $code->adjust_num == '1.00'
        ? ($estimateSheet->material_cost + $estimateSheet->misc_cost)
        : ($code->adjusted_cost + $estimateSheet->misc_cost);

    // ✅ Step 2: multiply by quantity
    $material_adjust_cost = $per_unit_adjust_cost * $quantity;

    $updated = $estimateSheet->update([
        "labor_cost"            => $estimateSheet->labor_cost,
        "material_cost"         => $estimateSheet->material_cost,
        "actual_labor_cost"     => $labor_cost_total,
        "actual_material_cost"  => $material_cost_total,
        "misc_cost"             => $code->misc_cost,
        "row_total"             => ($labor_cost_total + $material_cost_total + $misc_cost),
        "unit"                  => $estimateSheet->unit,
        "quantity"              => $quantity,
        "material_adjust_cost"  => $material_adjust_cost, // ✅ new column
    ]);

    return $updated ? true : false;
}


    function saveEstimate(Estimate $estimate){
        $detail = $this->getDetail($estimate,auth()->user());
        return $detail;
    }

     function getDetail(Estimate $estimate, User $user) {
        $detail = [];
        $sheets = $estimate->sheet;
        
    
        // Calculate material costs
        $detail['material'] = $sheets->map(function($sheet) {
            $value = $sheet->total_material_cost != 0 ? round($sheet->total_material_cost, 2) : 0;
            return $value;
        })->filter()->values();
    
    
        
        // Calculate labor costs
        $detail['labor_cost'] = $sheets->map(function($sheet) {
            return $sheet->total_labor_cost != 0 ? round($sheet->total_labor_cost, 2) : 0; // Return 0 instead of null
        })->filter()->values();
    
        // Calculate the subtotals (before tax but with markup) and format them as strings
        $detail['material_sub_total'] = number_format(collect($detail['material'])->sum(), 2, '.', '');  // Subtotal for materials as string
        $detail['labor_sub_total'] = number_format(collect($detail['labor_cost'])->sum(), 2, '.', '');   // Subtotal for labor as string
    
  
        // Ensure values are formatted correctly as strings with two decimal places
        if ($detail['material_sub_total'] === '0') {
            $detail['material_sub_total'] = '0.00';
        }
        if ($detail['labor_sub_total'] === '0') {
            $detail['labor_sub_total'] = '0.00';
        }
    
        // Format pre-cost values with number_format to ensure two decimal places
        $detail['material_pre_cost'] = number_format(floatval($detail['material_sub_total']), 2, '.', '');
        $detail['labor_pre_cost'] = number_format(floatval($detail['labor_sub_total']), 2, '.', '');
    
        // Fetch settings for the estimate
        $settingCollection = Setting::where("company_id", $user->company_id)
            ->where("type", SettingType::SETTING_TYPE_FOR_ESTIMATE)
            ->get();
    
        // Calculate "material markup" and add to material subtotal
        $settingMaterialMarkup = $settingCollection->where('slug', "material_markup")->first();
        $markupValue = round((($settingMaterialMarkup->value ?? 0) / 100) * floatval($detail['material_sub_total']), 2);
        $detail['settings']["material_markup"] = number_format($markupValue, 2, '.', '');
        $detail['material_sub_total'] = number_format(floatval($detail['material_sub_total']) + $markupValue, 2, '.', '');
        
        // Calculate "labor markup" and add to labor subtotal
       $settingLaborMarkup = $settingCollection->first(function ($setting) {
                return in_array($setting->slug, ['labour_markup', 'labor_markup']);
            });
        
        // dd($settingLaborMarkup);
       
        $laborMarkupValue = round(((($settingLaborMarkup->value ?? 0) / 100) * floatval($detail['labor_sub_total'])), 2);

        $detail['settings']["labour_markup"] = number_format($laborMarkupValue, 2, '.', '');
        $detail['labor_sub_total'] = number_format(floatval($detail['labor_sub_total']) + $laborMarkupValue, 2, '.', '');
        
        // Calculate "tax" on material after adding markup
        $settingTax = $settingCollection->where('slug', "tax")->first();
        $taxValue = round(($settingTax->value / 100) * floatval($detail['material_sub_total']), 2);
        $detail['settings']["tax"] = number_format($taxValue, 2, '.', '');
        $detail['material_total'] = number_format(floatval($detail['material_sub_total']) + $taxValue, 2, '.', '');
        
        // Calculate the total (material + labor after markup and tax)
        $detail['labor_total'] = number_format(floatval($detail['labor_sub_total']), 2, '.', '');  // labor total remains same as labor_sub_total since no tax on labor
        $detail['total'] = number_format(floatval($detail['material_total']) + floatval($detail['labor_total']), 2, '.', '');
        
        
        
        $detail['material_sub_total'] = number_format(round($detail['material_sub_total'], 2), 2, '.', ',');
        $detail['labor_sub_total'] = number_format(round($detail['labor_sub_total'], 2), 2, '.', ',');
        
        
        // Initialize grand total
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
            
        $contractPrice = $detail['grand_total'];
        
        // Final formatting for grand total and total
        $detail['grand_total'] = number_format(floatval($detail['grand_total']), 2, '.', ',');
        $detail['total'] = number_format(floatval($detail['total']), 2, '.', ',');
        $detail['material_total'] = number_format(floatval($detail['material_total']), 2, '.', ',');
    
        // Ensure 0 values are formatted as 0.00
        $detail['material_pre_cost'] = number_format(floatval($detail['material_pre_cost']), 2, '.', '');
        $detail['labor_pre_cost'] = number_format(floatval($detail['labor_pre_cost']), 2, '.', '');
        
        
    
        // Format all settings values to ensure they are strings with two decimal places
        foreach ($detail['settings'] as $key => $value) {
            if (is_numeric($value)) {
                $detail['settings'][$key] = number_format(floatval($value), 2, '.', '');
            }
        }
        
        $estimate->contract_price = $contractPrice;
        
        // Save the updated Estimate model
        $estimate->save();
    
        return $detail;
    }




    public function materialList(Estimate $estimate){
        return EstimateSheet::with(["Code.ProductGroup","Code.supplier" ])->where("material_cost","!=", 0)
            ->where("estimate_id",$estimate->id)
            ->get()
            ->groupBy("Code.product_group_id")
            ->map(function($group){
                $totalMatCost = $group->sum(function ($item) {
                    return ($item->material_cost * $item->quantity);
                });
                $actualMatCost = $group->sum('actual_material_cost');
                return [
                    "estimate_sheet_id" => optional($group->first())->id,
                    "product_group_id" => optional($group->first()->Code)->product_group_id,
                    "category" => optional($group->first()->Code->ProductGroup)->group_name,
                    "supplier" => optional($group->first()->Code->supplier)->full_name,
                    "supplier_id" => optional($group->first()->Code->supplier)->id,
                    "codes" => $group->pluck("code_id")->unique()->count(),
                    "allowance" => round($totalMatCost,2),
                    "total" => round($actualMatCost,2),
                    "difference" => round(($totalMatCost - $actualMatCost),2),
                    "description" => optional($group->first()->Code)->description,
                ];

            });
    }
   public function materialListNotGroup(Estimate $estimate, $is_supplier = false, $supplier = null, $type = 'both')
{
    // Start building the query
    $mainQuery = EstimateSheet::with(["Code.ProductGroup", "Code.supplier"])
        ->where("material_cost", "!=", 0)
        ->where("estimate_id", $estimate->id)
        ->whereHas('Code', function ($query) {
            // Exclude records where Code.quantity is 0
            $query->where('quantity', '>', 0);
        });

    // Apply filter based on supplier if applicable
    if ($is_supplier && $supplier) {
        $supplierid = $supplier->id;
        $mainQuery->whereHas("Code", function ($query) use ($supplierid) {
            $query->where("supplier_id", $supplierid);
        });
    }

    // Apply filter based on the 'type'
    if ($type === 'supplier') {
        // Include records where type is 'supplier' or 'both'
        $mainQuery->whereHas('Code', function ($query) {
            $query->where(function ($subQuery) {
                $subQuery->where('type', 'supplier')
                         ->orWhere('type', 'both');
            });
        });
    } elseif ($type === 'customer') {
        // Include records where type is 'customer' or 'both'
        $mainQuery->whereHas('Code', function ($query) {
            $query->where(function ($subQuery) {
                $subQuery->where('type', 'customer')
                         ->orWhere('type', 'both');
            });
        });
    } elseif ($type === 'both') {
        // Get records that match either 'supplier', 'customer', or 'both'
        $mainQuery->whereHas('Code', function ($query) {
            $query->where(function ($subQuery) {
                $subQuery->where('type', 'supplier')
                         ->orWhere('type', 'customer')
                         ->orWhere('type', 'both');
            });
        });
    }

    // Get the final results
    return $mainQuery->get();
}




//   public function getSalesReport(User $user,$from_date,$to_date){
//         $company = $user->company;
        
      
//         $to_date = Carbon::parse($to_date)->endOfDay(); // Set time to 23:59:59 on the to_date

//         $data = $company->estimates()
//             ->whereBetween("created_at", [$from_date, $to_date])
//             ->get();
         
//       $estimateService = new EstimateService();
//       $estimateTypes = EstimateType::all();


//       $detail = $data->map(function ($estimate) use ($user,$estimateService,$estimateTypes) {
//           $data = $estimateService->getDetail($estimate,$user);

//           $type = $estimateTypes->where('id', $estimate->type)->first();
//         //   $estimate->key = $type->initials . $estimate->id;
//           $estimate->grand_total = $data['grand_total'];
//           return $estimate;
//       });
//       return $detail;

//   }


public function getSalesReport(User $user, $from_date, $to_date)
{
    $currentUser = $user; // Get the current user
    $company = $currentUser->company;
    $to_date = Carbon::parse($to_date)->endOfDay(); // Set time to 23:59:59 on the to_date

    // Fetch estimates based on user role
    $data = $company->estimates()
        ->where('user_id', $currentUser->id) // Both admin and child user see their own estimates
        ->whereBetween("created_at", [$from_date, $to_date])
        ->get();


    $estimateService = new EstimateService();
    $estimateTypes = EstimateType::all();

    $detail = $data->map(function ($estimate) use ($currentUser, $estimateService, $estimateTypes) {
        $data = $estimateService->getDetail($estimate, $currentUser);
        $type = $estimateTypes->where('id', $estimate->type)->first();
        $estimate->grand_total = $data['grand_total'];
        return $estimate;
    });
    
    return $detail;
}


   public function getSalesReportCount(User $user,$from_date,$to_date)
   {
       $company = $user->company;
       return $company->estimates()->where("is_saved",1)
           ->where(function ($query) use($from_date,$to_date){
               $query->whereBetween("created_at",[$from_date, $to_date]);
           })->count();
   }

}