<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Helpers\Helper;
use App\Models\Estimate;
use App\Models\EstimateSheet;
use App\Models\Membership;
use App\Models\Template;
use App\Models\User;
use App\Models\Code;
use App\Models\UserInvoiceSetting;
use App\Models\UserSetting;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Notifications\FirebasePushNotification;
use App\Services\FirebaseService;
use Illuminate\Validation\Rule;
use App\Rules\UniqueWithSoftDelete;
use App\Models\Device;
use App\Models\AppUpdate;
use Illuminate\Support\Facades\Auth;
use App\Models\ModulePermission;
use App\Models\UserModulePermission;

class UserController extends ApiBaseController
{
   public function user_dashboard(Request $request): JsonResponse
    {
        
        $currentUser = $request->user(); // Get the current user
    
        if ($currentUser->hasFullModuleAccess()) {
           
            // Determine which user's subscriptions to fetch
            $currentUser = $currentUser->is_admin ? $currentUser : User::find($currentUser->parent_id);
    
        } 
        
        $userEstimates = [];
        
        if ($currentUser->is_admin == 1) {
            // If the user is an admin, get IDs of all their child users
            $childUserIds = User::where('parent_id', $currentUser->id)->pluck('id')->toArray();
            
            // Include both the current user and child users in the estimates query
            $userIds = array_merge([$currentUser->id], $childUserIds);
            $userEstimates = Estimate::whereIn("user_id", $userIds)->get();
        } else {
            // For non-admin users, show only their estimates
            $userEstimates = Estimate::where("user_id", "=", $currentUser->id)->get();
        }
        
        
        $userTemplates = Template::where("user_id", "=", $currentUser->id)->get();
        
        if ($currentUser->is_admin == 0) 
        {
            $userTemplates = Template::where("user_id", "=", $currentUser->parent_id)->get();
        }
       
       $Codes = Code::withoutTrashed()->where("user_id", "=", $currentUser->company_id)->get();
       
        
        $invoiceSetting = UserInvoiceSetting::where("user_id", "=", $currentUser->id)->first();
        $globalSetting = UserSetting::select("tax", "material_cost", "labor_cost")
            ->where("user_id", "=", $currentUser->id)->first();
    
        $this->response_data["status"] = true;
        $this->response_data["data"] = [
            "estimate" => count($userEstimates),
            "invoice" => count($Codes),
            "template" => count($userTemplates),
            "invoice_setting" => (empty($invoiceSetting)) ? [] : ["color" => $invoiceSetting->color, "logo" => $invoiceSetting->full_logo],
            "global_setting" => (empty($globalSetting)) ? [] : $globalSetting
        ];
    
        return $this->sendJsonResponse();
    }
    
    
    public function getPermissionsModules(Request $request)
{
    try {
        $userId = $request->input('user_id');
        $defaultEnabledIds = [4, 5, 6, 11]; // Default modules enabled for everyone

        // Get module IDs already assigned to the user
        $userPermissions = [];

        if ($userId) {
            $userPermissions = UserModulePermission::where('user_id', $userId)
                ->pluck('module_permission_id')
                ->toArray();
        }

        $modules = ModulePermission::select('id', 'name', 'slug', 'sort_order')
            ->orderBy('sort_order')
            ->get()
            ->map(function ($module) use ($userId, $userPermissions, $defaultEnabledIds) {
                $hasPermission = false;

                if ($userId) {
                    $hasPermission = in_array($module->id, $userPermissions) || in_array($module->id, $defaultEnabledIds);
                } else {
                    $hasPermission = in_array($module->id, $defaultEnabledIds);
                }
                

                return [
                    'id' => $module->id,
                    'name' => $module->name,
                    'slug' => $module->slug,
                    'sort_order' => $module->sort_order,
                    'permission' => $hasPermission,
                    'is_default' => in_array($module->id, $defaultEnabledIds), // only for sorting
                ];
            })
            ->sortBy(function ($module) use ($defaultEnabledIds) {
                if ($module['is_default']) {
                    // Fixed order from defaultEnabledIds
                    return array_search($module['id'], $defaultEnabledIds);
                }
                // Others come after defaults, sorted by sort_order
                return 9999 + $module['sort_order'];
            })
            ->map(function ($module) {
                // remove is_default from final response
                unset($module['is_default']);
                return $module;
            })
            ->values();

        return response()->json([
            'status' => true,
            'message' => 'Modules fetched successfully.',
            'data' => $modules,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'Error fetching module permissions: ' . $e->getMessage(),
        ], 500);
    }
}



