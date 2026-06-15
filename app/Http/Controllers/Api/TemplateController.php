<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Template;
use App\Models\TemplateCode;
use App\Models\TemplateUserSync;
use App\Rules\TemplateCodeRule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Models\EstimateSheet;
use App\Models\Code;
use App\Models\Supplier;
use App\Models\ProductGroup;
use Illuminate\Support\Facades\DB;

class TemplateController extends ApiBaseController
{
    

    // public function index(Request $request):JsonResponse
    // {
       
    //     $templates = Template::where("company_id", "=", $request->user()->company_id)->orderBy("created_at", "asc")->get();
        
        
    //     $listOfTemplates = [];
    //     foreach ($templates as $template){
    //         $templateCodes = TemplateCode::with('SingleCode')->whereHas("SingleCode", function($query){ $query->where("status", 1);})->where("template_id", "=", $template->id)->where("user_id", "=", $template->user_id)->get();
    //         $tcList = [];
    //         foreach ($templateCodes as $tc){
    //             $tcList[] = [
    //                 "id" => $tc->SingleCode->id,
    //                 "code_name" => $tc->SingleCode->code_name,
    //                 "code_date" => $tc->SingleCode->code_date,
    //                 "product_group_id" => $tc->SingleCode->product_group_id,
    //                 "sort_order" => $tc->SingleCode->sort_order,
    //                 "product_name" => $tc->SingleCode->product_name,
    //                 "sku_no" => $tc->SingleCode->sku_no,
    //                 "model_no" => $tc->SingleCode->model_no,
    //                 "labor_cost" => $tc->SingleCode->labor_cost,
    //                 "unit_of_measure" => $tc->SingleCode->unit_of_measure,
    //                 "material_cost" => $tc->SingleCode->material_cost,
    //                 "misc_cost" => $tc->SingleCode->misc_cost,
    //                 "description" => $tc->SingleCode->description,
    //                 "image" => $tc->SingleCode->full_image,
    //             ];
    //         }
    //         if (!empty($tcList)){
    //             $listOfTemplates[] = [
    //                 "id" => $template->id,
    //                 "template_name" => $template->template_name,
    //                 "codes" => $tcList
    //             ];
    //         }
    //     }
    //     $this->response_data["status"] = true;
    //     $this->response_data["data"] = ["template" => $listOfTemplates];
    //     return $this->sendJsonResponse();
    // }

 public function index(Request $request): JsonResponse
        {
            // Retrieve templates associated with the user's company
            $templates = Template::where("company_id", "=", $request->user()->company_id)
                 ->orderBy('template_order', 'asc') // Order by group_order in ascending order
                ->get();
            
            $listOfTemplates = [];
            $hasActiveSubscription = $request->user()->hasSubscription('template'); // Check for active subscription
            
            // Retrieve the current estimate ID from the request
            $currentEstimateId = $request->input('estimate_id'); // Assuming you're passing the current estimate ID
            
            // Retrieve all the code_id and template_id pairs from the estimate sheets for the given estimate ID
            $addedCodes = EstimateSheet::where("estimate_id", $currentEstimateId)
                ->where("user_id", $request->user()->id)
                 ->get(['code_id', 'template_id']) // Get both code_id and template_id
                ->toArray();
            
            foreach ($templates as $template) {
                // Retrieve all template codes for the current template
                $templateCodes = TemplateCode::with('SingleCode')
                    ->where("template_id", "=", $template->id)
                    ->get();
        
                $tcList = [];
                $alreadyAdded = false; // Initially assume that no codes are added for this template
                
                // dd($addedCodes);
        
                foreach ($templateCodes as $tc) {
                    $tcList[] = [
                        "id" => $tc->SingleCode->id,
                        "code_name" => $tc->SingleCode->code_name,
                        "code_date" => $tc->SingleCode->code_date,
                        "product_group_id" => $tc->SingleCode->product_group_id,
                        "sort_order" => $tc->SingleCode->sort_order,
                        "product_name" => $tc->SingleCode->product_name,
                        "sku_no" => $tc->SingleCode->sku_no,
                        "model_no" => $tc->SingleCode->model_no,
                        "labor_cost" => $tc->SingleCode->labor_cost,
                        "unit_of_measure" => $tc->SingleCode->unit_of_measure,
                        "material_cost" => $tc->SingleCode->material_cost,
                        "misc_cost" => $tc->SingleCode->misc_cost,
                        "description" => $tc->SingleCode->description,
                        "image" => $tc->SingleCode->full_image,
                    ];
        
                    // Check if this specific code and template pair is in the estimate sheet
                    foreach ($addedCodes as $addedCode) {
                        if ($addedCode['code_id'] == $tc->SingleCode->id && $addedCode['template_id'] == $template->id) {
                            $alreadyAdded = true; // If at least one code is added for this template, set to true
                            break; // No need to check further, we found at least one match
                        }
                    }
                }
        
                // Add all templates to the list
                $listOfTemplates[] = [
                    "id" => $template->id,
                    "template_name" => $template->template_name,
                    "codes" => $tcList,
                    "already_added" => $alreadyAdded, // If at least one code is added, this will be true
                    "has_active_subscription" => $hasActiveSubscription // Add subscription flag here
                ];
            }
        
            $this->response_data["status"] = true;
            $this->response_data["data"] = [
                "template" => $listOfTemplates
            ];
            
            return $this->sendJsonResponse();
        }

    
    // public function publicTemplates(Request $request): JsonResponse
    // {
    //         $masterUserId = 346;
        
