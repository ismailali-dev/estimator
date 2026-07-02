<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Membership;
use App\Models\Setting;
use App\Models\SettingType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Models\UserEstimatedAnnualJobCost;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\SettingDocument;
use App\Models\Estimate;
use App\Models\UserInvoiceSetting;
use App\Models\EstimateSignature;
use Illuminate\Support\Facades\Storage;
use App\Helpers\DocumentConverter;
use setasign\Fpdi\Fpdi;
use Illuminate\Support\Facades\Mail;
use App\Mail\SettingDocumentsMail;
use App\Services\EstimateService;
use App\Models\ProfitBudgetEstimateSetting;
use Barryvdh\DomPDF\Facade\Pdf;



class GlobalSettingController extends ApiBaseController
{
   
   
    public function types()
    {
        // Retrieve only the main setting types (those without a parent_id)
        $settingTypes = SettingType::whereNull('parent_id')->get()->map(function ($settingType) {
            $settingType->subscription_status = true; // Set default status
            return $settingType;
        });
    
        // Check if the user has the required subscription
        $hasSubscription = auth()->user()->hasSubscription(Membership::SUBSCRIPTION_GLOBAL_SETTING_SUPPORT);
        
        // If no subscription, set the subscription status for the specific record ID constant
        if (!$hasSubscription) {
            $record = $settingTypes->firstWhere('id', SettingType::SETTING_TYPE_FOR_PROFIT_BUDGET); // Use constant for ID
            if ($record) {
                $record->subscription_status = false; // Update status to false
            }
    
            // Set response message indicating lack of subscription
            $this->response_data["message"] = "No subscription found for global setting support.";
        }
    
        // Set response data and status
        $this->response_data["data"] = $settingTypes;
        $this->response_data["status"] = $hasSubscription; // Set status based on subscription
    
        return $this->sendJsonResponse();
    }


    public function fetchChildCategories($parentId)
    {
        // Retrieve the parent setting type
        $parentSettingType = SettingType::find($parentId);
    
        // If parent setting type doesn't exist, return an error message
        if (!$parentSettingType) {
            $this->response_data["message"] = "Parent category not found.";
            $this->response_data["status"] = false;
            return $this->sendJsonResponse();
        }
    
        // Fetch child categories
        $childSettingTypes = SettingType::where('parent_id', $parentId)->get();
    
        // Check if there are any child categories
        if ($childSettingTypes->isEmpty()) {
            $this->response_data["message"] = "No child categories found for this parent.";
            $this->response_data["status"] = false;
            return $this->sendJsonResponse();
        }
    
        // Retrieve the user's company
        $company = auth()->user()->company;
    
        // Fetch the first estimated annual job cost, or return 0 if not available
        $estimatedAnnualJobCost = optional($company->userEstimatedAnnualJobCosts->first())->estimated_annual_job_cost ?? "0";
    
        // Prepare response data
        $this->response_data = [
            "status" => true,
            "data" => [
                "child_categories" => $childSettingTypes,
                "user_estimated_annual_job_costs" => $estimatedAnnualJobCost
            ]
        ];
    
        return $this->sendJsonResponse();
    }


    
    public function updateAnnualJobCost(Request $request)
    {
        
       
        // Step 0: Validation
        $validator = Validator::make($request->all(), [
            'estimated_annual_job_cost' => 'required|numeric|min:0',
            'setting_type_id' => 'required|exists:setting_types,id',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
            ], 400);
        }
    
        $currentUser = auth()->user();
        if (!$currentUser) {
            return response()->json([
                'status' => false,
                'message' => 'User not authenticated.',
            ], 401);
        }
    
       
    
        if (!$currentUser) {
            return response()->json([
                'status' => false,
                'message' => 'Parent user not found.',
            ], 404);
        }
    
        // Step 2: Check company (parent/admin must have company)
        $company = $currentUser->company;
        if (!$company) {
            return response()->json([
                'status' => false,
                'message' => 'Company not found for the user.',
            ], 404);
        }
    
        // step 3: Permission check -> Profit Budget (id=3)
        if (!$currentUser->hasModuleAccess(10)) {
            return response()->json([
                'status' => false,
                'message' => 'You do not have permission to update annual job cost.',
            ], 403);
        }
    
        // //step 4: If child user, validate that child belongs to same company
        // if (!$currentUser->is_admin && $currentUser->parent_id) {
            
        //     if ($currentUser->company_id !== $company->id) {
        //         return response()->json([
        //             'status' => false,
        //             'message' => 'You are not allowed to update cost for this company.',
        //         ], 403);
        //     }
            
            
        // }
    
        // step 5: Update or create record against parent/admin company
        $jobCost = UserEstimatedAnnualJobCost::updateOrCreate(
            [
                'company_id' => $company->id
            ],
            [
                'estimated_annual_job_cost' => $request->estimated_annual_job_cost,
                'setting_type_id' => $request->setting_type_id,
            ]
        );
    
        return response()->json([
            'status' => true,
            'message' => 'Annual job cost updated successfully.',
            'data' => $jobCost,
        ]);
    }
    


    // public function settingOfType(SettingType $settingType)
    // {
    //     // Fetch settings of the specified type for the authenticated user
    //     $settings = auth()->user()->settings()->where("type", $settingType->id)->get();
    
    //     // Calculate the total cost of all settings
    //     $totalCost = $settings->sum('value'); // Assuming 'value' is the cost of each setting
    
    //     // Calculate the percentage for each setting based on the total cost
    //     $settings = $settings->map(function ($setting) use ($totalCost) {
    //         // Calculate individual setting's percentage of the total cost
    //         $settingPercentage = $totalCost > 0 ? ($setting->value / $totalCost) * 100 : 0;
            
    //         // Round the percentage to 2 decimal places and add it as an attribute to each setting
    //         $setting->percentage_of_total = round($settingPercentage, 2);
    
    //         return $setting;
    //     });
    
    //     // Set the response data with `total_cost`, `total_percentage`, and `settings`
    //     $this->response_data["data"] = [
    //         "total_cost" => round($totalCost, 2),
    //         "total_percentage" => 100,
    //         "settings" => $settings
    //     ];
    //     $this->response_data["status"] = true;
    
    //     return $this->sendJsonResponse();
    // }
    
    
    
