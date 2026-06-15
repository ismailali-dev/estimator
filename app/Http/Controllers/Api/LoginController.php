<?php

namespace App\Http\Controllers\Api;

use App\Helpers\Helper;
use App\Models\Setting;
use App\Models\SettingType;
use App\Models\User;
use App\Models\UserSetting;
use App\Models\UserInvoiceSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Notifications\NewContractorNotification;
use Illuminate\Validation\Rule;
use App\Notifications\FirebasePushNotification;
use App\Services\FirebaseService;
use App\Models\Membership;



class LoginController extends ResponseController
{

    public function __construct(){
        $this->middleware('guest:app-api')->except('logout');
    }



public function login(Request $request): JsonResponse
{
    $validator = Validator::make($request->all(), [
        'email' => 'required|email',
        'password' => 'required|string',
        'device_id' => 'required|string',
    ]);

    if ($validator->fails()) {
        foreach ($validator->errors()->all() as $msg) {
            if (strlen(trim($msg)) > 1) {
                $this->response_data["message"] = $msg;
                return $this->sendJsonResponse();
            }
        }
    }

   // Fetch user and ensure not soft-deleted
    $user = User::where('email', $request->email)
        ->whereNull('deleted_at')
        ->first();
    
    if (!$user) {
        
        $this->response_data["message"] = "Invalid email or password.";
        return $this->sendJsonResponse();
    }
    
    // Check if user has a parent and if the parent is soft-deleted
    if ($user->parent_id) {
        $parent = User::withTrashed()->find($user->parent_id);
        if ($parent && $parent->trashed()) {
             $this->response_data["data"]['is_account_deleted'] = true ;
            $this->response_data["message"] = "Your admin account is not active. Please contact your admin.";
            return $this->sendJsonResponse();
        }
    }

    // Show basic user info early (if needed for conditional logic)
    $this->response_data["data"] = [
        "user" => [
            "user_id" => $user->id,
            "email" => $user->email,
        ],
    ];

    // Check email verification and status
    if (is_null($user->email_verified_at)) {
        $this->response_data["message"] = "Email verification required.";
        return $this->sendJsonResponse();
    } elseif ($user->status == 0) {
        $this->response_data["message"] = "Password isn't set yet.";
        $this->response_data["set_password"] = false;
        return $this->sendJsonResponse();
    }

    // Clear previous response data
    $this->response_data["data"] = [];

    // Check password manually instead of Auth::attempt
    if (!Hash::check($request->password, $user->password)) {
        $this->response_data["message"] = "Invalid email or password.";
        return $this->sendJsonResponse();
    }

    // Log in user manually
    Auth::login($user);

    $authUser = Auth::user();

    // Check for temp email
    if (str_contains($authUser->email, "@email.tmp")) {
        $this->response_data["message"] = "Please provide email.";
        $this->response_data["data"] = [
            "user" => [
                "user_id" => $authUser->id,
                "first_name" => $authUser->first_name,
                "last_name" => $authUser->last_name,
                "phone" => $authUser->phone
            ],
            "step" => 1
        ];
        return $this->sendJsonResponse();
    }

    // Check account status
    if (!$authUser->status) {
        $this->response_data["message"] = "Account status disabled.";
        return $this->sendJsonResponse();
    }

    // Check subscription if not admin
    // if (!$authUser->is_admin) {
    //     if (!$authUser->hasSubscription('multi_user_access')) {
    //         $this->response_data["message"] = "You do not have access to this module.";
    //         return $this->sendJsonResponse();
    //     }
    // }

    // Token generation using device_id
    $device_id = $request->device_id;
    $tokenName = 'Device-' . $device_id;

    // Revoke previous token with this device name
    $authUser->tokens()->where('name', $tokenName)->delete();

    // Create new token
    $token = $authUser->createToken($tokenName)->plainTextToken;

    // Fetch user settings
    $globalSetting = UserSetting::where("user_id", $authUser->id)->first();
    $invoiceSetting = $authUser->is_admin
        ? UserInvoiceSetting::where("user_id", $authUser->id)->first()
        : UserInvoiceSetting::where("user_id", $authUser->parent_id)->first();

    // Response
    $this->response_data["status"] = true;
    $this->response_data["token"] = $token;
    $this->response_data["data"] = [
        "user" => $authUser,
        "invoice_setting" => $invoiceSetting ? [
            "color" => $invoiceSetting->color,
            "logo" => $invoiceSetting->full_logo
        ] : null,
        "global_setting" => $globalSetting ? [
            "tax" => $globalSetting->tax,
            "labor_cost" => $globalSetting->labor_cost,
            "material_cost" => $globalSetting->material_cost
        ] : null,
        "email_verified" => !is_null($authUser->email_verified_at)
    ];

    return $this->sendJsonResponse();
}



//     public function login(Request $request): JsonResponse
//     {
//         $validator = Validator::make($request->all(), [
//             'email' => 'required|email',
//             'password' => 'required|string',
//             'device_id' => 'required|string',  // Require device_id
//         ]);

//         if ($validator->fails()) {
//             foreach ($validator->errors()->all() as $msg) {
//                 if (strlen(trim($msg)) > 1) {
//                     $this->response_data["message"] = $msg;
//                     return $this->sendJsonResponse();
//                 }
//             }
//         }
        
        
     

//     // Check if the user exists
//     if ($user = User::where('email', $request->email)->whereNull('deleted_at')->first()) {
//         $this->response_data["data"] = [
//             "user" => [
//                 "user_id" => $user->id,
//                 "email" => $user->email,
//             ],
//         ];

//         // Check email verification and account status
//         if (is_null($user->email_verified_at)) {
//             $this->response_data["message"] = "Email verification required.";
//             return $this->sendJsonResponse();
//         } elseif ($user->status == 0) {
//             $this->response_data["message"] = "Password isn't set yet.";
//             $this->response_data["set_password"] = false;
//             return $this->sendJsonResponse();
//         }
        
//         // Clear previous response data
//         $this->response_data["data"] = [];
//     }
   
//     if(!$user){
//             $this->response_data["message"] = "Your account is not active, please contact with our support team";
//             $this->response_data["set_password"] = false;
//             return $this->sendJsonResponse();
//     }

  
    
//     // Attempt login with Auth
//     if (Auth::attempt(['email' => $request->email, 'password' => $request->password, 'type' => 3])) {
//         $authUser = Auth::user();

//         // Check for temp email address
//         if (str_contains($authUser->email, "@email.tmp")) {
//             $this->response_data["message"] = "Please provide email.";
//             $this->response_data["data"] = [
//                 "user" => [
//                     "user_id" => $authUser->id,
//                     "first_name" => $authUser->first_name,
//                     "last_name" => $authUser->last_name,
//                     "phone" => $authUser->phone
//                 ],
//                 "step" => 1
//             ];
//             return $this->sendJsonResponse();
//         }

//         // Check account status
//         if (!$authUser->status) {
//             $this->response_data["message"] = "Account status disabled.";
//             return $this->sendJsonResponse();
//         }

//         // Subscription check
//         if (!$authUser->is_admin) {
//             // If not an admin, check the subscription for 'multi_user_access'
//             if (!$authUser->hasSubscription('multi_user_access')) {
//                 $this->response_data["message"] = "You do not have access to this module.";
//                 return $this->sendJsonResponse();
//             }
//         }

//         // // Use device_id to create/reuse token
//         // $device_id = $request->device_id;
//         // // $tokenName = 'Device-' . $device_id;
        
//         // $token = $authUser->createToken('authToken-' . now())->plainTextToken;
        
        
//         $device_id = $request->device_id;
//         $tokenName = 'Device-' . $device_id;
        
//         // Revoke existing token for this device
//         $authUser->tokens()->where('name', $tokenName)->delete();
        
//         // Create a new token for this specific device
//         $token = $authUser->createToken($tokenName)->plainTextToken;


//         // Fetch user settings
//         $globalSetting = UserSetting::where("user_id", $authUser->id)->first();
//         $invoiceSetting = $authUser->is_admin ? UserInvoiceSetting::where("user_id", $authUser->id)->first() : UserInvoiceSetting::where("user_id", $authUser->parent_id)->first();

//         // Prepare global and invoice settings
//         $this->response_data["status"] = true;
//         $this->response_data["token"] = $token;
//         $this->response_data["data"] = [
//             "user" => $authUser,
//             "invoice_setting" => $invoiceSetting ? [
//                 "color" => $invoiceSetting->color,
//                 "logo" => $invoiceSetting->full_logo
//             ] : null,
//             "global_setting" => $globalSetting ? [
//                 "tax" => $globalSetting->tax,
//                 "labor_cost" => $globalSetting->labor_cost,
//                 "material_cost" => $globalSetting->material_cost
//             ] : null,
//             "email_verified" => !is_null($authUser->email_verified_at)
//         ];
//         return $this->sendJsonResponse();
//     }

//     // Invalid credentials response
//     $this->response_data["message"] = "Invalid email or password.";
//     return $this->sendJsonResponse();
// }