    //         // Fetch all templates belonging to the master user
    //         $templates = Template::where("user_id", $masterUserId)
    //             ->where("status", 1)
    //             ->orderBy('template_order', 'asc')
    //             ->get();
                
           
    //         $listOfTemplates = [];
    //         $hasActiveSubscription = $request->user()->hasSubscription('template');
        
    //         // Already synced template IDs
    //         $syncedTemplateIds = TemplateUserSync::where('user_id', $request->user()->id)
    //             ->pluck('template_id')
    //             ->toArray();
        
    //         foreach ($templates as $template) {
    //             $templateCodes = TemplateCode::with(['SingleCode' => function ($query) {
    //                     // only fetch non-deleted codes
    //                     $query->whereNull('deleted_at');
    //                 }])
    //                 ->where("template_id", $template->id)
    //                 ->get()
    //                 ->filter(function ($tc) {
    //                     // exclude if related code is deleted or missing
    //                     return $tc->SingleCode !== null;
    //                 });
        
    //             $tcList = [];
        
    //             foreach ($templateCodes as $tc) {
    //                 $code = $tc->SingleCode;
    //                 $tcList[] = [
    //                     "id" => $code->id,
    //                     "code_name" => $code->code_name,
    //                     "code_date" => $code->code_date,
    //                     "product_group_id" => $code->product_group_id,
    //                     "sort_order" => $code->sort_order,
    //                     "product_name" => $code->product_name,
    //                     "sku_no" => $code->sku_no,
    //                     "model_no" => $code->model_no,
    //                     "labor_cost" => $code->labor_cost,
    //                     "unit_of_measure" => $code->unit_of_measure,
    //                     "material_cost" => $code->material_cost,
    //                     "misc_cost" => $code->misc_cost,
    //                     "description" => $code->description,
    //                     "image" => $code->full_image,
    //                 ];
    //             }
        
    //             $listOfTemplates[] = [
    //                 "id" => $template->id,
    //                 "template_name" => $template->template_name,
    //                 "codes" => $tcList,
    //                 "already_added" => in_array($template->id, $syncedTemplateIds),
    //                 "codes_count" => count($tcList), // now excludes deleted codes
    //                 "has_active_subscription" => $hasActiveSubscription,
    //             ];
    //         }
        
    //         $this->response_data["status"] = true;
    //         $this->response_data["data"] = [
    //             "template" => $listOfTemplates
    //         ];
        
    //         return $this->sendJsonResponse();
    //     }
    