public function settingOfType(SettingType $settingType)
{
    $user = auth()->user();
    
    $user = $user->is_admin ? $user : User::find($user->parent_id);
    
    $defaults = $this->defaultSettingsForType($settingType->id);

    // Current saved settings for this user and this type
    $savedSettings = $user->settings()->where('type', $settingType->id)->orderBy('sort_order', 'asc')->get();
    
    $savedMap = $savedSettings->keyBy('title');
    $titlesArray = $savedSettings->pluck('title')->toArray();

    $newSettings = [];

    // Loop through defaults and create missing ones
    foreach (array_values($defaults) as $sortOrder => $item) {
        if (is_array($defaults)) {
            $key = array_keys($defaults)[$sortOrder];
            $title = is_int($key) ? $item : $key;
            $slug = is_int($key) ? Str::slug($item) : $item;
        } else {
            $title = $item;
            $slug = Str::slug($item);
        }

        $normalizedTitlesArray = array_map('strtolower', $titlesArray);
        if (!in_array(strtolower($title), $normalizedTitlesArray)) {
            $newSettings[] = [
                'user_id' => $user->id,
                'company_id' => $user->company_id,
                'title' => $title,
                'slug' => $slug,
                'value' => 0,
                'type' => $settingType->id,
                'sort_order' => $sortOrder + 1,
                'is_edit' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
    }

    if (!empty($newSettings)) {
        \App\Models\Setting::insert($newSettings);
    }

    $finalSettings = $user->settings()->where('type', $settingType->id)->get();

    $company = $user->company;
    $estimatedAnnualJobCost = optional($company->userEstimatedAnnualJobCosts->first())->estimated_annual_job_cost;
    $totalValue = $finalSettings->sum('value');

    $finalSettings = $finalSettings->map(function ($setting) use ($estimatedAnnualJobCost) {
        $setting->percentage_of_total = $estimatedAnnualJobCost > 0
            ? round(($setting->value / $estimatedAnnualJobCost) * 100, 2)
            : 0;
        $setting->is_edit = $setting->is_edit ? true : false;
        return $setting;
    });

    // Custom sort for type 1 (Global Estimate Settings)
    if ($settingType->id == 1) {
        $desiredOrder = ['Labor Markup', 'Material Markup', 'Tax'];

        // Separate prioritized and other settings
        $prioritized = $finalSettings->filter(function ($item) use ($desiredOrder) {
            return in_array($item->title, $desiredOrder);
        })->sortBy(function ($item) use ($desiredOrder) {
            return array_search($item->title, $desiredOrder);
        });

        $others = $finalSettings->filter(function ($item) use ($desiredOrder) {
            return !in_array($item->title, $desiredOrder);
        })->sortBy('sort_order');

        // Merge prioritized with others
        $finalSettings = $prioritized->concat($others)->values();
    } else {
        // Default sort by sort_order
        $finalSettings = $finalSettings->sortBy('sort_order')->values();
    }

    $totalPercentage = $estimatedAnnualJobCost > 0
        ? round(($totalValue / $estimatedAnnualJobCost) * 100, 2)
        : 0;

    $this->response_data["data"] = [
        'type_id' => $settingType->id,
        'type_title' => $settingType->title,
        'total_cost' => round($totalValue, 2),
        'total_percentage' => $totalPercentage,
        'settings' => $finalSettings
    ];

    $this->response_data["status"] = true;

    return $this->sendJsonResponse();
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



public function addSetting(SettingType $settingType, Request $request)
{
    $validator = Validator::make($request->all(), [
        'title' => [
            'required',
            'string',
            Rule::unique("app_settings")->where(function ($query) use ($request, $settingType) {
                return $query
                    ->where("title", $request->get('title'))
                    ->where("type", $settingType->id)
                    ->where("user_id", $request->user()->id);
            })
        ],
        'value' => 'required|numeric|min:0'
    ]);

    if ($validator->fails()) {
        foreach ($validator->errors()->all() as $msg) {
            if (strlen(trim($msg)) > 1) {
                $this->response_data["message"] = $msg;
                return $this->sendJsonResponse();
            }
        }
    }

    $user = auth()->user();
    
    $user = $user->is_admin ? $user : User::find($user->parent_id);
    
    
    // Get the max order for this setting type + user
    $maxOrder = \App\Models\Setting::where('type', $settingType->id)
        ->where('user_id', $user->id)  // Make it user-specific
        ->max('sort_order');

    // Assign the new sort order by incrementing the max order value
    $newOrder = $maxOrder + 1;
    
    if (!$user) {
       
       
        return response()->json([
                'status' => false,
                'message' => 'User not found.',
            ], 404);
            
    }

    // Create the new setting
    $setting = $user->settings()->create([
        'title'      => $request->title,
        'type'       => $settingType->id,
        'company_id' => $user->company_id,
        'slug'       => Str::slug($request->title),
        'value'      => $request->value,
        'sort_order' => $newOrder,  // New sort_order user-wise
    ]);

    $this->response_data["data"] = $setting;
    $this->response_data["status"] = true;
    return $this->sendJsonResponse();
}



    // public function delete(Setting $setting)
    // {
    //     if($setting = auth()->user()->settings()->where("id",$setting->id)->first()){
    //         $setting->delete();
    //         $this->response_data["message"] = "Setting deleted successfully";
    //         $this->response_data["status"] = true;
    //         return $this->sendJsonResponse();
    //     }
    //     $this->response_data["message"] = "Setting doesn't exists";
    //     $this->response_data["status"] = false;
    //     return $this->sendJsonResponse();
    // }


public function delete(Setting $setting)
{
    
    
    $user = auth()->user();
    
    $user = $user->is_admin ? $user : User::find($user->parent_id);
    
    

    $setting = $user->settings()->where("id", $setting->id)->first();

    if (!$setting) {
        $this->response_data["message"] = "Setting doesn't exist";
        $this->response_data["status"] = false;
        return $this->sendJsonResponse();
    }

    DB::beginTransaction();

    try {
        // Delete related ProfitBudgetEstimateSetting records
        ProfitBudgetEstimateSetting::where('slug', $setting->slug)
            ->where('user_id', $user->id)
            ->where('company_id', $user->company_id)
            ->delete();

        // Delete the Setting
        $setting->delete();

        DB::commit();

        $this->response_data["message"] = "Setting deleted successfully";
        $this->response_data["status"] = true;
    } catch (\Exception $e) {
        DB::rollBack();

        $this->response_data["message"] = "Failed to delete setting: " . $e->getMessage();
        $this->response_data["status"] = false;
    }

    return $this->sendJsonResponse();
}
    public function edit(Setting $setting){
        $this->response_data["data"] = $setting;
        $this->response_data["status"] = true;
        return $this->sendJsonResponse();
    }

  

public function update(Setting $setting, Request $request)
{
    // Validation to ensure 'title' is unique per user, ignoring current record
    $validator = Validator::make($request->all(), [
        'title' => [
            'required',
            'string'
        ],
        'value' => 'required|numeric|min:0',
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

    // Update the setting without modifying the sort_order
    $updated = $setting->update([
        'title' => $request->title,
        'value' => $request->value,
    ]);

    if ($updated) {
        $setting->refresh(); // Refresh the model
        $this->response_data["data"] = $setting;
        $this->response_data["status"] = true;
    } else {
        $this->response_data["message"] = "Setting doesn't exist";
        $this->response_data["status"] = false;
    }

    return $this->sendJsonResponse();
}



public function reorderSetting(SettingType $settingType, Request $request)
{
    // Validate the request
    $validator = Validator::make($request->all(), [
        'setting_id' => 'required|integer|exists:app_settings,id',
        'reorder_id' => 'required|integer|min:1',
    ]);

    if ($validator->fails()) {
        foreach ($validator->errors()->all() as $msg) {
            if (strlen(trim($msg)) > 1) {
                $this->response_data["message"] = $msg;
                return $this->sendJsonResponse();
            }
        }
    }

    $user = auth()->user();
    $user = $user->is_admin ? $user : User::find($user->parent_id);
    
    $settingId = $request->input('setting_id');
    $newOrder = $request->input('reorder_id');

    DB::beginTransaction();

    try {
        // Fetch the specific setting belonging to user and type
        $setting = $user->settings()
            ->where('type', $settingType->id)
            ->find($settingId);

        if (!$setting) {
            throw new \Exception("Setting not found.");
        }

        $oldOrder = $setting->sort_order;

        $totalSettings = $user->settings()
            ->where('type', $settingType->id)
            ->count();

        if ($newOrder > $totalSettings) {
            throw new \Exception("The reorder_id must be within the valid range.");
        }

        // Reordering logic
        if ($newOrder > $oldOrder) {
            $user->settings()
                ->where('type', $settingType->id)
                ->whereBetween('sort_order', [$oldOrder + 1, $newOrder])
                ->decrement('sort_order');
        } elseif ($newOrder < $oldOrder) {
            $user->settings()
                ->where('type', $settingType->id)
                ->whereBetween('sort_order', [$newOrder, $oldOrder - 1])
                ->increment('sort_order');
        }

        $setting->sort_order = $newOrder;
        $setting->save();

        // Reindex all settings for this type
        $this->reindexSettingOrders($user->id, $settingType->id);

        $updatedSettings = $user->settings()
            ->where('type', $settingType->id)
            ->orderBy('sort_order')
            ->get();

        $this->response_data["status"] = true;
        $this->response_data["message"] = "Setting reordered successfully.";
        $this->response_data["data"] = $updatedSettings;

        DB::commit();
    } catch (\Exception $e) {
        DB::rollBack();

        $this->response_data["status"] = false;
        $this->response_data["message"] = "Failed to reorder setting.";
        $this->response_data["error"] = $e->getMessage();
    }

    return $this->sendJsonResponse();
}

/**
 * Reindex the sort orders of settings for a given user and type.
 */
private function reindexSettingOrders(int $userId, int $settingTypeId)
{
    $settings = \App\Models\Setting::where('user_id', $userId)
        ->where('type', $settingTypeId)
        ->orderBy('sort_order')
        ->get();

    $counter = 1;
    foreach ($settings as $setting) {
        $setting->sort_order = $counter++;
        $setting->save();
    }   
}


public function upload_setting_documents(Request $request)
{
    if (!auth()->check()) {
        return response()->json([
            'status'  => false,
            'message' => 'Unauthenticated'
        ], 401);
    }

    $validator = Validator::make($request->all(), [
        'file'      => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:20480',
        'fields'    => 'nullable',
        // 'construction_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:20480',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status'  => false,
            'message' => $validator->errors()->first()
        ], 422);
    }

    $user          = auth()->user();
    $uploadedFiles = [];

    if ($request->hasFile('file')) {
        $file     = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());

        if (!$this->isAllowedSettingDocumentExtension($extension)) {
            return response()->json([
                'status'  => false,
                'message' => 'Only PDF, DOC, DOCX, PNG, JPG and JPEG files are allowed.'
            ], 422);
        }

        $name     = $request->input('name');
        $signature = $request->input('sign_required'); // Get signature data from request
        $fields = $this->normalizeDocumentFields($request->input('fields', []));
        $filename = time() . '_worker_' . Str::random(10) . '.' . $extension;
        $path     = $file->storeAs('setting_documents', $filename, 'public');

        SettingDocument::where('user_id', $user->id)
            ->where('company_id', $user->company_id)
            ->where('document_type', $name)
            ->delete();

        $uploadedFiles[] = SettingDocument::create([
            'user_id'       => $user->id,
            'company_id'    => $user->company_id,
            'document_type' => 'file',
            'file_path'     => $path,
            'file_type'     => $extension,
            'document_name' => $name,  
            'signature_required' => $signature, // Save signature data to the database
            'fields' => $fields,
        ]);
    }

    // if ($request->hasFile('construction_file')) {
    //     $file     = $request->file('construction_file');
    //     $name     = $request->input('construction_name');  // ✅ Correct input key
    //     $filename = time() . '_contract_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
    //     $path     = $file->storeAs('setting_documents', $filename, 'public');

    //     SettingDocument::where('user_id', $user->id)
    //         ->where('company_id', $user->company_id)
    //         ->where('document_type', 'contract')
    //         ->delete();

    //     $uploadedFiles[] = SettingDocument::create([
    //         'user_id'       => $user->id,
    //         'company_id'    => $user->company_id,
    //         'document_type' => 'contract',
    //         'file_path'     => $path,
    //         'file_type'     => $file->getClientOriginalExtension(),
    //         'document_name' => $name,  // ✅ Fixed: was incorrectly using worker_name
    //     ]);
    // }

    if (empty($uploadedFiles)) {
        return response()->json([
            'status'  => false,
            'message' => 'No files uploaded'
        ], 422);
    }

    return response()->json([
        'status'  => true,
        'message' => 'Files uploaded successfully',
        'data'    => $uploadedFiles
    ]);
}



public function get_setting_documents(Request $request)
{
    if (!auth()->check()) {
        return response()->json([
            'status' => false,
            'message' => 'Unauthenticated'
        ], 401);
    }

    $user = auth()->user();

  $documents = SettingDocument::where('user_id', $user->id)
    ->where('document_type', '!=', 'estimate')
    ->latest()
    ->get();

    $response = [];
    
    foreach ($documents as $doc) {
        $response[] = [
            'id' => $doc->id,
            'document_type' => $doc->document_type,
            'file_type' => $doc->file_type,
            'file_url' => asset('public/storage/' . $doc->file_path),
            'preview_url' => url('/api/global-setting/upload-documents/' . $doc->id . '/preview'),
            'filled_preview_url' => url('/api/global-setting/upload-documents/' . $doc->id . '/filled-preview'),
            'label' => $doc->document_name,
            'signature_required' => $doc->signature_required,
            'fields' => $this->normalizeDocumentFields($doc->fields ?? []),
            'created_at' => $doc->created_at,
        ];
    }

    return response()->json([
        'status' => true,
        'data' => $response
    ]);
}

public function uploadEstimateDocuments(Request $request, Estimate $estimate)
{
    if (!auth()->check()) {
        return response()->json([
            'status'  => false,
            'message' => 'Unauthenticated'
        ], 401);
    }

    $user = auth()->user();
    if ((int) $estimate->company_id !== (int) $user->company_id) {
        return response()->json([
            'status'  => false,
            'message' => 'Estimate not found'
        ], 404);
    }

    $validator = Validator::make($request->all(), [
        'file' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:20480',
        'files' => 'nullable|array',
        'files.*' => 'file|mimes:pdf,jpg,jpeg,png,doc,docx|max:20480',
        'name' => 'nullable|string|max:255',
        'names' => 'nullable|array',
        'names.*' => 'nullable|string|max:255',
        'sign_required' => 'nullable|boolean',
        'signature_required' => 'nullable|boolean',
        'fields' => 'nullable',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status'  => false,
            'message' => $validator->errors()->first()
        ], 422);
    }

    $files = [];
    if ($request->hasFile('file')) {
        $files[] = $request->file('file');
    }
    if ($request->hasFile('files')) {
        foreach ($request->file('files') as $file) {
            $files[] = $file;
        }
    }

    if (empty($files)) {
        return response()->json([
            'status'  => false,
            'message' => 'No files uploaded'
        ], 422);
    }

    $uploadedDocuments = [];
    $names = $request->input('names', []);
    $signatureRequired = $request->boolean('signature_required', $request->boolean('sign_required'));
    $fields = $this->normalizeDocumentFields($request->input('fields', []));

    foreach ($files as $index => $file) {
        $extension = strtolower($file->getClientOriginalExtension());
        if (!$this->isAllowedSettingDocumentExtension($extension)) {
            return response()->json([
                'status'  => false,
                'message' => 'Only PDF, DOC, DOCX, PNG, JPG and JPEG files are allowed.'
            ], 422);
        }

        $filename = time() . '_estimate_' . $estimate->id . '_' . Str::random(10) . '.' . $extension;
        $path = $file->storeAs('setting_documents/estimates/' . $estimate->id, $filename, 'public');
        $documentName = $names[$index] ?? $request->input('name') ?? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

        $uploadedDocuments[] = SettingDocument::create([
            'user_id' => $user->id,
            'company_id' => $user->company_id,
            'estimate_id' => $estimate->id,
            'document_type' => 'sub_estimate_upload',
            'file_path' => $path,
            'file_type' => $extension,
            'document_name' => $documentName,
            'signature_required' => $signatureRequired,
            'fields' => $fields,
        ]);
    }

    return response()->json([
        'status' => true,
        'message' => 'Estimate documents uploaded successfully',
        'data' => $this->formatSettingDocuments($uploadedDocuments),
    ]);
}

public function getEstimateDocuments(Request $request, Estimate $estimate)
{
    if (!auth()->check()) {
        return response()->json([
            'status'  => false,
            'message' => 'Unauthenticated'
        ], 401);
    }

    $user = auth()->user();
    if ((int) $estimate->company_id !== (int) $user->company_id) {
        return response()->json([
            'status'  => false,
            'message' => 'Estimate not found'
        ], 404);
    }

    $documents = SettingDocument::where('estimate_id', $estimate->id)
        ->where('company_id', $user->company_id)
        ->where('document_type', 'sub_estimate_upload')
        ->latest()
        ->get();

    return response()->json([
        'status' => true,
        'data' => $this->formatSettingDocuments($documents),
    ]);
}

private function formatSettingDocuments($documents): array
{
    return collect($documents)->map(function ($doc) {
        return [
            'id' => $doc->id,
            'estimate_id' => $doc->estimate_id,
            'document_type' => $doc->document_type,
            'file_type' => $doc->file_type,
            'file_url' => asset('storage/' . $doc->file_path),
            'preview_url' => url('/api/global-setting/upload-documents/' . $doc->id . '/preview'),
            'filled_preview_url' => url('/api/global-setting/upload-documents/' . $doc->id . '/filled-preview'),
            'label' => $doc->document_name,
            'signature_required' => (bool) $doc->signature_required,
            'fields' => $this->normalizeDocumentFields($doc->fields ?? []),
            'created_at' => $doc->created_at,
        ];
    })->values()->all();
}

public function preview_setting_document(SettingDocument $document)
{
    $absolutePath = $this->resolveDocumentAbsolutePath($document);

    if (!$absolutePath) {
        return response()->json([
            'status' => false,
            'message' => 'Document file not found'
        ], 404);
    }

    try {
        $previewPdf = $this->buildSettingDocumentFieldPreviewPdf($document, $absolutePath);
    } catch (\Throwable $exception) {
        \Log::error('Unable to build setting document field preview: ' . $exception->getMessage(), [
            'document_id' => $document->id,
        ]);

        return response()->json([
            'status' => false,
            'message' => 'Unable to build document preview'
        ], 500);
    }

    return response($previewPdf, 200, [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => 'inline; filename="document_' . $document->id . '_fields_preview.pdf"',
    ]);
}



public function preview_filled_setting_document(SettingDocument $document)
{
    $absolutePath = $this->resolveDocumentAbsolutePath($document);

    if (!$absolutePath) {
        return response()->json([
            'status' => false,
            'message' => 'Document file not found'
        ], 404);
    }

    try {
        $previewPdf = $this->buildFilledSettingDocumentPreviewPdf($document, $absolutePath);
    } catch (\Throwable $exception) {
        \Log::error('Unable to build filled setting document preview: ' . $exception->getMessage(), [
            'document_id' => $document->id,
        ]);

        if (strtolower($document->file_type ?: pathinfo($absolutePath, PATHINFO_EXTENSION)) === 'pdf') {
            return $this->streamInlineFile($absolutePath, 'application/pdf');
        }

        return response()->json([
            'status' => false,
            'message' => 'Unable to build filled document preview'
        ], 500);
    }

    return response($previewPdf, 200, [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => 'inline; filename="document_' . $document->id . '_filled_preview.pdf"',
        'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        'Pragma' => 'no-cache',
        'Expires' => '0',
    ]);
}

private function buildFilledSettingDocumentPreviewPdf(SettingDocument $document, string $filePath): string
{
    $pdf = new Fpdi();
    $pdf->SetAutoPageBreak(false, 0);

    $tempDir = storage_path('app/temp/filled_previews');
    if (!is_dir($tempDir)) {
        mkdir($tempDir, 0755, true);
    }

    $tempFiles = [];
    $fieldContext = $this->buildDocumentFieldContext([
        'user_id' => $document->user_id,
        'company_id' => $document->company_id,
        'estimate_id' => $document->estimate_id,
        'document_ids' => [$document->id],
        'field_signature_paths' => [],
        'field_values' => [],
    ], $document->signed_at ?: now());

    try {
        $this->appendDocumentToSignedPacket($pdf, $document, $tempFiles, $tempDir, false, $fieldContext);

        if ($pdf->PageNo() === 0) {
            throw new \RuntimeException('No preview pages were generated.');
        }

        return $pdf->Output('S');
    } finally {
        foreach ($tempFiles as $tempFile) {
            if (is_file($tempFile)) {
                @unlink($tempFile);
            }
        }
    }
}

private function isAllowedSettingDocumentExtension(string $extension): bool
{
    return in_array($extension, ['pdf', 'doc', 'docx', 'png', 'jpg','jpeg'], true);
}

private function normalizeDocumentFields($fields): array
{
    if (is_string($fields)) {
        $decoded = json_decode($fields, true);
        $fields = is_array($decoded) ? $decoded : [];
    }

    if (!is_array($fields)) {
        return [];
    }

    return collect($fields)
        ->filter(function ($field) {
            return is_array($field);
        })
        ->map(function ($field, $index) {
            $type = strtolower((string) ($field['type'] ?? $field['field_type'] ?? 'text'));
            $key = (string) ($field['key'] ?? $field['name'] ?? $field['label'] ?? $type);
            $normalizedKey = Str::slug($key, '_');

            if (Str::contains($normalizedKey, ['signature', 'e_signature', 'esignature', 'digital_signature'])) {
                $type = 'signature';
            }

            return [
                'id' => (string) ($field['id'] ?? $key . '_' . $index),
                'type' => in_array($type, ['text', 'date', 'signature'], true) ? $type : 'text',
                'key' => $key,
                'label' => (string) ($field['label'] ?? $key),
                'page' => max(1, (int) ($field['page'] ?? $field['page_number'] ?? 1)),
                'x' => (float) ($field['x'] ?? $field['left'] ?? 0),
                'y' => (float) ($field['y'] ?? $field['top'] ?? 0),
                'width' => (float) ($field['width'] ?? $field['w'] ?? 40),
                'height' => (float) ($field['height'] ?? $field['h'] ?? 10),
                'unit' => strtolower((string) ($field['unit'] ?? 'mm')),
                'page_width' => isset($field['page_width']) ? (float) $field['page_width'] : null,
                'page_height' => isset($field['page_height']) ? (float) $field['page_height'] : null,
                'value' => $field['value'] ?? null,
                'font_size' => isset($field['font_size']) ? (float) $field['font_size'] : null,
            ];
        })
        ->values()
        ->all();
}

private function normalizeSubmittedFieldValues($fieldValues): array
{
    if (is_string($fieldValues)) {
        $decoded = json_decode($fieldValues, true);
        $fieldValues = is_array($decoded) ? $decoded : [];
    }

    if (!is_array($fieldValues)) {
        return [];
    }

    $normalized = [];

    foreach ($fieldValues as $documentId => $values) {
        if (is_string($values)) {
            $decoded = json_decode($values, true);
            $values = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($values)) {
            continue;
        }

        $documentKey = (string) $documentId;
        $normalized[$documentKey] = [];

        foreach ($values as $fieldKey => $value) {
            if (is_array($value) || is_object($value)) {
                continue;
            }

            $normalized[$documentKey][(string) $fieldKey] = $value === null ? '' : (string) $value;
        }
    }

    return $normalized;
}

public function delete_setting_document(SettingDocument $document)
{
    if (!auth()->check()) {
        return response()->json([
            'status' => false,
            'message' => 'Unauthenticated'
        ], 401);
    }

    $user = auth()->user();

    if ($document->user_id !== $user->id || $document->company_id !== $user->company_id) {
        return response()->json([
            'status' => false,
            'message' => 'Document not found'
        ], 404);
    }

    if ($document->file_path && Storage::disk('public')->exists($document->file_path)) {
        Storage::disk('public')->delete($document->file_path);
    }

    if ($document->signature_path && Storage::disk('public')->exists($document->signature_path)) {
        Storage::disk('public')->delete($document->signature_path);
    }

    $document->delete();

    return response()->json([
        'status' => true,
        'message' => 'Document deleted successfully'
    ]);
}

// public function mergeDocuments(Request $request)
// {
//     try {
//         $user = auth()->user();
        
//         // Get documents ordered by ID or created_at for consistent merging
//         $documents = SettingDocument::where('user_id', $user->id)
//             ->orderBy('id', 'asc')
//             ->get();
        
//         if ($documents->isEmpty()) {
//             return response()->json([
//                 'status' => false,
//                 'message' => 'No documents found to merge.'
//             ], 400);
//         }
        
//         // Initialize FPDI
//         $pdf = new Fpdi();
//         $pdf->SetAutoPageBreak(false, 0);
        
//         // Create temp directory for converted files
//         $tempDir = storage_path('app/temp/merged_' . uniqid());
//         if (!file_exists($tempDir)) {
//             mkdir($tempDir, 0755, true);
//         }
        
//         $tempFiles = []; // Track temp files for cleanup
//         $pageCount = 0; // Track total pages manually
        
//         foreach ($documents as $doc) {
//             $filePath = $this->resolveDocumentAbsolutePath($doc);
            
//             // Check if file exists
//             if (!$filePath) {
//                 \Log::warning("File not found for document ID: {$doc->id}", [
//                     'file_path' => $doc->file_path,
//                 ]);
//                 continue;
//             }
            
//             $fileType = strtolower($doc->file_type);
            
//             // HANDLE PDF
//             if ($fileType == 'pdf') {
//                 try {
//                     $sourceFile = $this->preparePdfForFpdi($pdf, $filePath, $tempFiles, $tempDir);
//                     $totalPages = $pdf->setSourceFile($sourceFile);
//                     $pageCount += $totalPages;
                    
//                     for ($i = 1; $i <= $totalPages; $i++) {
//                         $template = $pdf->importPage($i);
//                         $size = $pdf->getTemplateSize($template);
                        
//                         // Use A4 as fallback if size is invalid
//                         if ($size['width'] <= 0 || $size['height'] <= 0) {
//                             $size = ['width' => 210, 'height' => 297, 'orientation' => 'P'];
//                         }
                        
//                         $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
//                         $pdf->useTemplate($template);
//                     }
//                 } catch (\Exception $e) {
//                     \Log::error("Error processing PDF {$doc->id}: " . $e->getMessage());
//                     continue;
//                 }
//             }
            
//             // HANDLE IMAGES
//             elseif (in_array($fileType, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'])) {
//                 try {
//                     $imageInfo = getimagesize($filePath);
//                     if ($imageInfo === false) {
//                         \Log::warning("Invalid image file: {$filePath}");
//                         continue;
//                     }
                    
//                     list($width, $height) = $imageInfo;
                    
//                     // Convert pixels to mm (DPI: 72)
//                     $widthMM = $width * 0.352777;
//                     $heightMM = $height * 0.352777;
                    
//                     // Calculate orientation
//                     $orientation = ($widthMM > $heightMM) ? 'L' : 'P';
                    
//                     // Scale down if image is too large (max A3 size: 420x297mm)
//                     $maxWidth = 420;
//                     $maxHeight = 297;
                    
//                     if ($widthMM > $maxWidth || $heightMM > $maxHeight) {
//                         $scale = min($maxWidth / $widthMM, $maxHeight / $heightMM);
//                         $widthMM = $widthMM * $scale;
//                         $heightMM = $heightMM * $scale;
//                     }
                    
//                     $pdf->AddPage($orientation, [$widthMM, $heightMM]);
//                     $pdf->Image($filePath, 0, 0, $widthMM, $heightMM);
//                     $pageCount++; // Increment page count for image
                    
//                 } catch (\Exception $e) {
//                     \Log::error("Error processing image {$doc->id}: " . $e->getMessage());
//                     continue;
//                 }
//             }
            
//             // HANDLE WORD DOCUMENTS (DOC, DOCX)
//             elseif (in_array($fileType, ['doc', 'docx'])) {
//                 try {
//                     // Convert Word to PDF
//                     $convertedPdfPath = $tempDir . '/' . uniqid() . '.pdf';
//                     DocumentConverter::convertWordToPdf($filePath, $convertedPdfPath);
//                     $tempFiles[] = $convertedPdfPath;
                    
//                     // Add converted PDF pages to final PDF
//                     $totalPages = $pdf->setSourceFile($convertedPdfPath);
//                     $pageCount += $totalPages;
                    
//                     for ($i = 1; $i <= $totalPages; $i++) {
//                         $template = $pdf->importPage($i);
//                         $size = $pdf->getTemplateSize($template);
                        
//                         if ($size['width'] <= 0 || $size['height'] <= 0) {
//                             $size = ['width' => 210, 'height' => 297, 'orientation' => 'P'];
//                         }
                        
//                         $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
//                         $pdf->useTemplate($template);
//                     }
                    
//                 } catch (\Exception $e) {
//                     \Log::error("Error processing Word document {$doc->id}: " . $e->getMessage());
//                     continue;
//                 }
//             }
            
//             // HANDLE TEXT FILES
//             elseif (in_array($fileType, ['txt', 'rtf'])) {
//                 try {
//                     $content = file_get_contents($filePath);
                    
//                     // Create a simple PDF page with text
//                     $pdf->AddPage();
//                     $pdf->SetFont('Helvetica', '', 12);
//                     $pdf->SetXY(10, 10);
                    
//                     // Split text into lines
//                     $lines = explode("\n", wordwrap($content, 80, "\n"));
//                     $y = 10;
//                     $linesPerPage = 0;
                    
//                     foreach ($lines as $line) {
//                         if ($y > 280) { // Page limit reached
//                             $pdf->AddPage();
//                             $y = 10;
//                             $linesPerPage = 0;
//                         }
//                         $pdf->Cell(0, 10, $line, 0, 1, 'L');
//                         $y += 10;
//                         $linesPerPage++;
//                     }
                    
//                     // Calculate pages for text content (approximate)
//                     $textPages = ceil(count($lines) / 65); // ~65 lines per page
//                     $pageCount += max(1, $textPages);
                    
//                 } catch (\Exception $e) {
//                     \Log::error("Error processing text file {$doc->id}: " . $e->getMessage());
//                     continue;
//                 }
//             }
//         }
        
//         // Check if PDF has any pages
//         if ($pageCount === 0) {
//             // Cleanup temp files
//             $this->cleanupTempFiles($tempFiles, $tempDir);
            
//             return response()->json([
//                 'status' => false,
//                 'message' => 'No valid pages were found to merge.'
//             ], 400);
//         }
        
//         // Generate unique filename
//         $fileName = 'merged_' . uniqid() . '_' . time() . '.pdf';
//         $storagePath = 'merged_documents/' . date('Y/m');
//         $fullPath = public_path('storage/' . $storagePath);
        
//         // Create directory if it doesn't exist
//         if (!file_exists($fullPath)) {
//             mkdir($fullPath, 0755, true);
//         }
        
//         $outputPath = $fullPath . '/' . $fileName;
//         $pdf->Output($outputPath, 'F');
        
//         // Cleanup temp files
//         $this->cleanupTempFiles($tempFiles, $tempDir);
        
//         return response()->json([
//             'status' => true,
//             'message' => 'Documents merged successfully',
//             'file_url' => $request->getSchemeAndHttpHost() . '/storage/' . $storagePath . '/' . $fileName,
//             'page_count' => $pageCount,
//             'document_count' => $documents->count(),
//             // 'supported_types' => ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'doc', 'docx', 'txt', 'rtf']
//         ]);
        
//     } catch (\Exception $e) {
//         \Log::error('Merge documents error: ' . $e->getMessage(), [
//             'user_id' => auth()->id(),
//             'trace' => $e->getTraceAsString()
//         ]);
        
//         return response()->json([
//             'status' => false,
//             'message' => 'Failed to merge documents: ' . $e->getMessage()
//         ], 500);
//     }
// }

/**
 * Cleanup temporary files
 */
private function cleanupTempFiles($tempFiles, $tempDir)
{
    foreach ($tempFiles as $file) {
        if (file_exists($file)) {
            @unlink($file);
        }
    }
    
    if (file_exists($tempDir)) {
        @rmdir($tempDir);
    }
}

private function getOrderedSigningDocuments(array $documentIds)
{
    $documentOrder = array_flip($documentIds);

    return SettingDocument::whereIn('id', $documentIds)
        ->get()
        ->sortBy(function ($document) use ($documentOrder) {
            return $documentOrder[$document->id] ?? PHP_INT_MAX;
        })
        ->values();
}

private function getSigningCacheKey(string $token): string
{
    return 'sign_token_' . $token;
}

private function getSigningSession(string $token): ?array
{
    $data = \Cache::get($this->getSigningCacheKey($token));

    return is_array($data) && isset($data['document_ids']) ? $data : null;
}

private function putSigningSession(string $token, array $data)
{
    // \Cache::put($this->getSigningCacheKey($token), $data, now()->addHours(48));

     \Cache::put($this->getSigningCacheKey($token),$data,now()->addWeek() // 1 week expiration
    );
}

private function resolveSigningDocuments(array $data)
{
    return $this->getOrderedSigningDocuments($data['document_ids'])
        ->where('user_id', $data['user_id'] ?? null)
        ->where('company_id', $data['company_id'] ?? null)
        ->values();
}

private function resolveDocumentAbsolutePath(SettingDocument $document): ?string
{
    $storagePath = storage_path('app/public/' . $document->file_path);
    if (file_exists($storagePath)) {
        return $storagePath;
    }

    $publicStoragePath = public_path('storage/' . $document->file_path);
    if (file_exists($publicStoragePath)) {
        return $publicStoragePath;
    }

    return null;
}

private function appendPdfPagesToPacket(
    Fpdi $pdf,
    string $filePath,
    bool $reserveSignatureSpace = false,
    ?SettingDocument $document = null,
    array $fieldContext = [],
    array &$tempFiles = [],
    string $tempDir = ''
)
{
    $sourceFile = $this->preparePdfForFpdi($pdf, $filePath, $tempFiles, $tempDir);
    $totalPages = $pdf->setSourceFile($sourceFile);

    for ($pageNumber = 1; $pageNumber <= $totalPages; $pageNumber++) {
        $template = $pdf->importPage($pageNumber);
        $size = $pdf->getTemplateSize($template);

        if (($size['width'] ?? 0) <= 0 || ($size['height'] ?? 0) <= 0) {
            $size = ['width' => 210, 'height' => 297, 'orientation' => 'P'];
        }

        if ($reserveSignatureSpace && $pageNumber === $totalPages) {
            $footerReserve = 65;
            $pdf->AddPage($size['orientation'], [$size['width'], $size['height'] + $footerReserve]);
            $pdf->useTemplate($template, 0, 0, $size['width'], $size['height']);
            $this->overlayDocumentFields($pdf, $document, $pageNumber, $size, $fieldContext, $tempFiles, $tempDir);
            continue;
        }

        $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
        $pdf->useTemplate($template);
        $this->overlayDocumentFields($pdf, $document, $pageNumber, $size, $fieldContext, $tempFiles, $tempDir);
    }
}

private function preparePdfForFpdi(Fpdi $pdf, string $filePath, array &$tempFiles, string $tempDir): string
{
    try {
        $pdf->setSourceFile($filePath);
        return $filePath;
    } catch (\Throwable $exception) {
        $normalizedPath = $this->normalizePdfForFpdi($filePath, $tempFiles, $tempDir);
        if ($normalizedPath && is_file($normalizedPath)) {
            return $normalizedPath;
        }

        throw $exception;
    }
}

private function normalizePdfForFpdi(string $filePath, array &$tempFiles, string $tempDir): ?string
{
    if ($tempDir === '' || !is_dir($tempDir)) {
        $tempDir = storage_path('app/temp/fpdi_normalized');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
    }

    $outputPath = $tempDir . '/' . uniqid('fpdi_normalized_') . '.pdf';
    $commands = $this->pdfNormalizationCommands($filePath, $outputPath);

    foreach ($commands as $command) {
        @exec($command, $output, $exitCode);
        if ($exitCode === 0 && is_file($outputPath) && filesize($outputPath) > 0) {
            $tempFiles[] = $outputPath;
            return $outputPath;
        }

        if (is_file($outputPath)) {
            @unlink($outputPath);
        }
    }

    return null;
}

private function pdfNormalizationCommands(string $inputPath, string $outputPath): array
{
    $input = escapeshellarg($inputPath);
    $output = escapeshellarg($outputPath);

    $commands = [];
    foreach (['qpdf'] as $binary) {
        if ($this->commandExists($binary)) {
            $commands[] = $binary . ' --object-streams=disable --stream-data=uncompress ' . $input . ' ' . $output;
        }
    }

    foreach (['gs', 'gswin64c', 'gswin32c'] as $binary) {
        if ($this->commandExists($binary)) {
            $commands[] = $binary . ' -q -dNOPAUSE -dBATCH -dSAFER -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dPDFSETTINGS=/prepress -sOutputFile=' . $output . ' ' . $input;
        }
    }

    if ($this->commandExists('magick')) {
        $commands[] = 'magick ' . $input . ' -compress none ' . $output;
    }

    return $commands;
}

private function commandExists(string $command): bool
{
    $probe = stripos(PHP_OS_FAMILY, 'Windows') === 0
        ? 'where ' . escapeshellarg($command)
        : 'command -v ' . escapeshellarg($command);

    @exec($probe, $output, $exitCode);

    return $exitCode === 0;
}
private function appendImagePageToPacket(
    Fpdi $pdf,
    string $filePath,
    bool $reserveSignatureSpace = false,
    ?SettingDocument $document = null,
    array $fieldContext = [],
    array &$tempFiles = [],
    string $tempDir = ''
)
{
    $imageInfo = @getimagesize($filePath);
    if ($imageInfo === false) {
        throw new \RuntimeException('Invalid image file.');
    }

    [$width, $height] = $imageInfo;
    $widthMm  = $width  * 0.264583;
    $heightMm = $height * 0.264583;

    // ── Margins ──────────────────────────────────────────
    $marginTop   = 10; // mm
    $marginLeft  = 10; // mm
    $marginRight = 10; // mm
    // ─────────────────────────────────────────────────────

    $footerReserve = $reserveSignatureSpace ? 65 : 0;
    $pageWidth     = 210;
    $pageHeight    = 297;

    $maxWidth  = $pageWidth  - $marginLeft - $marginRight;
    $scale = $maxWidth / max($widthMm, 1);

    $renderWidth  = $widthMm  * $scale;
    $renderHeight = $heightMm * $scale;
    $pageHeight = max($pageHeight, $marginTop + $renderHeight + $footerReserve);

    $pdf->AddPage('P', [$pageWidth, $pageHeight]);

    // Render at full page width. Let page height grow for tall screenshots.
    $x = $marginLeft + ($maxWidth  - $renderWidth)  / 2;
    $y = $marginTop;

    $pdf->Image($filePath, $x, $y, $renderWidth, $renderHeight);
    $this->overlayDocumentFields($pdf, $document, 1, [
        'width' => $pageWidth,
        'height' => $pageHeight,
    ], $fieldContext, $tempFiles, $tempDir);
}

private function appendTextPageToPacket(Fpdi $pdf, string $filePath, bool $reserveSignatureSpace = false)
{
    $content = @file_get_contents($filePath);
    if ($content === false) {
        throw new \RuntimeException('Unable to read text document.');
    }

    $pdf->AddPage('P', 'A4');
    $pdf->SetMargins(18, 18, 18);
    $pdf->SetFont('Helvetica', '', 11);
    $pdf->SetXY(18, 18);
    $pdf->MultiCell(174, 6, utf8_decode($content));
}

private function buildSettingDocumentFieldPreviewPdf(SettingDocument $document, string $filePath): string
{
    $pdf = new Fpdi();
    $pdf->SetAutoPageBreak(false, 0);

    $fileType = strtolower($document->file_type ?: pathinfo($filePath, PATHINFO_EXTENSION));

    if ($fileType === 'pdf') {
        $this->appendPdfFieldPreviewPages($pdf, $document, $filePath);
    } elseif (in_array($fileType, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'])) {
        $this->appendImageFieldPreviewPage($pdf, $document, $filePath);
    } elseif (in_array($fileType, ['doc', 'docx'])) {
        $previewPdfPath = $this->resolveWordPreviewPdfPath($document, $filePath);
        $this->appendPdfFieldPreviewPages($pdf, $document, $previewPdfPath);
    } elseif (in_array($fileType, ['txt', 'rtf'])) {
        $this->appendTextPageToPacket($pdf, $filePath);
        $this->overlayDocumentFieldLabels($pdf, $document, 1, [
            'width' => $pdf->GetPageWidth(),
            'height' => $pdf->GetPageHeight(),
        ]);
    } else {
        throw new \RuntimeException('Unsupported document type: ' . $fileType);
    }

    if ($pdf->PageNo() === 0) {
        throw new \RuntimeException('No preview pages were generated.');
    }

    return $pdf->Output('S');
}

private function appendPdfFieldPreviewPages(Fpdi $pdf, SettingDocument $document, string $filePath): void
{
    $tempDir = storage_path('app/temp/field_previews');
    if (!is_dir($tempDir)) {
        mkdir($tempDir, 0755, true);
    }

    $tempFiles = [];
    $sourceFile = $this->preparePdfForFpdi($pdf, $filePath, $tempFiles, $tempDir);
    $totalPages = $pdf->setSourceFile($sourceFile);

    for ($pageNumber = 1; $pageNumber <= $totalPages; $pageNumber++) {
        $template = $pdf->importPage($pageNumber);
        $size = $pdf->getTemplateSize($template);

        if (($size['width'] ?? 0) <= 0 || ($size['height'] ?? 0) <= 0) {
            $size = ['width' => 210, 'height' => 297, 'orientation' => 'P'];
        }

        $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
        $pdf->useTemplate($template);
        $this->overlayDocumentFieldLabels($pdf, $document, $pageNumber, $size);
    }
}

private function appendImageFieldPreviewPage(Fpdi $pdf, SettingDocument $document, string $filePath): void
{
    $imageInfo = @getimagesize($filePath);

    if ($imageInfo === false) {
        throw new \RuntimeException('Invalid image file.');
    }

    [$width, $height] = $imageInfo;
    $widthMm = $width * 0.264583;
    $heightMm = $height * 0.264583;
    $marginTop = 10;
    $marginLeft = 10;
    $marginRight = 10;
    $pageWidth = 210;
    $pageHeight = 297;
    $maxWidth = $pageWidth - $marginLeft - $marginRight;
    $scale = $maxWidth / max($widthMm, 1);
    $renderWidth = $widthMm * $scale;
    $renderHeight = $heightMm * $scale;
    $pageHeight = max($pageHeight, $marginTop + $renderHeight);

    $pdf->AddPage('P', [$pageWidth, $pageHeight]);

    $x = $marginLeft + ($maxWidth - $renderWidth) / 2;
    $y = $marginTop;

    $pdf->Image($filePath, $x, $y, $renderWidth, $renderHeight);
    $this->overlayDocumentFieldLabels($pdf, $document, 1, [
        'width' => $pageWidth,
        'height' => $pageHeight,
    ]);
}

private function overlayDocumentFieldLabels(Fpdi $pdf, SettingDocument $document, int $pageNumber, array $pageSize): void
{
    $fields = $this->normalizeDocumentFields($document->fields ?? []);

    if (empty($fields)) {
        return;
    }

    $pageWidth = (float) ($pageSize['width'] ?? $pdf->GetPageWidth());
    $pageHeight = (float) ($pageSize['height'] ?? $pdf->GetPageHeight());

    foreach ($fields as $field) {
        if ((int) ($field['page'] ?? 1) !== $pageNumber) {
            continue;
        }

        $rect = $this->resolveDocumentFieldRect($field, $pageWidth, $pageHeight);
        $label = trim((string) ($field['label'] ?? $field['key'] ?? $field['type'] ?? 'Field'));

        if ($label === '') {
            $label = 'Field';
        }

        $fontSize = $field['font_size'] ?: min(11, max(7, $rect['height'] * 1.1));
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetDrawColor(13, 52, 71);
        $pdf->SetLineWidth(0.35);
        $pdf->Rect($rect['x'], $rect['y'], $rect['width'], $rect['height'], 'DF');
        $pdf->SetFont('Helvetica', 'B', $fontSize);
        $pdf->SetTextColor(220, 0, 0);
        $pdf->SetXY($rect['x'] + 1, $rect['y'] + max(0.5, ($rect['height'] - $fontSize * 0.45) / 2));
        $pdf->MultiCell(max(1, $rect['width'] - 2), max(3, $fontSize * 0.45), utf8_decode($label), 0, 'C');
    }
}

private function appendDocumentToSignedPacket(Fpdi $pdf, SettingDocument $document, array &$tempFiles, string $tempDir, bool $reserveSignatureSpace = false, array $fieldContext = [])
{
    $filePath = $this->resolveDocumentAbsolutePath($document);
    if (!$filePath) {
        throw new \RuntimeException('Source document file not found.');
    }

    $fileType = strtolower($document->file_type ?: pathinfo($filePath, PATHINFO_EXTENSION));

    if ($fileType === 'pdf') {
        $this->appendPdfPagesToPacket($pdf, $filePath, $reserveSignatureSpace, $document, $fieldContext, $tempFiles, $tempDir);
        return;
    }

    if (in_array($fileType, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'])) {
        $this->appendImagePageToPacket($pdf, $filePath, $reserveSignatureSpace, $document, $fieldContext, $tempFiles, $tempDir);
        return;
    }

    if (in_array($fileType, ['doc', 'docx'])) {
        $convertedPdfPath = $tempDir . '/' . uniqid('signed_') . '.pdf';
        DocumentConverter::convertWordToPdf($filePath, $convertedPdfPath);
        $tempFiles[] = $convertedPdfPath;
        $this->appendPdfPagesToPacket($pdf, $convertedPdfPath, $reserveSignatureSpace, $document, $fieldContext, $tempFiles, $tempDir);
        return;
    }

    if (in_array($fileType, ['txt', 'rtf'])) {
        $this->appendTextPageToPacket($pdf, $filePath);
        return;
    }

    throw new \RuntimeException('Unsupported document type: ' . $fileType);
}

private function buildDocumentFieldContext(array $sessionData, $signedAt): array
{
    $user = User::with('company')->find($sessionData['user_id'] ?? null);
    $estimate = null;

    if (!empty($sessionData['estimate_id'])) {
        $estimate = Estimate::with(['customer', 'company'])
            ->where('id', $sessionData['estimate_id'])
            ->where('company_id', $sessionData['company_id'] ?? null)
            ->first();
    }

    $company = optional($estimate)->company ?: optional($user)->company;
    $customer = optional($estimate)->customer;
    $signedDate = $signedAt ? $signedAt->format('m/d/Y') : now()->format('m/d/Y');
    $contractPrice = optional($estimate)->contract_price;

    return [
        'user' => $user,
        'estimate' => $estimate,
        'company' => $company,
        'customer' => $customer,
        'signed_at' => $signedAt,
        'field_signature_paths' => $sessionData['field_signature_paths'] ?? [],
        'field_values' => $this->normalizeSubmittedFieldValues($sessionData['field_values'] ?? []),
        'values' => [
            'date' => $signedDate,
            'signed_date' => $signedDate,
            'owner_name' => optional($customer)->full_name ?: optional($customer)->name,
            'name' => optional($customer)->full_name ?: optional($customer)->name,
            'customer_name' => optional($customer)->full_name ?: optional($customer)->name,
            'customer_address' => optional($customer)->address,
            'customer_email' => optional($customer)->email,
            'customer_phone' => optional($customer)->phone,
            'company_name' => optional($company)->name,
            'contractor_name' => optional($company)->name,
            'company_phone' => optional($user)->phone,
            'company_email' => optional($user)->email,
            'license_number' => optional($company)->license_no,
            'license_no' => optional($company)->license_no,
            'address' => optional($customer)->address,
            'work_address' => optional($customer)->address,
            'company_address' => optional($company)->address,
            'scope_of_work' => optional($estimate)->estimate_scope,
            'scope' => optional($estimate)->estimate_scope,
            'grand_total' => is_numeric($contractPrice) ? '$' . number_format((float) $contractPrice, 2) : null,
            'contract_price' => is_numeric($contractPrice) ? '$' . number_format((float) $contractPrice, 2) : null,
            'estimate_no' => optional($estimate)->key ?: optional($estimate)->id,
            'estimate_number' => optional($estimate)->key ?: optional($estimate)->id,
            'quote_date' => optional($estimate)->created_at ? $estimate->created_at->format('M j, Y') : null,
            'qoute_date' => optional($estimate)->created_at ? $estimate->created_at->format('M j, Y') : null,
        ],
    ];
}

private function overlayDocumentFields(Fpdi $pdf, ?SettingDocument $document, int $pageNumber, array $pageSize, array $fieldContext, array &$tempFiles, string $tempDir): void
{
    if (!$document) {
        return;
    }

    $fields = $this->normalizeDocumentFields($document->fields ?? []);
    if (empty($fields)) {
        return;
    }

    $pageWidth = (float) ($pageSize['width'] ?? $pdf->GetPageWidth());
    $pageHeight = (float) ($pageSize['height'] ?? $pdf->GetPageHeight());

    foreach ($fields as $field) {
        if ((int) ($field['page'] ?? 1) !== $pageNumber) {
            continue;
        }

        $rect = $this->resolveDocumentFieldRect($field, $pageWidth, $pageHeight);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetDrawColor(255, 255, 255);
        $pdf->Rect($rect['x'], $rect['y'], $rect['width'], $rect['height'], 'F');

        if (($field['type'] ?? 'text') === 'signature') {
            $signaturePath = $this->resolveFieldSignaturePath($document, $field, $fieldContext);
            if ($signaturePath && file_exists($signaturePath)) {
                $signatureImagePath = $this->prepareTransparentSignatureForPacket($signaturePath, $tempFiles, $tempDir);
                $pdf->Image($signatureImagePath, $rect['x'] + 1, $rect['y'] + 1, max(1, $rect['width'] - 2), max(1, $rect['height'] - 2), 'PNG');
            }
            continue;
        }

        $value = $this->resolveDocumentFieldValue($field, $fieldContext, $document->id);
        if ($value === '') {
            continue;
        }

        $fontSize = $field['font_size'] ?: min(12, max(7, $rect['height'] * 1.25));
        $pdf->SetFont('Helvetica', '', $fontSize);
        $pdf->SetTextColor(18, 27, 38);
        $pdf->SetXY($rect['x'] + 1, $rect['y'] + 1);
        $pdf->MultiCell(max(1, $rect['width'] - 2), max(3, $fontSize * 0.45), utf8_decode($value), 0, 'C');
    }
}

private function resolveDocumentFieldRect(array $field, float $pageWidth, float $pageHeight): array
{
    $x = (float) ($field['x'] ?? 0);
    $y = (float) ($field['y'] ?? 0);
    $width = (float) ($field['width'] ?? 40);
    $height = (float) ($field['height'] ?? 10);
    $unit = strtolower((string) ($field['unit'] ?? 'mm'));
    $baseWidth = (float) ($field['page_width'] ?? 0);
    $baseHeight = (float) ($field['page_height'] ?? 0);

    if (in_array($unit, ['percent', 'percentage', 'ratio'], true) || ($x <= 1 && $y <= 1 && $width <= 1 && $height <= 1)) {
        $x *= $pageWidth;
        $width *= $pageWidth;
        $y *= $pageHeight;
        $height *= $pageHeight;
    } elseif (in_array($unit, ['px', 'pixel', 'pixels'], true) && $baseWidth > 0 && $baseHeight > 0) {
        $x = ($x / $baseWidth) * $pageWidth;
        $width = ($width / $baseWidth) * $pageWidth;
        $y = ($y / $baseHeight) * $pageHeight;
        $height = ($height / $baseHeight) * $pageHeight;
    }

    return [
        'x' => max(0, min($x, $pageWidth)),
        'y' => max(0, min($y, $pageHeight)),
        'width' => max(1, min($width, $pageWidth)),
        'height' => max(1, min($height, $pageHeight)),
    ];
}

private function resolveDocumentFieldValue(array $field, array $fieldContext, ?int $documentId = null): string
{
    if ($documentId) {
        $submittedValue = $this->resolveSubmittedDocumentFieldValue($field, $fieldContext, $documentId);
        if ($submittedValue !== null) {
            return $submittedValue;
        }
    }

    if (isset($field['value']) && $field['value'] !== null && $field['value'] !== '') {
        return (string) $field['value'];
    }

    $key = Str::slug((string) ($field['key'] ?? $field['label'] ?? ''), '_');
    $values = $fieldContext['values'] ?? [];

    if (array_key_exists($key, $values)) {
        return (string) ($values[$key] ?? '');
    }

    if (Str::contains($key, ['grand_total', 'total', 'price'])) {
        return (string) ($values['grand_total'] ?? '');
    }

    if (Str::contains($key, ['scope'])) {
        return (string) ($values['scope_of_work'] ?? '');
    }

    if (Str::contains($key, ['license'])) {
        return (string) ($values['license_number'] ?? '');
    }

    if (Str::contains($key, ['company', 'contractor'])) {
        return (string) ($values['company_name'] ?? '');
    }

    if (Str::contains($key, ['address'])) {
        if (Str::contains($key, ['company'])) {
            return (string) ($values['company_address'] ?? '');
        }

        return (string) ($values['customer_address'] ?? $values['address'] ?? '');
    }

    if (Str::contains($key, ['date'])) {
        return (string) ($values['date'] ?? '');
    }

    if (Str::contains($key, ['owner', 'customer', 'name'])) {
        return (string) ($values['customer_name'] ?? '');
    }

    if (Str::contains($key, ['estimate_no', 'estimate_number', 'estimate'])) {
        return (string) ($values['estimate_no'] ?? '');
    }

    return '';
}

private function resolveSubmittedDocumentFieldValue(array $field, array $fieldContext, int $documentId): ?string
{
    $submittedValues = $fieldContext['field_values'] ?? [];
    $documentValues = $submittedValues[(string) $documentId] ?? $submittedValues[$documentId] ?? null;

    if (!is_array($documentValues)) {
        return null;
    }

    $lookupKeys = array_filter([
        (string) ($field['id'] ?? ''),
        (string) ($field['key'] ?? ''),
        (string) ($field['label'] ?? ''),
        Str::slug((string) ($field['key'] ?? ''), '_'),
        Str::slug((string) ($field['label'] ?? ''), '_'),
    ]);

    foreach (array_unique($lookupKeys) as $lookupKey) {
        if (array_key_exists($lookupKey, $documentValues)) {
            return (string) ($documentValues[$lookupKey] ?? '');
        }
    }

    return null;
}

private function resolveFieldSignaturePath(SettingDocument $document, array $field, array $fieldContext): ?string
{
    $paths = data_get($fieldContext, 'field_signature_paths.' . $document->id, []);
    $fieldId = (string) ($field['id'] ?? '');
    $fieldKey = (string) ($field['key'] ?? '');
    $relativePath = $paths[$fieldId] ?? $paths[$fieldKey] ?? $document->signature_path;

    return $relativePath ? storage_path('app/public/' . $relativePath) : null;
}

private function appendSignatureSummaryPage(Fpdi $pdf, SettingDocument $document, string $token, ?string $recipientEmail, string $signerName, $signedAt, array &$tempFiles, string $tempDir)
{
    $signatureAbsolutePath = $document->signature_path
        ? storage_path('app/public/' . $document->signature_path)
        : null;

    $pdf->SetFont('Helvetica', '', 9);

    $signedDate = $signedAt
        ? $signedAt->format('M j, Y | h:i A')
        : now()->format('M j, Y | h:i A');

    $startX = 8;
    $pageHeight = $pdf->GetPageHeight();
    $startY = $pageHeight - 62;

    $boxWidth = $pdf->GetPageWidth() - 16;
    $boxHeight = 52;
    $footerHeight = 10;
    $contentHeight = $boxHeight - $footerHeight;

    $pdf->SetFillColor(255, 255, 255);
    $pdf->SetDrawColor(226, 206, 170);
    $pdf->Rect($startX, $startY, $boxWidth, $boxHeight, 'DF');

    $leftWidth = $boxWidth * 0.46;
    $rightX = $startX + $leftWidth;
    $statusWidth = 28;
    $detailsX = $rightX + 12;

    $pdf->SetDrawColor(189, 197, 209);
    // $pdf->Line($rightX, $startY + 4, $rightX, $startY + $contentHeight - 4);
    // $pdf->Line($startX, $startY + $contentHeight, $startX + $boxWidth, $startY + $contentHeight);

    $pdf->SetFont('Helvetica', 'B', 10);
    $pdf->SetTextColor(18, 27, 38);
    $pdf->SetXY($startX + 6, $startY + 6);
    $pdf->Cell(0, 4, 'Signature', 0, 1);

    if ($signatureAbsolutePath && file_exists($signatureAbsolutePath)) {
        $signatureImagePath = $this->prepareTransparentSignatureForPacket($signatureAbsolutePath, $tempFiles, $tempDir);
        $pdf->Image(
            $signatureImagePath,
            $startX + 8,
            $startY + 13,
            $leftWidth - 18,
            18,
            'PNG'
        );
    }

    $pdf->SetDrawColor(210, 216, 224);
    // $pdf->Line($startX + 8, $startY + 34, $startX + $leftWidth - 12, $startY + 34);

    $pdf->SetFont('Helvetica', 'B', 9);
    $pdf->SetTextColor(88, 99, 113);
    $pdf->SetXY($detailsX + 2, $startY + 5);
    $pdf->Cell(0, 3, 'Name', 0, 1);
    $pdf->SetFont('Helvetica', '', 10);
    $pdf->SetTextColor(18, 27, 38);
    $pdf->SetXY($detailsX + 2, $startY + 9);
    $pdf->Cell(0, 4, utf8_decode($signerName), 0, 1);

    $pdf->SetDrawColor(224, 229, 236);
    // $pdf->Line($detailsX + 2, $startY + 17, $startX + $boxWidth - $statusWidth - 8, $startY + 17);

    $pdf->SetFont('Helvetica', 'B', 9);
    $pdf->SetTextColor(88, 99, 113);
    $pdf->SetXY($detailsX + 2, $startY + 20);
    $pdf->Cell(0, 3, 'Date Signed', 0, 1);
    $pdf->SetFont('Helvetica', '', 10);
    $pdf->SetTextColor(18, 27, 38);
    $pdf->SetXY($detailsX + 2, $startY + 24);
    $pdf->Cell(0, 4, utf8_decode($signedDate), 0, 1);

    // $statusX = $startX + $boxWidth - $statusWidth - 4;
    // $pdf->SetFillColor(246, 248, 251);
    // $pdf->SetDrawColor(246, 248, 251);
    // $pdf->Rect($statusX, $startY + 6, $statusWidth, 11, 'DF');
    // $pdf->SetFillColor(30, 166, 90);
    // $pdf->Rect($statusX + 3, $startY + 8.5, 4, 4, 'F');
    // $pdf->SetFont('Helvetica', '', 7.5);
    // $pdf->SetTextColor(18, 27, 38);
    // $pdf->SetXY($statusX + 9, $startY + 9);
    // $pdf->Cell(0, 3.5, 'Completed', 0, 1);

    // $documentReference = 'Document ID: ' . substr(str_replace('-', '', (string) $token), 0, 28);
    // $pdf->SetFont('Helvetica', '', 6.5);
    // $pdf->SetTextColor(88, 99, 113);
    // $pdf->SetXY($startX + 6, $startY + $contentHeight + 3);
    // $pdf->Cell(0, 3, $documentReference, 0, 1);

    // $pdf->SetFont('Helvetica', 'B', 6.5);
    // $pdf->SetXY($startX + $boxWidth - 34, $startY + $contentHeight + 3);
    // $pdf->Cell(28, 3, 'Powered by DocuSign', 0, 0, 'R');
}



private function prepareTransparentSignatureForPacket(string $signatureAbsolutePath, array &$tempFiles, string $tempDir): string
{
    if (!function_exists('imagecreatefrompng')) {
        return $signatureAbsolutePath;
    }

    $image = @imagecreatefrompng($signatureAbsolutePath);
    if (!$image) {
        return $signatureAbsolutePath;
    }

    $width = imagesx($image);
    $height = imagesy($image);
    $transparentImage = imagecreatetruecolor($width, $height);

    imagealphablending($transparentImage, false);
    imagesavealpha($transparentImage, true);
    $transparent = imagecolorallocatealpha($transparentImage, 255, 255, 255, 127);
    imagefilledrectangle($transparentImage, 0, 0, $width, $height, $transparent);

    for ($x = 0; $x < $width; $x++) {
        for ($y = 0; $y < $height; $y++) {
            $rgba = imagecolorat($image, $x, $y);
            $alpha = ($rgba & 0x7F000000) >> 24;
            $red = ($rgba >> 16) & 0xFF;
            $green = ($rgba >> 8) & 0xFF;
            $blue = $rgba & 0xFF;

            if ($red >= 245 && $green >= 240 && $blue >= 232) {
                continue;
            }

            $color = imagecolorallocatealpha($transparentImage, $red, $green, $blue, $alpha);
            imagesetpixel($transparentImage, $x, $y, $color);
        }
    }

    $transparentPath = $tempDir . '/' . uniqid('signature_transparent_') . '.png';
    imagepng($transparentImage, $transparentPath);
    imagedestroy($image);
    imagedestroy($transparentImage);

    $tempFiles[] = $transparentPath;

    return $transparentPath;
}

private function generateSignedPacket(array $sessionData, $documents, string $token, $signedAt): string
{
    $pdf = new Fpdi();
    $pdf->SetAutoPageBreak(false, 0);

    $tempDir = storage_path('app/temp/signed_packet_' . $token);
    if (!file_exists($tempDir)) {
        mkdir($tempDir, 0755, true);
    }

    $tempFiles = [];
    $recipientEmail = $sessionData['email'] ?? null;
    $signerBase = $recipientEmail ? explode('@', $recipientEmail)[0] : 'customer';
    $signerName = ucwords(trim(str_replace(['.', '_', '-'], ' ', $signerBase)));
    $fieldContext = $this->buildDocumentFieldContext($sessionData, $signedAt);

    try {
        foreach ($documents as $document) {
            $hasSignatureFields = $this->documentHasSignatureFields($document);
            $shouldAppendSignature = $document->signature_required && $document->signature_path && !$hasSignatureFields;
            $this->appendDocumentToSignedPacket($pdf, $document, $tempFiles, $tempDir, $shouldAppendSignature, $fieldContext);

            if ($shouldAppendSignature) {
                $this->appendSignatureSummaryPage($pdf, $document, $token, $recipientEmail, $signerName, $signedAt, $tempFiles, $tempDir);
            }
        }

        if ($pdf->PageNo() === 0) {
            throw new \RuntimeException('No pages were generated for the signed packet.');
        }

        $relativePath = 'signed_packets/' . date('Y/m') . '/signed_packet_' . $token . '.pdf';
        Storage::disk('public')->put($relativePath, $pdf->Output('S'));

        return $relativePath;
    } finally {
        $this->cleanupTempFiles($tempFiles, $tempDir);
    }
}

private function documentHasSignatureFields(SettingDocument $document): bool
{
    return collect($this->normalizeDocumentFields($document->fields ?? []))
        ->contains(function ($field) {
            return ($field['type'] ?? null) === 'signature';
        });
}

private function documentRequiresSignature(SettingDocument $document): bool
{
    return (bool) $document->signature_required || $this->documentHasSignatureFields($document);
}

private function resolveStoredSignedPacket(array $data): ?array
{
    if (empty($data['signed_packet_path'])) {
        return null;
    }

    $absolutePath = storage_path('app/public/' . $data['signed_packet_path']);
    if (!file_exists($absolutePath)) {
        return null;
    }

    return [
        'data' => $data,
        'absolute_path' => $absolutePath,
    ];
}

private function resolveSignedAtForPacket(array $data, $documents)
{
    if (!empty($data['completed_at'])) {
        try {
            return \Illuminate\Support\Carbon::parse($data['completed_at']);
        } catch (\Throwable $exception) {
        }
    }

    $signedAt = $documents->pluck('signed_at')->filter()->first();

    if ($signedAt instanceof \DateTimeInterface) {
        return \Illuminate\Support\Carbon::instance($signedAt);
    }

    if (!empty($signedAt)) {
        try {
            return \Illuminate\Support\Carbon::parse((string) $signedAt);
        } catch (\Throwable $exception) {
        }
    }

    return now();
}

private function storeSignatureDataUrl(?string $signature, string $filename): ?string
{
    if (!is_string($signature) || trim($signature) === '') {
        return null;
    }

    $decodedSignature = base64_decode(
        preg_replace('#^data:image/\w+;base64,#i', '', $signature),
        true
    );

    if ($decodedSignature === false) {
        return null;
    }

    Storage::disk('public')->put($filename, $decodedSignature);

    return $filename;
}


private function resolveSignedPacketForToken(string $token): ?array
{
    $data = $this->getSigningSession($token);

    if (!$data || (empty($data['signed_packet_path']) && empty($data['completed_at']))) {
        return null;
    }

    $storedPacket = $this->resolveStoredSignedPacket($data);
    $documents = $this->resolveSigningDocuments($data);

    if ($documents->isEmpty()) {
        return $storedPacket;
    }

    $hasMissingRequiredSignature = $documents->contains(function ($document) {
        return $this->documentRequiresSignature($document) && empty($document->signature_path);
    });

    if ($hasMissingRequiredSignature) {
        return $storedPacket;
    }

    try {
        $signedPacketPath = $this->generateSignedPacket(
            $data,
            $documents,
            $token,
            $this->resolveSignedAtForPacket($data, $documents)
        );

        $data['signed_packet_path'] = $signedPacketPath;
        $this->putSigningSession($token, $data);

        return $this->resolveStoredSignedPacket($data);
    } catch (\Throwable $exception) {
        return $storedPacket;
    }
}

public function sendDocumentsByEmail(Request $request)
{
    $validator = Validator::make($request->all(), [
        'document_ids'   => 'nullable|required_without:estimate_id|array|min:1',
        'document_ids.*' => 'integer|distinct|exists:setting_documents,id',
        'estimate_id'    => 'integer|exists:estimates,id',
        'estimate_fields' => 'nullable',
        'email'          => 'required|email',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => false,
            'message' => $validator->errors()->first(),
        ], 422);
    }

    $user = auth()->user();
    $documentIds = collect($request->input('document_ids', []))
        ->map(function ($documentId) {
            return (int) $documentId;
        })
        ->values()
        ->all();

    $documents = $this->getOrderedSigningDocuments($documentIds)
        ->where('user_id', $user->id)
        ->where('company_id', $user->company_id)
        ->values();

    if ($documents->count() !== count($documentIds)) {
        return response()->json([
            'status'  => false,
            'message' => 'Some selected documents were not found.'
        ], 404);
    }

    if ($documents->isEmpty() && !$request->filled('estimate_id')) {
        return response()->json([
            'status'  => false,
            'message' => 'No matching documents found.'
        ], 404);
    }

    $estimateDocumentId = null;

    if ($request->filled('estimate_id')) {
        $estimate = Estimate::where('id', $request->estimate_id)
            ->where('company_id', $user->company_id)
            ->first();
            // dd($estimate);

        if ($estimate) {
            $storedFilename = 'estimate_' . $estimate->id . '_' . time() . '.pdf';
            $storedPath = 'setting_documents/generated_estimates/' . date('Y/m') . '/' . $storedFilename;
            Storage::disk('public')->put(
                $storedPath,
                $this->buildEstimatePreviewPdf($estimate, $user, app(EstimateService::class))
            );

            $estimateDocument = SettingDocument::create([
                'user_id' => $user->id,
                'company_id' => $user->company_id,
                'document_type' => 'estimate',
                'file_path' => $storedPath,
                'file_type' => 'pdf',
                'document_name' => 'Estimate ' . ($estimate->key ?: $estimate->id),
                'signature_required' => false,
                'fields' => [],
            ]);
        } else {
            $estimateDocument = SettingDocument::where('id', $request->estimate_id)
                ->where('user_id', $user->id)
                ->where('company_id', $user->company_id)
                ->where('document_type', 'estimate')
                ->first();

            if ($estimateDocument && $request->has('estimate_fields')) {
                $estimateDocument->fields = $this->normalizeDocumentFields($request->input('estimate_fields'));
                $estimateDocument->save();
            }
        }

        if (!$estimateDocument) {
            return response()->json([
                'status'  => false,
                'message' => 'Estimate was not found. Send the estimate id, or an existing generated estimate document id.'
            ], 404);
        }

        // if (!in_array($estimateDocument->id, $documentIds)) {
        //     $documentIds[] = $estimateDocument->id;
        //     $documents->push($estimateDocument);
        // }

        if (!in_array($estimateDocument->id, $documentIds)) {
            array_unshift($documentIds, $estimateDocument->id);  // prepend instead of append
            $documents->prepend($estimateDocument);              // prepend instead of push
        }

        $estimateDocumentId = $estimateDocument->id;
    }

    // Generate a unique signing token
    $token = \Str::uuid();

    // Store token against document ids
    $this->putSigningSession($token, [
        'document_ids' => $documentIds,
        'email'        => $request->email,
        'user_id'      => $user->id,
        'company_id'   => $user->company_id,
        'estimate_id'  => $request->filled('estimate_id') ? (int) $request->estimate_id : null,
    ]);

    $signingLink = url('/document/sign/' . $token);

    Mail::to($request->email)->send(new SettingDocumentsMail($documents, $signingLink));

    return response()->json([
        'status'  => true,
        'message' => 'Documents sent successfully to ' . $request->email,
        'signing_link' => $signingLink,
        // 'document_ids' => $documentIds,
        // 'estimate_document_id' => $estimateDocumentId,
    ]);
}

public function showSignDocument($token)
{
    $data = $this->getSigningSession($token);

    // if (!$data || !isset($data['document_ids'])) {
    //     return view('document.expired');
    // }

    // link expired or invalid

    if (!empty($data['signed_packet_path'])) {
        return view('document.completed', [
            'token' => $token,
            'recipientEmail' => $data['email'] ?? null,
            'viewUrl' => url('/document/sign/' . $token . '/view'),
            'downloadUrl' => url('/document/sign/' . $token . '/download'),
        ]);
    }

    $documents = $this->resolveSigningDocuments($data);

    if ($documents->isEmpty()) {
        return view('document.expired');
    }

    $requiredDocumentIds = $documents
        ->filter(function ($document) {
            return $this->documentRequiresSignature($document);
        })
        ->pluck('id')
        ->map(function ($documentId) {
            return (string) $documentId;
        })
        ->values();

    $requiredSignatureTargets = $documents
        ->filter(function ($document) {
            return $this->documentRequiresSignature($document);
        })
        ->flatMap(function ($document) {
            $signatureFields = collect($this->normalizeDocumentFields($document->fields ?? []))
                ->filter(function ($field) {
                    return ($field['type'] ?? null) === 'signature';
                })
                ->values();

            if ($signatureFields->isEmpty()) {
                return [[
                    'document_id' => (string) $document->id,
                    'field_id' => null,
                ]];
            }

            return $signatureFields->map(function ($field) use ($document) {
                return [
                    'document_id' => (string) $document->id,
                    'field_id' => (string) $field['id'],
                ];
            });
        })
        ->values();

    return view('document.sign', [
        'documents' => $documents,
        'token' => $token,
        'recipientEmail' => $data['email'] ?? null,
        'requiredDocumentIds' => $requiredDocumentIds,
        'requiredSignatureTargets' => $requiredSignatureTargets,
    ]);
}

public function viewSigningSourceDocument($token, SettingDocument $document)
{
    $matchedDocument = $this->resolveSigningSessionDocument($token, $document);

    $absolutePath = $this->resolveDocumentAbsolutePath($matchedDocument);

    if (!$absolutePath) {
        abort(404);
    }

    return $this->streamInlineFile($absolutePath);
}

public function previewSigningDocument($token, SettingDocument $document)
{
    $matchedDocument = $this->resolveSigningSessionDocument($token, $document);
    $absolutePath = $this->resolveDocumentAbsolutePath($matchedDocument);

    if (!$absolutePath) {
        abort(404);
    }

    $fileType = strtolower($matchedDocument->file_type ?: pathinfo($absolutePath, PATHINFO_EXTENSION));

    if ($fileType === 'pdf') {
        return $this->streamInlineFile($absolutePath, 'application/pdf');
    }

    if (in_array($fileType, ['doc', 'docx'])) {
        try {
            $previewPdfPath = $this->resolveWordPreviewPdfPath($matchedDocument, $absolutePath);
            return $this->streamInlineFile($previewPdfPath, 'application/pdf');
        } catch (\Throwable $exception) {
            \Log::error('Unable to build signing preview for document ' . $matchedDocument->id . ': ' . $exception->getMessage());
            abort(404);
        }
    }

    abort(404);
}

public function servePublicStorageFile($path)
{
    $relativePath = ltrim((string) $path, '/');

    if (
        $relativePath === '' ||
        Str::contains($relativePath, ['../', '..\\'])
    ) {
        abort(404);
    }

    $absolutePath = storage_path('app/public/' . $relativePath);

    if (!is_file($absolutePath)) {
        abort(404);
    }

    return $this->streamInlineFile($absolutePath);
}

private function resolveSigningSessionDocument(string $token, SettingDocument $document): SettingDocument
{
    $data = $this->getSigningSession($token);

    if (!$data || !isset($data['document_ids'])) {
        abort(404);
    }

    $matchedDocument = $this->resolveSigningDocuments($data)
        ->firstWhere('id', $document->id);

    if (!$matchedDocument) {
        abort(404);
    }

    return $matchedDocument;
}

private function buildEstimatePreviewPdf(Estimate $estimate, User $user, EstimateService $estimateService): string
{
    $data = $this->buildEstimatePreviewData($estimate, $user, $estimateService);
    $html = $this->renderEstimatePreviewHtmlForPdf($data);

    return Pdf::setOption(['isRemoteEnabled' => false])
        ->loadHTML($html)
        ->setPaper('a4', 'portrait')
        ->output();
}

private function renderEstimatePreviewHtmlForPdf(array $data): string
{
    return view('reports.estimates.signature_preview', $data)->render();
}

private function buildEstimatePreviewData(Estimate $estimate, User $user, EstimateService $estimateService): array
{
    $sheets = $estimate->sheet()
        ->join('codes', 'estimate_sheets.code_id', '=', 'codes.id')
        ->join('product_groups', 'codes.product_group_id', '=', 'product_groups.id')
        ->where('estimate_sheets.quantity', '>', 0)
        ->select('estimate_sheets.*')
        ->orderBy('product_groups.group_order')
        ->with(['code.ProductGroup'])
        ->get();

    $company = $user->company ?: $estimate->company;
    $customer = $estimate->customer;
    $logoAndFont = $this->getCompanyLogoAndFontColor($company, $user, true);


    $detail = $this->calculateEstimatePreviewDetail($sheets, $user);
    $estimateSignature = EstimateSignature::where('estimate_id', $estimate->id)
        ->where('user_id', $user->id)
        ->first();

    return [
        'color' => $logoAndFont['fontColor'],
        'logoPath' => $logoAndFont['logoPath'],
        'company' => $company,
        'estimate' => $estimate,
        'sheets' => $sheets,
        'user' => $user,
        'customer' => $customer,
        'detail' => $detail,
        'grand_total' => $detail['grand_total'],
        'signatureData' => [
            'estimator_signature' => optional($estimateSignature)->estimator_signature,
            'customer_signature' => optional($estimateSignature)->customer_signature,
            'estimator_signed_at' => optional($estimateSignature)->estimator_signed_at,
            'customer_signed_at' => optional($estimateSignature)->customer_signed_at,
        ],
    ];
}

private function calculateEstimatePreviewDetail($sheets, User $user): array
{
    $detail = [];
    $detail['material'] = $sheets->map(function ($sheet) {
        return $sheet->total_material_cost != 0 ? round($sheet->total_material_cost, 2) : null;
    })->filter()->values();

    $detail['labor_cost'] = $sheets->map(function ($sheet) {
        return $sheet->total_labor_cost != 0 ? round($sheet->total_labor_cost, 2) : null;
    })->filter()->values();

    $detail['material_sub_total'] = round(collect($detail['material'])->sum(), 2);
    $detail['labor_sub_total'] = round(collect($detail['labor_cost'])->sum(), 2);

    $settingCollection = Setting::where('company_id', $user->company_id)
        ->where('type', SettingType::SETTING_TYPE_FOR_ESTIMATE)
        ->get();

    $settingMaterialMarkup = $settingCollection->where('slug', 'material_markup')->first();
    $detail['settings']['material_markup'] = round((($settingMaterialMarkup->value ?? 0) / 100) * $detail['material_sub_total'], 2);
    $detail['material_sub_total'] = round($detail['material_sub_total'] + $detail['settings']['material_markup'], 2);

    $settingLaborMarkup = $settingCollection->first(function ($setting) {
        return in_array($setting->slug, ['labour_markup', 'labor_markup']);
    });
    $detail['settings']['labour_markup'] = (($settingLaborMarkup->value ?? 0) / 100) * $detail['labor_sub_total'];
    $detail['labor_sub_total'] = round($detail['labor_sub_total'] + $detail['settings']['labour_markup'], 2);

    $settingTax = $settingCollection->where('slug', 'tax')->first();
    $detail['settings']['tax'] = (($settingTax->value ?? 0) / 100) * $detail['material_sub_total'];
    $detail['material_total'] = round($detail['material_sub_total'] + $detail['settings']['tax'], 2);
    $detail['settings']['tax'] = round($detail['settings']['tax'], 2);
    $detail['labor_total'] = $detail['labor_sub_total'];
    $detail['total'] = round($detail['material_total'] + $detail['labor_total'], 2);
    $detail['grand_total'] = floatval($detail['total']);

    $baseCost = $detail['total'];
    $totalOtherTaxes = 0;
    $detail['settings']['other_tax'] = $settingCollection->whereNotIn('slug', ['material_markup', 'labor_markup', 'labour_markup', 'tax'])
        ->map(function ($setting) use ($baseCost, &$totalOtherTaxes) {
            $settingTax = round(($setting->value / 100) * $baseCost, 2);
            $totalOtherTaxes += $settingTax;
            return number_format($settingTax, 2, '.', '');
        })->values();

    $detail['grand_total'] += $totalOtherTaxes;
    $detail['grand_total'] = number_format(round($detail['grand_total'], 2), 2, '.', ',');

    return $detail;
}

private function getCompanyLogoAndFontColor($company, User $user, bool $forPdf = false): array
{
    $fontColor = '000000';
    $logoPath = asset('assets/img/logo-old.png');
    $logoFilePath = public_path('assets/img/logo-old.png');
    $invoiceSetting = $company ? UserInvoiceSetting::where('company_id', $company->id)->first() : null;

    if ($invoiceSetting) {
        if ($invoiceSetting->logo) {
            $logoPath = asset($invoiceSetting->logo);
            $logoFilePath = public_path(ltrim($invoiceSetting->logo, '/'));
        }
        if ($invoiceSetting->color) {
            $invoiceColor = strtolower(ltrim($invoiceSetting->color, '#'));
            $fontColor = in_array($invoiceColor, ['fff', 'ffffff'], true) ? '000000' : $invoiceColor;
        }
    }

    return [
        'fontColor' => $fontColor,
        'logoPath' => $forPdf ? $this->imageDataUri($logoFilePath, $logoPath) : $logoPath,
    ];
}

private function imageDataUri(?string $filePath, string $fallbackUrl): string
{
    if (!$filePath || !is_file($filePath)) {
        return $fallbackUrl;
    }

    $mimeType = mime_content_type($filePath) ?: 'image/png';
    return 'data:' . $mimeType . ';base64,' . base64_encode(file_get_contents($filePath));
}

private function resolveWordPreviewPdfPath(SettingDocument $document, string $sourcePath): string
{
    $previewDirectory = storage_path('app/temp/signing_previews');

    if (!is_dir($previewDirectory)) {
        mkdir($previewDirectory, 0755, true);
    }

    $lastModified = @filemtime($sourcePath) ?: time();
    $previewPath = $previewDirectory . '/setting_document_' . $document->id . '_' . $lastModified . '.pdf';

    if (!is_file($previewPath)) {
        DocumentConverter::convertWordToPdf($sourcePath, $previewPath);
    }

    return $previewPath;
}

private function streamInlineFile(string $absolutePath, ?string $contentType = null)
{
    $headers = [
        'Content-Disposition' => 'inline; filename="' . basename($absolutePath) . '"',
        'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        'Pragma' => 'no-cache',
        'Expires' => '0',
    ];

    $mimeType = $contentType ?: @mime_content_type($absolutePath);
    if (is_string($mimeType) && $mimeType !== '') {
        $headers['Content-Type'] = $mimeType;
    }

    return response()->file($absolutePath, $headers);
}

public function submitSignature(Request $request, $token)
{
    $request->validate([
        'signatures'   => 'nullable|array',
        'signatures.*' => 'nullable|string',
        'field_signatures' => 'nullable|array',
        'field_values' => 'nullable|array',
    ]);

    $data = $this->getSigningSession($token);

    if (!$data) {
        return response()->json(['status' => false, 'message' => 'Link expired or invalid.'], 404);
    }

    $documents = $this->resolveSigningDocuments($data);

    if ($documents->isEmpty()) {
        return response()->json([
            'status' => false,
            'message' => 'No documents found for this link.',
        ], 404);
    }

    $signatures = $request->input('signatures', []);
    $fieldSignatures = $request->input('field_signatures', []);
    $fieldValues = $this->normalizeSubmittedFieldValues($request->input('field_values', []));
    $requiredDocuments = $documents->filter(function ($document) {
        return $this->documentRequiresSignature($document);
    });

    foreach ($requiredDocuments as $document) {
        $signatureFields = collect($this->normalizeDocumentFields($document->fields ?? []))
            ->filter(function ($field) {
                return ($field['type'] ?? null) === 'signature';
            });

        if ($signatureFields->isNotEmpty()) {
            $documentSignature = data_get($signatures, (string) $document->id, data_get($signatures, $document->id));

            foreach ($signatureFields as $field) {
                $fieldSignature = data_get($fieldSignatures, $document->id . '.' . $field['id'])
                    ?: data_get($fieldSignatures, $document->id . '.' . $field['key'])
                    ?: $documentSignature;

                if (!is_string($fieldSignature) || trim($fieldSignature) === '') {
                    return response()->json([
                        'status' => false,
                        'message' => 'Please add signatures to all required document signature boxes before submitting.',
                    ], 422);
                }
            }

            continue;
        }

        $signature = data_get($signatures, (string) $document->id, data_get($signatures, $document->id));
        if (!is_string($signature) || trim($signature) === '') {
            return response()->json([
                'status' => false,
                'message' => 'Please add signatures to all required documents before submitting.',
            ], 422);
        }
    }

    $signedAt = now();
    $fieldSignaturePaths = [];

    foreach ($documents as $document) {
        $updatePayload = [
            'signed_at' => $signedAt,
        ];

        $documentFieldValues = $fieldValues[(string) $document->id] ?? $fieldValues[$document->id] ?? null;
        if (is_array($documentFieldValues)) {
            $updatePayload['fields'] = $this->mergeSubmittedFieldValuesIntoFields($document, $documentFieldValues);
        }

        if ($this->documentRequiresSignature($document)) {
            $signature = data_get($signatures, (string) $document->id, data_get($signatures, $document->id));
            if (is_string($signature) && trim($signature) !== '') {
                $filename = $this->storeSignatureDataUrl($signature, 'signatures/' . $token . '_' . $document->id . '.png');
                if (!$filename) {
                    return response()->json([
                        'status' => false,
                        'message' => 'One of the signatures could not be processed. Please sign again.',
                    ], 422);
                }
                $updatePayload['signature_path'] = $filename;
            }

            foreach ($this->normalizeDocumentFields($document->fields ?? []) as $field) {
                if (($field['type'] ?? null) !== 'signature') {
                    continue;
                }

                $fieldSignature = data_get($fieldSignatures, $document->id . '.' . $field['id'])
                    ?: data_get($fieldSignatures, $document->id . '.' . $field['key'])
                    ?: $signature;

                if (!is_string($fieldSignature) || trim($fieldSignature) === '') {
                    continue;
                }

                $safeFieldId = preg_replace('/[^A-Za-z0-9_-]/', '_', $field['id']);
                $filename = $this->storeSignatureDataUrl($fieldSignature, 'signatures/' . $token . '_' . $document->id . '_' . $safeFieldId . '.png');
                if (!$filename) {
                    return response()->json([
                        'status' => false,
                        'message' => 'One of the signatures could not be processed. Please sign again.',
                    ], 422);
                }

                $fieldSignaturePaths[$document->id][$field['id']] = $filename;
                $fieldSignaturePaths[$document->id][$field['key']] = $filename;
                $updatePayload['signature_path'] = $updatePayload['signature_path'] ?? $filename;
            }
        }

        $document->update($updatePayload);
    }

    $data['field_signature_paths'] = $fieldSignaturePaths;
    $data['field_values'] = $fieldValues;

    $freshDocuments = $this->resolveSigningDocuments($data);
    $signedPacketPath = $this->generateSignedPacket($data, $freshDocuments, $token, $signedAt);

    $data['signed_packet_path'] = $signedPacketPath;
    $data['completed_at'] = $signedAt->toDateTimeString();
    $this->putSigningSession($token, $data);

    return response()->json([
        'status'  => true,
        'message' => 'Documents signed successfully.',
        'view_url' => url('/document/sign/' . $token . '/view'),
        'download_url' => url('/document/sign/' . $token . '/download'),
    ]);
}

public function viewSignedDocument($token)
{
    $signedPacket = $this->resolveSignedPacketForToken($token);

    if (!$signedPacket) {
        return view('document.expired');
    }

    return response()->file($signedPacket['absolute_path'], [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => 'inline; filename="' . basename($signedPacket['absolute_path']) . '"',
    ]);
}

public function downloadSignedDocument($token)
{
    $signedPacket = $this->resolveSignedPacketForToken($token);

    if (!$signedPacket) {
        return view('document.expired');
    }

    return response()->download($signedPacket['absolute_path'], basename($signedPacket['absolute_path']), [
        'Content-Type' => 'application/pdf',
    ]);
}


private function mergeSubmittedFieldValuesIntoFields(SettingDocument $document, array $documentFieldValues): array
{
    return collect($this->normalizeDocumentFields($document->fields ?? []))
        ->map(function ($field) use ($documentFieldValues) {
            if (($field['type'] ?? null) === 'signature') {
                return $field;
            }

            $lookupKeys = array_filter([
                (string) ($field['id'] ?? ''),
                (string) ($field['key'] ?? ''),
                (string) ($field['label'] ?? ''),
                Str::slug((string) ($field['key'] ?? ''), '_'),
                Str::slug((string) ($field['label'] ?? ''), '_'),
            ]);

            foreach (array_unique($lookupKeys) as $lookupKey) {
                if (array_key_exists($lookupKey, $documentFieldValues)) {
                    $field['value'] = (string) ($documentFieldValues[$lookupKey] ?? '');
                    break;
                }
            }

            return $field;
        })
        ->values()
        ->all();
}

public function update_setting_documents(Request $request)
{
    if (!auth()->check()) {
        return response()->json([
            'status'  => false,
            'message' => 'Unauthenticated'
        ], 401);
    }

    $validator = Validator::make($request->all(), [
        'id'     => 'required|integer|exists:setting_documents,id',
        'file'   => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:20480',
        'fields' => 'nullable',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status'  => false,
            'message' => $validator->errors()->first()
        ], 422);
    }

    $user = auth()->user();

    $document = SettingDocument::where('id', $request->input('id'))
        ->where('user_id', $user->id)
        ->where('company_id', $user->company_id)
        ->first();

    if (!$document) {
        return response()->json([
            'status' => false,
            'message' => 'Document not found'
        ], 404);
    }

    if ($request->hasFile('file')) {
        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());

        if (!$this->isAllowedSettingDocumentExtension($extension)) {
            return response()->json([
                'status'  => false,
                'message' => 'Only PDF, DOC, DOCX, PNG, JPG and JPEG files are allowed.'
            ], 422);
        }

        if ($document->file_path && Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
        }

        $filename = time() . '_updated_' . Str::random(10) . '.' . $extension;
        $path = $file->storeAs('setting_documents', $filename, 'public');

        $document->file_path = $path;
        $document->file_type = $extension;
    }

    $document->document_name = $request->name ?? $document->document_name;
    $document->signature_required = $request->sign_required ?? $document->signature_required;
    if ($request->has('fields')) {
        $document->fields = $this->normalizeDocumentFields($request->input('fields'));
    }

    $document->save();

    return response()->json([
        'status'  => true,
        'message' => 'Document updated successfully',
        'data'    => $document
    ]);
}

}