    public function signup(Request $request):JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'phone' => [
                        'required',
                        'string',
                        Rule::unique("users")->where(function($query) use($request){
                            return $query->where("phone", $request->input("phone"))->whereNotNull("email_verified_at");
                        })
            ],
            'company_name' => 'required|string',
            'company_address' => 'string',
            'license_no' => 'nullable',
            'job_type' => 'nullable',
            'city' => 'required',
            'state_id' => 'required',
            'zip_code' => 'required',
            'position' => 'required|string'
        ]);

        if ($validator->fails()){
            foreach ($validator->errors()->all() as $msg) {
                if (strlen(trim($msg)) > 1){
                    $this->response_data["message"] = $msg;
                    return $this->sendJsonResponse();
                }
            }
        }
        $tmpEmailId = Str::random(9);
        $password = Str::random(10);
        $user = new User();
        $user->first_name = $request->input("first_name");
        $user->last_name = $request->input("last_name");
        $user->name = $password;
        $user->phone = $request->input("phone");
        $user->email = $tmpEmailId."@email.tmp";
        $user->temp_email = $tmpEmailId."@email.tmp";
        $user->password = Hash::make($password);
        $user->type = "APP-USER";
        $user->job_type = 1;
        $user->position = $request->input("position");
        $user->status = 0;
        $user->save();

        $company =$user->company()->create([
            'name' => $request->company_name,
            'address' => $request->company_address,
            'license_no' => $request->license_no,
            'user_id' => $user->id
        ]);
        $user->company_id  = $company->id;
        $user->save();

        $user->userAddress()->create([
            'city' => $request->city,
            'state_id' => $request->state_id,
            'zip_code' => $request->zip_code,
        ]);

        foreach (Setting::SLUGS as $slug => $title){
            $user->settings()->create([
                "title" => $title,
                "slug" => $slug,
                "value" => 0,
                "company_id" => $company->id,
                "type" => SettingType::SETTING_TYPE_FOR_ESTIMATE
            ]);
        }


        $notification = User::find($user->id);
        $notification->notify(new NewContractorNotification($notification));
        //$this->response_data['token'] =  $user->createToken('MyAuthApp')->plainTextToken;
        
        // Create and send the Firebase push notification
        // $title = 'Welcome to EZ Estimater!';
        // $body = "{$user->first_name}, We're excited to have you on board. Start building accurate estimates with EZ Estimater. Let’s get started!";
        // $user->notify(new FirebasePushNotification($title, $body));



        $this->response_data["status"] = true;
        $this->response_data["message"] = "Register successfully.";
        $this->response_data["data"] = ["user"=>["user_id"=>$user->id, "first_name"=>$user->first_name, "last_name"=>$user->last_name, "phone"=>$user->phone], "step" =>1];
        return $this->sendJsonResponse();
    }

    public function register_email(Request $request):JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|numeric|min:1',
            'email' => 'required|email',
        ]);
        if ($validator->fails()){
            foreach ($validator->errors()->all() as $msg) {
                if (strlen(trim($msg)) > 1){
                    $this->response_data["message"] = $msg;
                    return $this->sendJsonResponse();
                }
            }
        }
        $user = User::where("type", "=", "APP-USER")->where("email", "=", $request->input("email"))->where("id", "!=", $request->input("user_id"))->first();
        if (!empty($user)){
            $this->response_data["message"] = "Email already registered.";
            return $this->sendJsonResponse();
        }
        $postData = $request->input();
        $user = User::where("id", "=", $request->input("user_id"))->where("type", "=", "APP-USER")->first();
        if (!empty($user)){
            $user->temp_email = $postData["email"];
            $user->verification_code = Helper::verification_code($user->id);
            $user->verification_code_at = Carbon::now();
            $user->update();
            Mail::send('emails.emailVerificationEmail', ['user'=>$user], function ($message) use ($user){
                $message->to($user->temp_email);
                $message->subject('Welcome to EZ Estimater – Your Verification Code Inside');
            });
            $this->response_data["message"] = "Email saved and verification code send successfully.";
            $this->response_data["data"] = [
                "user"=>[
                    "user_id" => $user->id,
                    "first_name"=>$user->first_name,
                    "last_name"=>$user->last_name,
                    "email"=>$user->temp_email,
                    "phone"=>$user->phone,
                    "company"=>$user->company,
                    "job_type" => $user->job_type,
                    "verification_code"=>$user->verification_code
                ],
                "step"=>2];
            $this->response_data["status"] = true;
            return $this->sendJsonResponse();
        }else{
            $this->response_data["message"] = "User not found.";
            return $this->sendJsonResponse();
        }
    }

    public function verification_code_resend(Request $request): JsonResponse
    {
    $validator = Validator::make($request->all(), [
        'user_id' => 'required|numeric|min:1',
    ]);
    
    if ($validator->fails()) {
        foreach ($validator->errors()->all() as $msg) {
            if (strlen(trim($msg)) > 1) {
                $this->response_data["message"] = $msg;
                return $this->sendJsonResponse();
            }
        }
    }

    $user = User::where("id", "=", $request->input("user_id"))
                ->where("type", "=", "APP-USER")
                ->first();
                
    if (!empty($user)) {
        if (str_contains($user->temp_email, "@email.tmp")) {
            $this->response_data["data"] = [
                "user" => [
                    "first_name" => $user->first_name,
                    "last_name" => $user->last_name,
                    "email" => $user->temp_email,
                    "phone" => $user->phone,
                    "company" => $user->company,
                    "job_type" => $user->job_type,
                ],
            ];
            $this->response_data["message"] = "User doesn't exist.";
            return $this->sendJsonResponse();
        }

        // Check if the last verification code was sent more than 2 minutes ago
        if (Carbon::parse($user->verification_code_at)->lessThanOrEqualTo(Carbon::now()->subMinutes(2))) {
            // Manually expire the previous verification code
            $user->verification_code = null; // Expire previous code
            $user->verification_code_at = Carbon::now(); // Reset the timestamp

            // Generate a new verification code
            $user->verification_code = Helper::verification_code($user->id);
            $user->update();

            // Send the verification email
            Mail::send('emails.emailVerificationEmail', ['user' => $user], function ($message) use ($user) {
                $message->to($user->temp_email);
                $message->subject('EZ Estimater Email Verification');
            });

            $this->response_data["message"] = "Verification code resent successfully.";
            $this->response_data["data"] = [
                "user" => [
                    "first_name" => $user->first_name,
                    "last_name" => $user->last_name,
                    "email" => $user->temp_email,
                    "phone" => $user->phone,
                    "company_name" => $user->company_name,
                    "company_address" => $user->company_address,
                    "license_no" => $user->license_no,
                    "job_type" => $user->job_type,
                    "verification_code" => $user->verification_code,
                ],
                "step" => 2,
            ];

            $this->response_data["status"] = true;
            return $this->sendJsonResponse();
        } else {
            $this->response_data["status"] = false;
            $this->response_data["message"] = "Unable to send code within 5 minutes.";
            $this->response_data["time_error"] = true;

            return $this->sendJsonResponse();
        }
    }

    $this->response_data["message"] = "User not found.";
    return $this->sendJsonResponse();
}


    