    public function publicTemplates(Request $request): JsonResponse
{
    $masterUserId = 346;

    // Fetch all templates belonging to the master user
    $templates = Template::where("user_id", $masterUserId)
        ->where("status", 1)
        ->orderBy('template_order', 'asc')
        ->get();

    $hasActiveSubscription = $request->user()->hasSubscription('template');

    // Already synced template IDs
    $syncedTemplateIds = TemplateUserSync::where('user_id', $request->user()->id)
        ->pluck('template_id')
        ->toArray();

    // Contractor groups
    $contractorGroups = [
        "General Contractor" => [
            "Room additions", "ADU", "ADU 2 nd story", "ADU garage conversion", "Custom House", 
            "Bathroom Remodel", "Kitchen Remodel"
        ],
        "Electrical Contractor" => [
            "Service Panle 100", "Service panel 200", "Rough Electrical", "Finish Electrical"
        ],
        "Plumbing Contractor" => [
            "Water Heater 50", "Water Heater 40", "Rough Plumbing", "Finish Plumbing"
        ],
        "Stucco Contractor" => ["Stucco"],
        "Foundation Contractor" => ["Slab foundation", "Raised foundation"],
        "Framing Contractor" => ["Framing", "Siding"],
        "Roofing Contractor" => ["Roofing"],
        "Drywall Contractor" => ["Drywall"],
        "Demolition Contractor" => ["Demolition"],
        "Flooring Contractor" => ["Laminate floor", "Carpeting"],
        "Finish Carpentry" => ["Doors", "Base molding", "Casing molding", "Crown molding"],
        "Painting Contractor" => ["Interior Painting", "Exterior Painting"],
    ];

    $groupedTemplates = [];
    $addedTemplateIds = []; // Track which templates have been added

    foreach ($contractorGroups as $heading => $subTemplates) {
        $templateList = [];

        foreach ($templates as $template) {
            if (in_array(trim($template->template_name), $subTemplates)) {

                $templateCodes = TemplateCode::with(['SingleCode' => function ($query) {
                        $query->whereNull('deleted_at');
                    }])
                    ->where("template_id", $template->id)
                    ->get()
                    ->filter(fn($tc) => $tc->SingleCode !== null);

                $tcList = [];
                foreach ($templateCodes as $tc) {
                    $code = $tc->SingleCode;
                    $tcList[] = [
                        "id" => $code->id,
                        "code_name" => $code->code_name,
                        "code_date" => $code->code_date,
                        "product_group_id" => $code->product_group_id,
                        "sort_order" => $code->sort_order,
                        "product_name" => $code->product_name,
                        "sku_no" => $code->sku_no,
                        "model_no" => $code->model_no,
                        "labor_cost" => $code->labor_cost,
                        "unit_of_measure" => $code->unit_of_measure,
                        "material_cost" => $code->material_cost,
                        "misc_cost" => $code->misc_cost,
                        "description" => $code->description,
                        "image" => $code->full_image,
                    ];
                }

                $templateList[] = [
                    "id" => $template->id,
                    "template_name" => $template->template_name,
                    "codes" => $tcList,
                    "already_added" => in_array($template->id, $syncedTemplateIds),
                    "codes_count" => count($tcList),
                    "has_active_subscription" => $hasActiveSubscription,
                ];

                $addedTemplateIds[] = $template->id; // mark as added
            }
        }

        if (count($templateList) > 0) {
            $groupedTemplates[] = [
                "template_heading" => $heading,
                "templates" => $templateList,
            ];
        }
    }

    // Handle Miscellaneous templates
    $miscTemplates = [];
    foreach ($templates as $template) {
        if (!in_array($template->id, $addedTemplateIds)) {

            $templateCodes = TemplateCode::with(['SingleCode' => function ($query) {
                    $query->whereNull('deleted_at');
                }])
                ->where("template_id", $template->id)
                ->get()
                ->filter(fn($tc) => $tc->SingleCode !== null);

            $tcList = [];
            foreach ($templateCodes as $tc) {
                $code = $tc->SingleCode;
                $tcList[] = [
                    "id" => $code->id,
                    "code_name" => $code->code_name,
                    "code_date" => $code->code_date,
                    "product_group_id" => $code->product_group_id,
                    "sort_order" => $code->sort_order,
                    "product_name" => $code->product_name,
                    "sku_no" => $code->sku_no,
                    "model_no" => $code->model_no,
                    "labor_cost" => $code->labor_cost,
                    "unit_of_measure" => $code->unit_of_measure,
                    "material_cost" => $code->material_cost,
                    "misc_cost" => $code->misc_cost,
                    "description" => $code->description,
                    "image" => $code->full_image,
                ];
            }

            $miscTemplates[] = [
                "id" => $template->id,
                "template_name" => $template->template_name,
                "codes" => $tcList,
                "already_added" => in_array($template->id, $syncedTemplateIds),
                "codes_count" => count($tcList),
                "has_active_subscription" => $hasActiveSubscription,
            ];
        }
    }

    if (count($miscTemplates) > 0) {
        $groupedTemplates[] = [
            "template_heading" => "Miscellaneous",
            "templates" => $miscTemplates,
        ];
    }

    $this->response_data["status"] = true;
    $this->response_data["data"] = [
        "grouped_templates" => $groupedTemplates
    ];

    return $this->sendJsonResponse();
}


    
    //  public function syncTemplate(Request $request, $templateId): JsonResponse
    // {
    //     $user = $request->user();
    //     $companyId = $user->company_id;
    