    public function profile(Request $request):JsonResponse
    {
        $user = User::with('company','userAddress')->find(auth()->id());
        $flattenedArray = $user->toFlattenedArray();
        $this->response_data["data"] = ["profile"=>$flattenedArray];
        $this->response_data["status"] = true;
        return $this->sendJsonResponse();
    }
    
    public function deleteMyAccount(Request $request)
    {
        $user = Auth::user();
   
        if ($user) {
            $user->delete(); // this will soft delete the user
            $this->response_data["status"] = true;
            return $this->sendJsonResponse();
            return response()->json(['message' => 'User soft deleted successfully.']);
        }
        
        $this->response_data["message"] = "'User not found.";
        return $this->sendJsonResponse();
                    
    }


    public function update(Request $request):JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'phone' => [
                'required',
                'string',
                'regex:/^\(\d{3}\) \d{3}-\d{4}$/', // Ensures the format matches (675) 975-9759
                // Rule::unique('users', 'phone')->ignore($request->user()->id), // Ensures uniqueness except for the current user
            ],
            'company_name' => 'required|string',
            'company_address' => 'required|string',
            'license_no' => 'nullable',
            'city' => 'required',
            'state_id' => 'required',
            'zip_code' => 'required',
            'job_type' => 'nullable',
            'sign' => 'nullable',
        ]);
        if ($validator->fails()){
            foreach ($validator->errors()->all() as $msg) {
                if (strlen(trim($msg)) > 1){
                    $this->response_data["message"] = $msg;
                    return $this->sendJsonResponse();
                }
            }
        }
        $user = User::where("id", "=", $request->user()->id)->first();
        $user->first_name = $request->input("first_name");
        $user->last_name = $request->input("last_name");
        $user->phone = $request->input("phone");
        $user->job_type = $request->input("job_type");
        if ($request->hasFile("sign")) {
            $signValidator = Validator::make($request->all(), [
                'sign' => 'image|mimes:jpeg,jpg,png,webp|max:5120',
            ]);
            if ($signValidator->fails()) {
                foreach ($signValidator->errors()->all() as $msg) {
                    if (strlen(trim($msg)) > 1) {
                        $this->response_data["message"] = $msg;
                        return $this->sendJsonResponse();
                    }
                }
            }

            $sign = $request->file("sign");
            $storedSign = $sign->storePubliclyAs("user/signatures", $sign->hashName(), "public");
            $user->sign = "storage/" . $storedSign;
        } elseif ($request->has("sign")) {
            $user->sign = $this->storeSignatureDataUrl($request->input("sign")) ?? $request->input("sign");
        }
        $user->update();
        
        $user->company()->updateOrCreate([
           "user_id" =>  $user->id
        ],[
            "name" => $request->input("company_name"),
            "address" => $request->input("company_address"),
            "license_no" => $request->input("license_no")
        ]);
        
       
        $user->userAddress()->updateOrCreate(
            ['user_id' => $user->id], // Match on user_id (or any other unique key)
            [
                'city' => $request->city,
                'state_id' => $request->state_id,
                'zip_code' => $request->zip_code,
            ]
        );
        
        $this->response_data["status"] = true;
        $this->response_data["message"] = "Profile update successfully.";
        return  $this->sendJsonResponse();
    }

    private function storeSignatureDataUrl($signature)
    {
        if (!is_string($signature) || strpos($signature, 'data:image/') !== 0) {
            return null;
        }

        if (!preg_match('/^data:image\/(png|jpeg|jpg|webp);base64,(.+)$/i', $signature, $matches)) {
            return null;
        }

        $extension = strtolower($matches[1]) === 'jpeg' ? 'jpg' : strtolower($matches[1]);
        $imageData = base64_decode($matches[2], true);
        if ($imageData === false) {
            return null;
        }

        $path = 'user/signatures/' . Str::random(40) . '.' . $extension;
        Storage::disk('public')->put($path, $imageData);

        return 'storage/' . $path;
    }

    public function invoice_setting(Request $request):JsonResponse
    {

        if(!auth()->user()->hasSubscription('invoice')) {
            $this->response_data["message"] = "Please upgrade your subscription";
            return  $this->sendJsonResponse();
        }
        $validator = Validator::make($request->all(), [
            'color' => 'required|string',
            'logo' => 'nullable',
        ]);
        if ($validator->fails()){
            foreach ($validator->errors()->all() as $msg) {
                if (strlen(trim($msg)) > 1){
                    $this->response_data["message"] = $msg;
                    return $this->sendJsonResponse();
                }
            }
        }
        
        $is = null;
        $currentUser = $request->user();
        if ($currentUser->is_admin) {
            
            // Fetch the invoice settings for the current admin user
            $is = UserInvoiceSetting::where("user_id", $currentUser->id)->first();
        } else {
           
            // Fetch the invoice settings for the parent user
            $is = UserInvoiceSetting::where("user_id", $currentUser->parent_id)->first();
        }
    
        
        if ($is === null){
            $invoiceSetting = new UserInvoiceSetting();
            $invoiceSetting->color = $request->input("color");
            if ($request->hasFile('logo')) {
                $invoiceSetting->logo = Helper::file_upload($request,'logo','user');
            }
            $invoiceSetting->user_id = $request->user()->id;
            $invoiceSetting->company_id = auth()->user()->company_id;
            $invoiceSetting->save();
            $this->response_data["data"] = ["invoice_setting"=>["color"=>$invoiceSetting->color, "logo"=>$invoiceSetting->full_logo]];
            $this->response_data["message"] = "Invoice setting save successfully.";
        }else{
            $update = [
                "color" => $request->input("color")
            ];
            if ($request->hasFile('logo')) {
                $update["logo"] = Helper::file_upload($request,'logo','user');
            }
            $this->response_data["message"] = "Invoice setting update successfully.";
            $is->update($update);
            $this->response_data["data"] = ["invoice_setting"=>["color"=>$is->color, "logo"=>$is->full_logo]];
        }
        $this->response_data["status"] = true;

        return  $this->sendJsonResponse();
    }

    public function global_setting(Request $request):JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'tax' => 'required|numeric|min:0',
            'material_cost' => 'required|numeric|min:0',
            'labor_cost' => 'required|numeric|min:0',
        ]);
        if ($validator->fails()){
            foreach ($validator->errors()->all() as $msg) {
                if (strlen(trim($msg)) > 1){
                    $this->response_data["message"] = $msg;
                    return $this->sendJsonResponse();
                }
            }
        }
        $us = UserSetting::where("user_id", "=", $request->user()->id)->first();

        if ($us === null){
            $globalSetting = new UserSetting();
            $globalSetting->tax = $request->input("tax");
            $globalSetting->labor_cost = $request->input("labor_cost");
            $globalSetting->material_cost = $request->input("material_cost");
            $globalSetting->user_id = $request->user()->id;
            $globalSetting->save();
            $this->response_data["data"] = ["global_setting"=>["tax"=>$globalSetting->tax, "labor_cost"=>$globalSetting->labor_cost, "material_cost"=>$globalSetting->material_cost]];
            $this->response_data["message"] = "Global setting save successfully.";
        }else{
            $us->tax = $request->input("tax");
            $us->labor_cost = $request->input("labor_cost");
            $us->material_cost = $request->input("material_cost");
            $us->update();
            $this->response_data["data"] = ["global_setting"=>["tax"=>$us->tax, "labor_cost"=>$us->labor_cost, "material_cost"=>$us->material_cost]];
            $this->response_data["message"] = "Global setting update successfully.";
        }

        $this->response_data["status"] = true;
        return $this->sendJsonResponse();
    }

    // public function device_update(Request $request):JsonResponse
    // {
    //     $validator = Validator::make($request->all(), [
    //         'device_token' => 'required|string'
    //     ]);
    //     if ($validator->fails()){
    //         foreach ($validator->errors()->all() as $msg) {
    //             if (strlen(trim($msg)) > 1){
    //                 $this->response_data["message"] = $msg;
    //                 return $this->sendJsonResponse();
    //             }
    //         }
    //     }
    //     $user = User::where("id", "=", $request->user()->id)->first();
    //     $user->device_token = $request->input("device_token");
    //     $user->update();
    //     $this->response_data["status"] = true;
    //     $this->response_data["message"] = "User device token update successfully.";
    //     return $this->sendJsonResponse();
    // }
    
    
   public function device_update(Request $request): JsonResponse
{
    // Validate the request inputs
    $validator = Validator::make($request->all(), [
        'device_token' => 'required|string', // Unique token per device
        'device_type' => 'required|string|in:android,ios', // Must be android or ios
        'device_info' => 'nullable|string', // Optional device details
    ]);

    if ($validator->fails()) {
        foreach ($validator->errors()->all() as $msg) {
            if (strlen(trim($msg)) > 1) {
                $this->response_data["message"] = $msg;
                return $this->sendJsonResponse();
            }
        }
    }

    // Get the currently authenticated user
    $user = $request->user();

    try {
        // Step 1: Check if the device token exists in the database, including soft-deleted records
        $existingDevice = Device::where('device_token', $request->device_token)
            ->withTrashed() // Include soft-deleted records
            ->first();

        if ($existingDevice) {
            if ($existingDevice->trashed()) {
                // If the device token is soft-deleted, restore it
                $existingDevice->restore();
            }

            // Step 2: If the device token belongs to another user, unbind it by updating the deviceable_id to the current user
            if ($existingDevice->deviceable_id != $user->id) {
                // Instead of setting the deviceable_id to null, set it to the current user
                $existingDevice->deviceable_id = $user->id; // Associate it with the current user
                $existingDevice->save(); // Save the change
            }
        }

        // Step 3: Now bind the device token to the new user
        $userDevice = $user->devices()->where('device_token', $request->device_token)->first();

        if ($userDevice) {
            // Update the existing device info if needed
            $userDevice->update([
                'device_type' => $request->device_type,
                'device_info' => $request->device_info ?? $userDevice->device_info,
            ]);
        } else {
            // If the device token doesn't exist for the current user, create a new device record
            $user->devices()->create([
                'device_token' => $request->device_token,
                'device_type' => $request->device_type,
                'device_info' => $request->device_info,
            ]);
        }

        // Response
        $this->response_data["status"] = true;
        $this->response_data["message"] = "Device updated successfully.";
    } catch (\Illuminate\Database\QueryException $e) {
        // Handle database query exceptions
        $this->response_data["status"] = false;
        $this->response_data["message"] = "Failed to update device: " . $e->getMessage();
    } catch (\Exception $e) {
        // Handle any other exceptions
        $this->response_data["status"] = false;
        $this->response_data["message"] = "An unexpected error occurred: " . $e->getMessage();
    }

    return $this->sendJsonResponse();
}



  public function companyUser()
{
    $authUser = auth()->user();
    $company = $authUser->company;

    $this->response_data["status"] = true;

    // Default enabled module IDs
    $defaultEnabledIds = [4, 5, 6, 11];

    // All available module IDs
    $allModuleIds = \App\Models\ModulePermission::pluck('id')->toArray();

    // User�s assigned permissions
    $userPermissionIds = \App\Models\UserModulePermission::where('user_id', $authUser->id)
        ->pluck('module_permission_id')
        ->toArray();

    // Combine with default
    $combinedPermissions = array_unique(array_merge($userPermissionIds, $defaultEnabledIds));

    // Check if user has all permissions
    $hasAllPermissions = empty(array_diff($allModuleIds, $combinedPermissions));

    // Treat as admin if they have all permissions or no parent
    $isAdmin = ($authUser->parent_id == 0) || $hasAllPermissions;

    // Base query (exclude self)
    $query = $company->users()->where('id', '!=', $authUser->id);

    if ($isAdmin) {
        // Admins (real or virtual) see all company users
        $users = $query->get();
    } else {
        // Regular users see users under same parent
        $users = $query->where('parent_id', $authUser->parent_id)->get();
    }

    // Prepare user list
    $this->response_data["data"] = $users->map(function ($user) {
        return [
            'id' => $user->id,
            'email' => $user->email,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'raw_password' => $user->raw_password,
            'position' => $user->position,
            'phone' => $user->phone,
            'is_admin' => ($user->parent_id == 0) ? 1 : 0,
            'multi_user_access' => $user->hasSubscription('multi_user_access'),
        ];
    });

    // Include current user info
    $this->response_data["current_user"] = [
        'id' => $authUser->id,
        'email' => $authUser->email,
        'first_name' => $authUser->first_name,
        'last_name' => $authUser->last_name,
        'is_admin' => $isAdmin ? 1 : 0,
        'parent_id' => $authUser->parent_id,
    ];

    return $this->sendJsonResponse();
}





    public function editCompanyUser(User $user){
        $company = auth()->user()->company;
        $user = User::where("is_admin",0)
            ->where("company_id",$company->id)
            ->first();
        if($user){
            $this->response_data["status"] = true;
            $this->response_data["data"] = $user;
        }else{
            $this->response_data["message"] = "User Not Found";
        }

        return $this->sendJsonResponse();
    }


    
    public function createCompanyUser(Request $request)
    {
        try {
            $company = auth()->user()->company;
    
            // Check subscription
            $hasSub = auth()->user()->hasSubscription(Membership::SUBSCRIPTION_MULTI_USER_ACCESS);
            if (!$hasSub) {
                $this->response_data["message"] = "Please upgrade your membership.";
                $this->response_data["no_subscription"] = true;
                return $this->sendJsonResponse();
            }
    
            // Validation
            $validator = Validator::make($request->all(), [
                'first_name' => 'required|string',
                'last_name' => 'required|string',
                'phone' => [
                    'required',
                    'string',
                    new UniqueWithSoftDelete('users', 'phone', $request->user()->id),
                ],
                'position' => 'required|string',
                'email' => [
                    'required',
                    'email',
                    new UniqueWithSoftDelete('users', 'email', $request->user()->id),
                ],
                'password' => [
                    'required',
                    'string',
                    'min:8',
                    'regex:/[a-z]/',
                    'regex:/[A-Z]/',
                    'regex:/[0-9]/',
                    'regex:/[@$!%*#?&]/',
                ],
                'permissions' => 'nullable|array',
                'permissions.*' => 'integer|exists:module_permissions,id',
            ]);
    
            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $msg) {
                    if (strlen(trim($msg)) > 1) {
                        $this->response_data["message"] = $msg;
                        return $this->sendJsonResponse();
                    }
                }
            }
    
            // Create user
            $user = User::create([
                "parent_id" => auth()->user()->id,
                "first_name" => $request->input("first_name"),
                "last_name" => $request->input("last_name"),
                "name" => $request->input("first_name") . " " . $request->input("last_name"),
                "phone" => $request->input("phone"),
                "email" => $request->input("email"),
                "password" => Hash::make($request->input("password")),
                "raw_password" => base64_encode($request->input("password")),
                "type" => "APP-USER",
                "job_type" => 1,
                "position" => $request->input("position"),
                "status" => 1,
                "is_admin" => 0,
                "company_id" => $company->id,
                "email_verified_at" => Carbon::now(),
            ]);
    
            // Assign permissions using model
            if (auth()->user()->is_admin && $request->has('permissions')) {
                $permissionIds = $request->input('permissions'); // array of IDs
            
                // Sync permissions to ensure insert or update
                $user->modulePermissions()->sync($permissionIds);
            }
    
            // Notify admin
            $this->response_data["status"] = true;
            $this->response_data["data"] = $user;
    
            $title = 'New User Added';
            $body = $request->input("first_name") . " has been successfully added to your team.";
            auth()->user()->notify(new FirebasePushNotification($title, $body));
    
            return $this->sendJsonResponse();
    
        } catch (\Exception $e) {
            $this->response_data["status"] = false;
            $this->response_data["message"] = $e->getMessage();
            return $this->sendJsonResponse();
        }
    }



   public function updateCompanyUser(User $user, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'phone' => [
                'required',
                'string',
                Rule::unique('users', 'phone')->ignore($user->id)->whereNull('deleted_at'),
            ],
            'position' => 'required|string',
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($user->id)->whereNull('deleted_at'),
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'regex:/[a-z]/',
                'regex:/[A-Z]/',
                'regex:/[0-9]/',
                'regex:/[@$!%*#?&]/',
            ],
            'permissions' => 'nullable|array',
            'permissions.*' => 'integer|exists:module_permissions,id',
        ]);
    
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $msg) {
                if (strlen(trim($msg)) > 1) {
                    $this->response_data["message"] = $msg;
                    return $this->sendJsonResponse();
                }
            }
        }
    
        $company = auth()->user()->company;
    
        $user->update([
            "first_name" => $request->input("first_name"),
            "last_name" => $request->input("last_name"),
            "name" => $request->input("first_name") . " " . $request->input("last_name"),
            "phone" => $request->input("phone"),
            "email" => $request->input("email"),
            "password" => Hash::make($request->input("password")),
            "raw_password" => base64_encode($request->input("password")),
            "position" => $request->input("position"),
            "company_id" => $company->id
        ]);
    
        // ✅ Sync permissions if provided
        if ($request->has('permissions') && is_array($request->permissions)) {
            $user->modulePermissions()->sync($request->permissions); // update or insert
        }
    
        $this->response_data["status"] = true;
        $this->response_data["data"] = $user->refresh();
        return $this->sendJsonResponse();
    }

    public function deleteCompanyUser(User $user){
        
        try{
            
        $authUser = auth()->user();
        if($user->company_id != $authUser->company_id || $user->is_admin == 1){
            $this->response_data["message"] = "Unauthorized Action";
        }
        else{
            $tempuser = $user;
            // $this->syncAllModuleWithAdminID($user->id,$authUser->id);
            $processed = $user->delete();
            if($processed){
                $this->response_data["status"] = true;
                $this->response_data["message"] = "Delete Successfully";
                
                
                $title = 'User Removed';
                $body = $tempuser->first_name." has been removed from your team on EZ Estimater.";
                $authUser->notify(new FirebasePushNotification($title, $body));
        
        
            }else{
                $this->response_data["message"] = "Unable to delete";
            }
        }
        
    }catch (\Exception $e){
                $this->response_data["status"] = false;
                $this->response_data["message"] = "Something went wrong";
                $this->response_data["data"] = $e->getMessage();
            }
        return $this->sendJsonResponse();
    }

    public function syncAllModuleWithAdminID($user_id,$admin_id){
        Estimate::where("user_id",$user_id)->update([
            "user_id" => $admin_id
        ]);
        EstimateSheet::where("user_id",$user_id)->update([
            "user_id" => $admin_id
        ]);
    }

    public function stripeDetail()
    {
        $this->response_data["status"] = true;
        $this->response_data["data"] = [
            "key" => env('STRIPE_KEY'),
            "secret" => env('STRIPE_SECRET'),
        ];
        return $this->sendJsonResponse();
    }
    
   public function getLatestUpdate(Request $request)
    {
        $request->validate([
            'update_type' => 'required|in:android,ios',   // Required platform type
        ]);
    
        // Latest app update fetch from database
        $update = AppUpdate::where('update_type', $request->update_type)
            ->latest()
            ->first();
    
        if (!$update) {
            $this->response_data["status"] = false;
            $this->response_data["message"] = "No update information available.";
            $this->response_data["data"] = [];
    
            return $this->sendJsonResponse();
        }
    
        // Set default update URL based on platform type
        $updateUrl = "https://play.google.com/store/apps/details?id=com.probuilds.ezestimater&hl=en_US";
        if ($request->update_type === 'ios') {
            $updateUrl = "https://apps.apple.com/us/app/ez-estimater/id1536314702";
        }
    
        // Populate the response data in your required format
        $version = $update->version;
    
        $this->response_data["status"] = true;
        $this->response_data["message"] = "Latest App Updated Version";
        $this->response_data["data"] = [
            "title" => "Update Available!",
            "body" => "Version {$version} is now available. Please update your app.",
            "type" => "app_update",
            "latest_version" => $version,
            "force_update" => (bool)$update->force_update,
            "update_url" => $updateUrl
        ];
    
        return $this->sendJsonResponse();
    }



}