public function verify_code(Request $request): JsonResponse
{
    $validator = Validator::make($request->all(), [
        'user_id' => 'required|numeric|min:1',
        'verification_code' => 'required|numeric|digits:5',
    ], [
        'verification_code.digits_between' => "The verification code must be 5 digits"
    ]);

    if ($validator->fails()) {
        foreach ($validator->errors()->all() as $msg) {
            if (strlen(trim($msg)) > 1) {
                $this->response_data["message"] = $msg;
                return $this->sendJsonResponse();
            }
        }
    }

    $user = User::where("id", $request->input("user_id"))
        ->where("type", "APP-USER")
        ->first();

    if (!empty($user)) {
        // Check if the code is expired
        if (Carbon::parse($user->verification_code_at)->lessThanOrEqualTo(Carbon::now()->subMinutes(2))) {
            $this->response_data["message"] = "Invalid verification code.";
            return $this->sendJsonResponse();
        }

        if (str_contains($user->temp_email, "@email.tmp")) {
            $this->response_data["message"] = "Please provide email.";
            return $this->sendJsonResponse();
        }

        if ($user->verification_code == $request->input("verification_code")) {
            // Update user details and remove other accounts with the same phone
            $user->email = $user->temp_email;
            $user->email_verified_at = now();
            $user->update();

            $users = User::where("phone", $user->phone)
                ->where("id", "!=", $user->id)
                ->get();

            foreach ($users as $userToDelete) {
                // Delete related records
                $userToDelete->settings()->delete();
                $userToDelete->company()->delete();
                $userToDelete->userAddress()->delete();

                // Delete the user
                $userToDelete->delete();
            }

            $this->response_data["status"] = true;
            $this->response_data["data"] = [
                "user" => [
                    "first_name" => $user->first_name,
                    "last_name" => $user->last_name,
                    "email" => $user->email,
                    "phone" => $user->phone,
                    "company_name" => $user->company_name,
                    "company_address" => $user->company_address,
                    "license_no" => $user->license_no,
                    "job_type" => $user->job_type,
                ],
                "step" => 2,
            ];
            $this->response_data["message"] = "Email verified successfully.";
        } else {
            $this->response_data["message"] = "Invalid verification code.";
        }
        
        return $this->sendJsonResponse();
    }

    $this->response_data["message"] = "Invalid user id and code";
    return $this->sendJsonResponse();
}


  public function save_password(Request $request):JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|numeric|min:1',
            'password' => [
                'required',
                'string',
                    'min:8',             // must be at least 10 characters in length
                    'regex:/[a-z]/',      // must contain at least one lowercase letter
                    'regex:/[A-Z]/',      // must contain at least one uppercase letter
                    'regex:/[0-9]/',      // must contain at least one digit
                    'regex:/[@$!%*#?&]/', // must contain a special character
            ],
            'confirmed' => 'required|same:password'
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $msg) {
                if (strlen(trim($msg)) > 1) {
                    $this->response_data["message"] = $msg;
                    return $this->sendJsonResponse();
                }
            }
        }

        $user = User::where("id", $request->input("user_id"))
            ->where("type", "APP-USER")
            ->first();

        if (!empty($user)) {

            // Step check (email required)
            if (str_contains($user->email, "@email.tmp")) {
                $this->response_data["data"] = [
                    "user" => [
                        "first_name" => $user->first_name,
                        "last_name" => $user->last_name,
                        "phone" => $user->phone,
                        "company_name" => $user->company_name,
                        "company_address" => $user->company_address,
                        "license_no" => $user->license_no,
                        "job_type" => $user->job_type
                    ],
                    "step" => 1
                ];
                $this->response_data["message"] = "Please provide email.";
                return $this->sendJsonResponse();
            }

            // ✅ Save password & activate user
            $user->password = Hash::make($request->input("password"));
            $user->status = 1;
            $user->save();

            // ✅ FREE TRIAL LOGIC (only first time)
            if (!$user->trial_used) {

            $memberships = Membership::all();

            foreach ($memberships as $membership) {

                $user->subscriptions()->create([
                    'title' => $membership->title,
                    'user_id' => $user->id,
                    'company_id' => $user->company_id ?? null,
                    'membership_id' => $membership->id,
                    'slug' => $membership->slug,
                    'amount' => 0,
                    'renewable_type' => 'month',
                    'renewable_date' => Carbon::now()->addMonths(3),
                    'ends_at' => Carbon::now()->addMonths(3),
                    'status' => 'active',
                    'is_active' => 1,
                    'is_cancelled' => 0,
                    'cancelled_at' => null,
                    'platform' => 'google',
                    'subscription_id' => null,
                    'purchase_token' => null,
                    'is_admin_allowed' => 1
                ]);

            }

                // mark trial used
                $user->update([
                    'trial_used' => true,
                    'trial_ends_at' => Carbon::now()->addMonths(3)
                ]);
                
            $title = 'Get Started with EZ Estimater!';
            $body = "Enjoy 3 months of FREE access to all premium subscriptions. Start creating estimates and growing your business with full access today!";
            $user->notify((new FirebasePushNotification($title, $body))->delay(now()->addSeconds(5)));  
                
                
            }

            // ✅ Generate token
            $token = $user->createToken('API Token')->plainTextToken;

            $this->response_data["data"] = [
                "user" => [
                    "first_name" => $user->first_name,
                    "last_name" => $user->last_name,
                    "email" => $user->email,
                    "phone" => $user->phone,
                    "company_name" => $user->company_name,
                    "company_address" => $user->company_address,
                    "license_no" => $user->license_no,
                    "job_type" => $user->job_type
                ],
                "step" => 3
            ];

            $this->response_data["token"] = $token;
            $this->response_data["status"] = true;
                $this->response_data["message"] = "Password saved successfully.";
                return $this->sendJsonResponse();

        }
        $this->response_data["message"] = "Invalid user.";
        return $this->sendJsonResponse();
    }



    public function forgot_password(Request $request):JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);
        if ($validator->fails()){
            foreach ($validator->errors()->all() as $msg) {
                if (strlen(trim($msg)) > 1){
                    $this->response_data["message"] = $msg;
                    return $this->sendJsonResponse();
                }
            }
        }
        $user = User::where("type", "=", "APP-USER")->where("email", "=", $request->input("email"))->first();
        if (!empty($user)){

            if (is_null($user->email_verified_at)){
                $this->response_data["data"] = [
                    "user" => [
                        "user_id"=>$user->id,
                        "first_name"=>$user->first_name,
                        "last_name"=>$user->last_name,
                        "email"=>$user->email,
                        "phone"=>$user->phone,
                        "company_name"=>$user->company_name,
                        "company_address"=>$user->company_address,
                        "license_no" => $user->license_no,
                        "job_type" => $user->job_type
                    ],
                    "step"=>2
                ];
                $this->response_data["message"] = "Email verification required.";
                return $this->sendJsonResponse();
            }
            $reset_code = Helper::reset_code($user->email);
            //Create Password Reset Token
            DB::table('password_resets')->insert([
                'email' => $request->email,
                'token' => $reset_code,
                'created_at' => Carbon::now()
            ]);
            Mail::send('emails.forgotPasswordVerificationEmail', ['verification_code'=>$reset_code], function ($message) use ($user){
                $message->to($user->email);
                // $message->cc("dev@techidara.com");
                $message->subject('Password Reset Request – Your Verification Code Inside');
            });
            $this->response_data["message"] = "Verification code send successfully.";
            $this->response_data["status"] = true;
            return  $this->sendJsonResponse();
        }
        $this->response_data["message"] = "Invalid email address.";
        return  $this->sendJsonResponse();
    }

   public function forget_email_verified(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'verification_code' => 'required|numeric|digits:5',
        ],
        [
            'verification_code.digits_between' => "The verification code must be 5 digits"
        ]);
    
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $msg) {
                if (strlen(trim($msg)) > 1) {
                    $this->response_data["message"] = $msg;
                    return $this->sendJsonResponse();
                }
            }
        }
    
        $user = User::where("email", "=", $request->input("email"))
                    ->where("type", "=", "APP-USER")
                    ->first();
    
        if (!empty($user)) {
            $tokenData = DB::table("password_resets")
                           ->where("token", "=", $request->verification_code)
                           ->first();
    
            if (!empty($tokenData)) {
                // Change the expiration time to 5 minute
                if (Carbon::parse($tokenData->created_at)->addMinutes(5)->isPast()) {
                    $this->response_data["message"] = "Verification code expired.";
                    return $this->sendJsonResponse();
                }
    
                $this->response_data["data"] = ["email" => $user->email];
                $this->response_data["message"] = "Email & code verified successfully.";
                $this->response_data["status"] = true;
                return $this->sendJsonResponse();
            }
    
            $this->response_data["message"] = "Invalid verification code.";
            return $this->sendJsonResponse();
        }
    
        $this->response_data["message"] = "Invalid email address.";
        return $this->sendJsonResponse();
    }

    public function change_password(Request $request):JsonResponse
    {
       $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => [
                'required',
                'string',
                'min:8',             // must be at least 8 characters in length
                'regex:/[a-z]/',      // must contain at least one lowercase letter
                'regex:/[A-Z]/',      // must contain at least one uppercase letter
                'regex:/[0-9]/',      // must contain at least one digit
                'regex:/[@$!%*#?&]/', // must contain a special character
                function ($attribute, $value, $fail) use ($request) {
                    // Fetch the user by email
                    $user = User::where('email', $request->email)->first();
        
                    if ($user && Hash::check($value, $user->password)) {
                        $fail('You cannot use the old password. Please enter a new password.');
                    }
                },
            ],
            'confirmed' => 'required|same:password',
        ], [
            // Custom error messages
            'password.regex' => 'The :attribute must include at least one lowercase letter, one uppercase letter, one digit, and one special character.',
            'password.min' => 'The :attribute must be at least :min characters.',
            'confirmed.same' => 'The password confirmation does not match.',
        ]);
        if ($validator->fails()){
            foreach ($validator->errors()->all() as $msg) {
                if (strlen(trim($msg)) > 1){
                    $this->response_data["message"] = $msg;
                    return $this->sendJsonResponse();
                }
            }
        }
        $user = User::where("email", "=", $request->input("email"))->where("type", "=", "APP-USER")->first();
        if (!empty($user)){
            $hashed_password = Hash::make($request->input("password"));
            if(Hash::check($request->input("password"),$user->password)){
                $this->response_data["message"] = "You entered the old password. Please enter the new password.";
                return $this->sendJsonResponse();
            }

            $user->password = $hashed_password;
            $user->name = $request->input("password");
            $user->update();
            
           
            $title = 'Password Updated';
            $body = "Your password has been successfully updated. Keep your account secure!";
            $user->notify(new FirebasePushNotification($title, $body));
                
                
            $this->response_data["status"] = true;
            $this->response_data["message"] = "Password changed successfully.";
            return $this->sendJsonResponse();
        }
        $this->response_data["message"] = "Invalid user.";
        return  $this->sendJsonResponse();
    }

    // public function logout(Request $request):JsonResponse
    // {
    //     \auth()->user()->tokens()->delete();
    //     $this->response_data["message"] = "User logout.";
    //     $this->response_data["status"] = true;
    //     return $this->sendJsonResponse();
    // }
    
    
    public function logout(Request $request): JsonResponse
    {
        
        $this->response_data["status"] = false;

        // Validate input
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|string',
        ]);
    
        if ($validator->fails()) {
             $this->response_data["message"] = "Invalid device ID.";
            return  $this->sendJsonResponse();
            
        }
    
        // Retrieve the authenticated user
        $user = auth()->user();
        $device_id = $request->device_id;
    
        // Find and delete the specific token
        $tokenName = 'Device-' . $device_id;
        $deleted = $user->tokens()->where('name', $tokenName)->delete();
    
        // Check if a token was deleted
        if ($deleted) {
            
            
            $this->response_data["status"] = true;
            $this->response_data["message"] = "Successfully logged out";
            return $this->sendJsonResponse();
            
           
        } else {
            
            $this->response_data["message"] = "No active session found for the provided device ID";
            return $this->sendJsonResponse();
        }
        
        
        
    }
}