    //     // Load template & related codes (excluding deleted ones)
    //     $template = Template::with([
    //         'TemplateCodes.SingleCode' => function ($query) {
    //             $query->whereNull('deleted_at')
    //                   ->with(['ProductGroup', 'Supplier']); // 👈 Load related product group & supplier
    //         }
    //     ])->find($templateId);
    
    //     if (!$template) {
    //         return response()->json([
    //             "status" => false,
    //             "message" => "Template not found."
    //         ]);
    //     }
    
    //     // Check if already synced
    //     $alreadySynced = TemplateUserSync::where('user_id', $user->id)
    //         ->where('template_id', $templateId)
    //         ->exists();
    
    //     if ($alreadySynced) {
    //         return response()->json([
    //             "status" => false,
    //             "message" => "Template already synced."
    //         ]);
    //     }
    
    //     DB::beginTransaction();
    
    //     try {
    //         // Record sync event
    //         TemplateUserSync::create([
    //             'user_id' => $user->id,
    //             'template_id' => $templateId,
    //             'status' => 'synced',
    //             'synced_at' => now(),
    //         ]);
    
    //         // Clone Template
    //         $newTemplate = $template->replicate();
    //         $newTemplate->user_id = $user->id;
    //         $newTemplate->company_id = $companyId;
    //         $newTemplate->save();
    
    //         $clonedCodes = [];
    //         $clonedProductGroups = [];
    //         $clonedSuppliers = [];
    
    //         // Clone active codes only
    //         foreach ($template->TemplateCodes as $tc) {
    //             $code = $tc->SingleCode;
    //             if (!$code || $code->deleted_at !== null) continue;
    
    //             // ✅ Clone Product Group (if not already cloned)
    //             $productGroup = $code->ProductGroup ?? null;
    //             $newProductGroupId = null;
    
    //             if ($productGroup && !isset($clonedProductGroups[$productGroup->id])) {
    //                 $newPG = $productGroup->replicate();
    //                 $newPG->user_id = $user->id;
    //                 $newPG->company_id = $companyId;
    //                 $newPG->created_at = now();
    //                 $newPG->updated_at = now();
    //                 $newPG->save();
    
    //                 $clonedProductGroups[$productGroup->id] = $newPG->id;
    //                 $newProductGroupId = $newPG->id;
    //             } elseif ($productGroup) {
    //                 $newProductGroupId = $clonedProductGroups[$productGroup->id];
    //             }
    
    //             // ✅ Clone Supplier (if exists)
    //             $supplier = $code->Supplier ?? null;
    //             $newSupplierId = null;
    
    //             if ($supplier && !isset($clonedSuppliers[$supplier->id])) {
    //                 $newSupplier = $supplier->replicate();
    //                 $newSupplier->user_id = $user->id;
                    
    //                 $newSupplier->created_at = now();
    //                 $newSupplier->updated_at = now();
    //                 $newSupplier->save();
    
    //                 $clonedSuppliers[$supplier->id] = $newSupplier->id;
    //                 $newSupplierId = $newSupplier->id;
    //             } elseif ($supplier) {
    //                 $newSupplierId = $clonedSuppliers[$supplier->id];
    //             }
    
    //             // ✅ Clone Code
    //             $newCode = $code->replicate();
    //             $newCode->user_id = $user->id;
    //             $newCode->company_id = $companyId;
    //             $newCode->product_group_id = $newProductGroupId;
    //             $newCode->supplier_id = $newSupplierId;
    //             $newCode->save();
    
    //             // ✅ Clone Template-Code Link
    //             $newTemplateCode = $tc->replicate();
    //             $newTemplateCode->template_id = $newTemplate->id;
    //             $newTemplateCode->code_id = $newCode->id;
    //             $newTemplateCode->user_id = $user->id;
    //             $newTemplateCode->save();
    
    //             // For response
    //             $clonedCodes[] = [
    //                 "id" => $newCode->id,
    //                 "code_name" => $newCode->code_name,
    //                 "product_name" => $newCode->product_name,
    //                 "sku_no" => $newCode->sku_no,
    //                 "model_no" => $newCode->model_no,
    //                 "labor_cost" => $newCode->labor_cost,
    //                 "material_cost" => $newCode->material_cost,
    //                 "misc_cost" => $newCode->misc_cost,
    //                 "description" => $newCode->description,
    //                 "image" => $newCode->full_image,
    //             ];
    //         }
    
    //         DB::commit();
    
    //         return response()->json([
    //             "status" => true,
    //             "data" => [
    //                 "template" => [
    //                     "id" => $newTemplate->id,
    //                     "template_name" => $newTemplate->template_name,
    //                     "codes" => $clonedCodes,
    //                     "codes_count" => count($clonedCodes),
    //                     "product_groups_cloned" => count($clonedProductGroups),
    //                     "suppliers_cloned" => count($clonedSuppliers),
    //                     "already_added" => true
    //                 ]
    //             ]
    //         ]);
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return response()->json([
    //             "status" => false,
    //             "message" => "Error syncing template: " . $e->getMessage()
    //         ]);
    //     }
    // }




    public function syncTemplate(Request $request, $templateId): JsonResponse
    {
        $user = $request->user();
        $companyId = $user->company_id;
    
        $template = Template::with([
            'TemplateCodes.SingleCode' => function ($query) {
                $query->whereNull('deleted_at')
                    ->with(['ProductGroup', 'Supplier']);
            }
        ])->find($templateId);
    
        if (!$template) {
            return response()->json(["status" => false, "message" => "Template not found."]);
        }
    
        //  Check if user already synced this template
        if (TemplateUserSync::where('user_id', $user->id)->where('template_id', $templateId)->exists()) {
            return response()->json(["status" => false, "message" => "Template already synced."]);
        }
    
        DB::beginTransaction();
        try {
            TemplateUserSync::create([
                'user_id' => $user->id,
                'template_id' => $templateId,
                'status' => 'synced',
                'synced_at' => now(),
            ]);
    
            // check if user already has a cloned template with same name
            $existingTemplate = Template::where('user_id', $user->id)
                ->where('template_name', $template->template_name)
                ->first();
    
            if ($existingTemplate) {
                $newTemplate = $existingTemplate;
            } else {
                $newTemplate = $template->replicate();
                $newTemplate->user_id = $user->id;
                $newTemplate->company_id = $companyId;
                $newTemplate->save();
            }
    
            $clonedCodes = [];
            $clonedProductGroups = [];
            $clonedSuppliers = [];
    
            foreach ($template->TemplateCodes as $tc) {
                $code = $tc->SingleCode;
                if (!$code || $code->deleted_at) continue;
    
                // Product Group
                $productGroup = $code->ProductGroup;
                $newProductGroupId = null;
                if ($productGroup) {
                    $existingPG = ProductGroup::where('user_id', $user->id)
                        ->where('group_name', $productGroup->group_name)
                        ->first();
    
                    if ($existingPG) {
                        $newProductGroupId = $existingPG->id;
                    } else {
                        $newPG = $productGroup->replicate();
                        $newPG->user_id = $user->id;
                        $newPG->company_id = $companyId;
                        $newPG->save();
                        $newProductGroupId = $newPG->id;
                    }
                }
    
                //  Supplier
                $supplier = $code->Supplier;
                $newSupplierId = null;
                if ($supplier) {
                    $existingSupplier = Supplier::where('user_id', $user->id)
                        ->where('full_name', $supplier->full_name)
                        ->first();
    
                    if ($existingSupplier) {
                        $newSupplierId = $existingSupplier->id;
                    } else {
                        $newSupplier = $supplier->replicate();
                        $newSupplier->user_id = $user->id;
                        $newSupplier->save();
                        $newSupplierId = $newSupplier->id;
                    }
                }
    
                //  Code (avoid duplicates by product_name or sku)
                $existingCode = Code::where('user_id', $user->id)
                    ->where(function ($q) use ($code) {
                        $q->where('code_name', $code->code_name);
                          
                    })
                    ->first();
    
                if ($existingCode) {
                    $newCode = $existingCode;
                } else {
                    $newCode = $code->replicate();
                    $newCode->user_id = $user->id;
                    $newCode->company_id = $companyId;
                    $newCode->product_group_id = $newProductGroupId;
                    $newCode->supplier_id = $newSupplierId;
                    $newCode->save();
                }
    
                //  TemplateCode (link only if not linked already)
                $exists = \App\Models\TemplateCode::where('template_id', $newTemplate->id)
                    ->where('code_id', $newCode->id)
                    ->where('user_id', $user->id)
                    ->exists();
    
                if (!$exists) {
                    $newTemplateCode = $tc->replicate();
                    $newTemplateCode->template_id = $newTemplate->id;
                    $newTemplateCode->code_id = $newCode->id;
                    $newTemplateCode->user_id = $user->id;
                    $newTemplateCode->save();
                }
    
                $clonedCodes[] = [
                    "id" => $newCode->id,
                    "code_name" => $newCode->code_name,
                    "sku_no" => $newCode->sku_no,
                    "model_no" => $newCode->model_no,
                    "labor_cost" => $newCode->labor_cost,
                    "material_cost" => $newCode->material_cost,
                    "misc_cost" => $newCode->misc_cost,
                    "description" => $newCode->description,
                    "image" => $newCode->full_image,
                ];
            }
    
            DB::commit();
    
            return response()->json([
                "status" => true,
                "message" => "Template synced successfully.",
                "data" => [
                    "template" => [
                        "id" => $newTemplate->id,
                        "template_name" => $newTemplate->template_name,
                        "codes" => $clonedCodes,
                        "codes_count" => count($clonedCodes),
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(["status" => false, "message" => "Error: " . $e->getMessage()]);
        }
    }



    public function create(Request $request):JsonResponse
    {
        $validator = Validator::make($request->all(), [
        'codes' => [
            'required',
            'array',
            'min:1', // Ensure at least one code is selected
            new TemplateCodeRule() // Custom validation rule
        ],
        'codes.*' => 'integer', // Each code must be an integer
        'template_name' => [
            'required',
            'string',
            Rule::unique("templates")->where(function($query) use($request) {
                return $query->where("template_name", $request->input("template_name"))
                             ->where("company_id", "=", $request->user()->company_id);
            })
        ]
    ]);
    
    if ($validator->fails()) {
        foreach ($validator->errors()->all() as $msg) {
            if (strlen(trim($msg)) > 1) {
                if ($msg === "no_subscription") {
                    $this->response_data["message"] = 'You can not add more than 3 Codes';
                    $this->response_data["no_subscription"] = true;
                    return $this->sendJsonResponse();
                }
                // Custom error for the "codes" field
                if (strpos($msg, 'codes') !== false && empty($request->input('codes'))) {
                    $this->response_data["message"] = 'Please select at least one code to proceed.';
                    return $this->sendJsonResponse();
                }
                $this->response_data["message"] = $msg;
                return $this->sendJsonResponse();
            }
        }
    }
        
        
        // Calculate the new group order
        $maxOrder = Template::where('company_id', $request->user()->company_id)
                                ->max('template_order');
        $newOrder = $maxOrder + 1;
    
    
    
        $newTemplate = new Template();
        $newTemplate->template_name = $request->input("template_name");
        $newTemplate->user_id = $request->user()->id;
        $newTemplate->company_id = $request->user()->company_id;
        $newTemplate->template_order = $newOrder;
        $newTemplate->save();
        if ($newTemplate->id){
            foreach ($request->input("codes") as $code){
                TemplateCode::create([
                    "code_id" => $code,
                    "template_id" => $newTemplate->id,
                    "user_id" => $request->user()->id
                ]);
            }
        }
        $this->response_data["status"] = true;
        $this->response_data["data"] = ["template"=>$newTemplate];
        $this->response_data["message"] = $newTemplate->template_name." saved successfully.";
        return $this->sendJsonResponse();
    }

    public function edit(Request $request, $id):JsonResponse
    {
        $data = Template::where("id", "=", $id)->where("company_id", "=", $request->user()->company_id)->first();
        $template = [];
        if (!empty($data)){
            $templateCodes = TemplateCode::with('SingleCode')->whereHas("SingleCode", function($query){ $query->where("status", 1);})->where("template_id", "=", $data->id)->where("user_id", "=", $data->user_id)->get();
            $tcList = [];
            foreach ($templateCodes as $tc){
                $tcList[] = [
                    "id" => $tc->SingleCode->id,
                    "code_name" => $tc->SingleCode->code_name,
                    "code_date" => $tc->SingleCode->code_date,
                    "product_group_id" => $tc->SingleCode->product_group_id,
                    "sort_order" => $tc->SingleCode->sort_order,
                    "product_name" => $tc->SingleCode->product_name,
                    "sku_no" => $tc->SingleCode->sku_no,
                    "model_no" => $tc->SingleCode->model_no,
                    "labor_cost" => $tc->SingleCode->labor_cost,
                    "unit_of_measure" => $tc->SingleCode->unit_of_measure,
                    "material_cost" => $tc->SingleCode->material_cost,
                    "misc_cost" => $tc->SingleCode->misc_cost,
                    "description" => $tc->SingleCode->description,
                    "image" => $tc->SingleCode->full_image,
                ];
            }
            $template = [
                "id" => $data->id,
                "template_name" => $data->template_name,
                "codes" => $tcList
            ];
            $this->response_data["status"] = true;
            $this->response_data["message"] = "";
            $this->response_data["data"] = ["template"=>$template];
        }
        return $this->sendJsonResponse();
    }

    public function update(Request $request, $id):JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'template_name' => [
                'required',
                'string',
                Rule::unique("templates")->where(function ($query) use ($request) {
                    return $query->where("template_name", $request->input("template_name"))
                                 ->where("company_id", "=", $request->user()->company_id)
                                 ->where("id", "!=", $request->id); // Exclude the current template ID for updates
                })
            ],
            'codes' => [
                'required',
                'array',
                'min:1', // Ensure at least one code is selected
                new TemplateCodeRule($request->id) // Pass $id for custom rule logic
            ],
            'codes.*' => 'integer', // Validate each code as an integer
        ]);
        
        // Handle validation errors
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $msg) {
                if (strlen(trim($msg)) > 1) {
                    // Custom error message for missing codes
                    if (strpos($msg, 'codes') !== false && empty($request->input('codes'))) {
                        $this->response_data["message"] = 'Please select at least one code to proceed.';
                        return $this->sendJsonResponse();
                    }
                    // Default message response
                    $this->response_data["message"] = $msg;
                    return $this->sendJsonResponse();
                }
            }
        }
        $oldTemplate = Template::where("id", "=", $id)->where("user_id", "=", $request->user()->id)->first();
        $this->response_data["message"] = "Data not found.";
        if (!empty($oldTemplate)){
            $oldTemplate->template_name = $request->input("template_name");
            $oldTemplate->update();
            $notInUseCodes = TemplateCode::where("template_id", "=", $oldTemplate->id)->whereNotIn("code_id", $request->input("codes"))->where("user_id", "=", $request->user()->id)->delete();
            foreach ($request->input("codes") as $cId){
                TemplateCode::firstOrCreate(["template_id"=>$oldTemplate->id, "code_id"=>$cId, "user_id"=>$request->user()->id]);
            }

            $this->response_data["data"] = ["template"=>$oldTemplate];
            $this->response_data["status"] = true;
            $this->response_data["message"] = $oldTemplate->template_name." update successfully.";
        }
        return $this->sendJsonResponse();
    }

    public function delete(Request $request, $id):JsonResponse
    {
        $template = Template::where("id", "=", $id)->where("user_id", "=", $request->user()->id)->first();
        $this->response_data["message"] = "Data not found.";
        if (!empty($template)){

            TemplateCode::where("template_id", "=", $id)->delete();
            $this->response_data["message"] = $template->template_name." delete successfully.";
            $template->delete();
            $this->response_data["status"] = true;

        }
        return $this->sendJsonResponse();
    }

    public function checkTemplateCreation(){
        $this->response_data["status"] = $this->isAllowTemplateCreation();
        if(!$this->response_data["status"])
            $this->response_data["message"] = "Upgrade membership plan";

        return $this->sendJsonResponse();
    }

    public function isAllowTemplateCreation(){
        $isAllow = true;
        if(!auth()->user()->hasSubscription('template') && auth()->user()->templates()->count() >= 3){
            $isAllow = false;
        }
        return $isAllow;
    }
    
    
    public function reorder(Request $request): JsonResponse
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|exists:templates,id',
            'template_order' => 'required|integer|min:1',
        ]);
    
        // Handle validation errors
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $msg) {
                if (strlen(trim($msg)) > 1) {
                    $this->response_data["message"] = $msg;
                    return $this->sendJsonResponse();
                }
            }
        }
    
        $userCompanyId = $request->user()->company_id;
        $templateId = $request->input('id');
        $newOrder = $request->input('template_order');
    
        // Fetch total count of product groups in the company
        $totalTemplates = Template::where('company_id', $userCompanyId)->count();
    
        // Ensure the new template_order is within the valid range
        if ($newOrder > $totalTemplates) {
            $this->response_data["status"] = false;
            $this->response_data["message"] = "The group order must be within the range of available groups.";
            return $this->sendJsonResponse();
        }
    
        // Fetch the product group to reorder and its old order
        $Template = Template::where('id', $templateId)
                                    ->where('company_id', $userCompanyId)
                                    ->first();
    
        if (empty($Template)) {
            $this->response_data["status"] = false;
            $this->response_data["message"] = "Data not found.";
            return $this->sendJsonResponse();
        }
    
        $oldOrder = $Template->template_order;
    
        DB::beginTransaction(); // Start the transaction
    
        try {
            if ($newOrder > $oldOrder) {
                // Move down: decrement the template_order of all items between old and new position
                Template::where('company_id', $userCompanyId)
                            ->whereBetween('template_order', [$oldOrder + 1, $newOrder])
                            ->decrement('template_order');
            } elseif ($newOrder < $oldOrder) {
                // Move up: increment the template_order of all items between new and old position
                Template::where('company_id', $userCompanyId)
                            ->whereBetween('template_order', [$newOrder, $oldOrder - 1])
                            ->increment('template_order');
            }
    
            // Update the dragged item's template_order to the new position
            $Template->template_order = $newOrder;
            $Template->save();
    
            // Ensure that template_order values remain sequential (optional but recommended)
            $this->reindexTemplatesOrders($userCompanyId);
    
            $this->response_data["status"] = true;
            $this->response_data["message"] = $Template->template_name . " reordered successfully.";
            $this->response_data["data"] = ["template" => $Template];
    
            DB::commit(); // Commit the transaction
    
        } catch (\Exception $e) {
            // Rollback the transaction if something goes wrong
            DB::rollBack();
    
            $this->response_data["status"] = false;
            $this->response_data["message"] = "Failed to reorder templates.";
            $this->response_data["data"] = null;
            $this->response_data["error"] = $e->getMessage();
        }
    
        return $this->sendJsonResponse();
    }
    
    
    private function reindexTemplatesOrders(int $companyId)
    {
        $templates = Template::where('company_id', $companyId)
                              ->orderBy('template_order')
                              ->get();
    
        $counter = 1;
        foreach ($templates as $template) {
            $template->template_order = $counter++;
            $template->save();
        }
    }
    
    
}
