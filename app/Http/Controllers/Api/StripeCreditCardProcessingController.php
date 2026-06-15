<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StripeAccount;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Stripe\Account;
use Stripe\AccountLink;
use Stripe\OAuth;
use Stripe\Stripe;
use Stripe\Transfer;
use Stripe\PaymentIntent;
use Illuminate\Support\Facades\Validator;
use Stripe\Invoice;
use Stripe\Customer;
use Stripe\InvoiceItem;
use App\Models\Estimate;
use App\Models\ContractorTransaction;
use Log;
use App\Notifications\FirebasePushNotification;
use App\Services\FirebaseService;

class StripeCreditCardProcessingController extends ApiBaseController
{
    public function __construct()
    {
        // Initialize Stripe API key
        Stripe::setApiKey(env('STRIPE_SECRET'));
    }
    
    
    
    
     // Create Stripe Connect link for existing Stripe account
    // public function connectStripe(Request $request)
    // {
    //     $user = Auth::user();
        
       
       
    //      $this->response_data["status"] = false;
    
    //     // Check if the user has a connected Stripe account
    //     $stripeAccount = StripeAccount::where('user_id', $user->id)->first();
    
    //     if ($stripeAccount) {
            
    //             $this->response_data["message"] = "Your account is already connected";
    //             return $this->sendJsonResponse();
            
            
    //     }
        
        
        
    //     // Generate a secure token for the current user
    //     $token = Str::random(40);
    //     // Store the user ID in the cache with the token for 10 minutes
    //      Cache::put("stripe_refresh_token_{$token}", $user->id, now()->addMinutes(10)); 
       
        
       
    //      $redirectUri = route('stripe.connect.callback');
 
    //         $stripeUrl = "https://connect.stripe.com/oauth/authorize?" . http_build_query([
    //             'response_type' => 'code',
    //             'client_id' => env('STRIPE_CLIENT_ID'),  // Stripe client ID
    //             'scope' => 'read_write',
    //             'redirect_uri' => $redirectUri,
    //             'state' => $token, // Pass the token securely via state
    //           'prompt' => 'login', // Force login prompt
    //         ]);
        
        
    //         $this->response_data["status"] = true;
    //         $this->response_data["message"] = "Stripe connect link generated successfully.";
    //         $this->response_data["data"] = ['connect_url' => $stripeUrl];
    //         return $this->sendJsonResponse();
             
             
    
    // }
    
    public function connectStripe(Request $request)
{
    $user = Auth::user();
    $this->response_data["status"] = false;

    // Check if the user has a connected Stripe account in the local database
    $stripeAccount = StripeAccount::where('user_id', $user->id)->first();

    if ($stripeAccount) {
        try {
            // Verify from Stripe API
            Stripe::setApiKey(env('STRIPE_SECRET'));
            $stripeAccountDetails = Account::retrieve($stripeAccount->stripe_account_id);

            // If account exists on Stripe, return response
            $this->response_data["status"] = true;
            $this->response_data["message"] = "Your account is already connected.";
            return $this->sendJsonResponse();
        } catch (\Exception $stripeException) {
            // If Stripe API doesn't find the account, remove it from database
            $stripeAccount->delete();
        }
    }

    // Generate a secure token for the current user
    $token = Str::random(40);
    // Store the user ID in the cache with the token for 10 minutes
    Cache::put("stripe_refresh_token_{$token}", $user->id, now()->addMinutes(10));

    // Stripe OAuth URL
    $redirectUri = route('stripe.connect.callback');
    $stripeUrl = "https://connect.stripe.com/oauth/authorize?" . http_build_query([
        'response_type' => 'code',
        'client_id' => env('STRIPE_CLIENT_ID'),
        'scope' => 'read_write',
        'redirect_uri' => $redirectUri,
        'state' => $token, // Pass the token securely via state
        'prompt' => 'login', // Force login prompt
    ]);

    $this->response_data["status"] = true;
    $this->response_data["message"] = "Stripe connect link generated successfully.";
    $this->response_data["data"] = ['connect_url' => $stripeUrl];

    return $this->sendJsonResponse();
}


    // Handle Stripe Connect callback after OAuth
    public function connectCallback(Request $request)
    {
       
        $this->response_data["status"] = false;
        $status = false;
    
        // Retrieve the token from the `state` parameter
        $token = $request->input('state');
      
        if (!$token) {
            $message = "Missing token in state parameter";
            return redirect()->away("ezestimator://stripe?state=".$status."&message=".$message);
        }
      
        // Retrieve the user ID from the cache
        $userId = Cache::get("stripe_refresh_token_{$token}");
        
        if (!$userId) {
            $message = "Invalid or expired token";
            
            return redirect()->away("ezestimator://stripe?state=".$status."&message=".$message);
        }
       
        // Retrieve the user from the database
        $user = User::find($userId);
        if (!$user) {
            $message = "Unauthorized user";
            return redirect()->away("ezestimator://stripe?state=".$status."&message=".$message);
        }
    
        // Retrieve the authorization code from the request
        $code = $request->input('code');
        if (!$code) {
            $message = "Authorization code not provided";
            return redirect()->away("ezestimator://stripe?state=".$status."&message=".$message);
        }
    
        try {
            // Exchange the authorization code for an access token
            $response = \Http::asForm()->post('https://connect.stripe.com/oauth/token', [
                'client_secret' => env('STRIPE_SECRET'),
                'code' => $code,
                'grant_type' => 'authorization_code',
            ]);
            
        
            $stripeData = $response->json();

            if (!$response->successful() || !isset($stripeData['stripe_user_id'])) {
                $message = "Failed to retrieve Stripe account ID";
                return redirect()->away("ezestimator://stripe?state=".$status."&message=".$message);
            }
    
            // Store the Stripe account ID in the database
            $stripeAccount = StripeAccount::firstOrNew(['user_id' => $user->id]);
            $stripeAccount->stripe_account_id = $stripeData['stripe_user_id'];
            $stripeAccount->status = 'connected';
            $stripeAccount->stripe_data = json_encode($stripeData); // Store Stripe data as JSON
            $stripeAccount->save();
    
            $status = true;
            $message = "Stripe account connected successfully";
            
            $title = 'Stripe Account Connected';
            $body = "Your stripe account has been successfully connected.";
            $user->notify(new FirebasePushNotification($title, $body));
             
             
            return redirect()->away("ezestimator://stripe?state=".$status."&message=".$message);
        } catch (\Exception $e) {
            $status = false;
            $message = "Something went wrong! " . $e->getMessage();
            return redirect()->away("ezestimator://stripe?state=".$status."&message=".$message);
        }
        
        
       $status = false;
       $message = "Something went wrong! ";
       return redirect()->away("com.probuilds.ezestimater://api/card-processing/stripe/connect/callback?state=".$status."&message=".$message);
        
    }

    
    
    
    
    public function disconnectStripeAccount(Request $request)
    {
        $user = Auth::user();
        
       
         $this->response_data["status"] = false;
    
        // Check if the user has a connected Stripe account
        $stripeAccount = StripeAccount::where('user_id', $user->id)->first();
    
        if (!$stripeAccount || !$stripeAccount->stripe_account_id) {
            
              
                $this->response_data["message"] = "No connected Stripe account found.";
              
                return $this->sendJsonResponse();
            
            
        }
        
        
    
        try {
           
            // Call Stripe's API to deauthorize the account
            $response = \Http::asForm()->post('https://connect.stripe.com/oauth/deauthorize', [
                'client_id' => env('STRIPE_CLIENT_ID'), // Your Stripe Client ID
                'stripe_user_id' => $stripeAccount->stripe_account_id,
                'client_secret' => env('STRIPE_SECRET'), // Your Stripe Secret Key
            ]);
    
            $responseBody = $response->json();
            // dd($responseBody);
    
            if ($response->failed() || !isset($responseBody['stripe_user_id'])) {
                
                
                $this->response_data["message"] = "Failed to disconnect the Stripe account";
              
                return $this->sendJsonResponse();
                
            }
    
            // Delete the Stripe account record from the database
            $stripeAccount->delete();
            
            
            $this->response_data["status"] = true;
            $this->response_data["message"] = "Stripe account disconnected successfully.";
                
                
            $title = 'Stripe Account Disconnected';
            $body = "Your stripe account has been successfully disconnected.";
            $user->notify(new FirebasePushNotification($title, $body));
            
              
                return $this->sendJsonResponse();
    
           
    
        } catch (\Exception $e) {
            
                $this->response_data["status"] = false;
                $this->response_data["message"] = "Something went wrong";
                $this->response_data["data"] = $e->getMessage();
                return $this->sendJsonResponse();
            
        }
    }
    
        
    public function handleonBoardWebhook(Request $request)
    {
        $payload = $request->all();
    
        // Log full payload for inspection
        Log::info('Received Stripe Webhook Payload', ['payload' => $payload]);
    
        $eventType = $payload['type'] ?? null;
        Log::info("Handling Stripe Event Type: {$eventType}");
    
        // Account updated case
        if ($eventType === 'account.updated') {
            if (!isset($payload['data']['object'])) {
                Log::error('Missing data.object in account.updated event');
                return response()->json(['error' => 'Invalid payload'], 400);
            }
    
            $accountData = $payload['data']['object'];
    
            $stripeAccountId = $accountData['id'] ?? null;
            $chargesEnabled = $accountData['charges_enabled'] ?? false;
            $payoutsEnabled = $accountData['payouts_enabled'] ?? false;
            $detailsSubmitted = $accountData['details_submitted'] ?? false;
    
            $status = 'incomplete';
            if ($detailsSubmitted && $chargesEnabled && $payoutsEnabled) {
                $status = 'completed';
            } elseif ($detailsSubmitted && (!$chargesEnabled || !$payoutsEnabled)) {
                $status = 'pending';
            }
    
            $stripeAccount = \App\Models\StripeAccount::where('stripe_account_id', $stripeAccountId)->first();
            if ($stripeAccount) {
                $stripeAccount->update([
                    'status' => $status,
                    'stripe_data' => json_encode($accountData),
                ]);
                Log::info("Stripe Account {$stripeAccountId} updated with status: {$status}");
            } else {
                Log::warning("Stripe Account ID {$stripeAccountId} not found");
            }
        }
    
        return response()->json(['status' => 'success']);
    }


// public function generateInvoiceLink(Request $request)
// {
//     // Validate request
//     $validator = Validator::make($request->all(), [
//         'estimate_id' => 'required|exists:estimates,id',
//         'description' => 'required|string|max:255',
//         'amount' => 'required|numeric|min:1',
//         'payment_method_id' => 'required|string', // Payment method ID from Stripe.js
//         'customer_zip' => 'required|string|max:10', // Customer ZIP code
//         'transaction_note' => 'nullable|string|max:255', // Optional transaction note
//     ]);

//     if ($validator->fails()) {
//         return response()->json([
//             'status' => false,
//             'message' => $validator->errors()->first(),
//         ]);
//     }

//     // Retrieve estimate and customer info
//     $estimate = Estimate::with('customer')->find($request->estimate_id);
//     if (!$estimate || !$estimate->customer) {
//         return response()->json([
//             'status' => false,
//             'message' => "Invalid estimate or associated customer not found.",
//         ]);
//     }

//     $user = Auth::user();
//     $stripeAccount = StripeAccount::where('user_id', $user->id)->first();

//     if (!$stripeAccount || !$stripeAccount->stripe_account_id) {
//         return response()->json([
//             'status' => false,
//             'message' => 'Please connect your stripe account first',
//         ]);
//     }

//     Stripe::setApiKey(env('STRIPE_SECRET'));

//     $customerEmail = $estimate->customer->email;
//     $customerName = $estimate->customer->name;
//     $customerZip = $request->customer_zip;
//     $transactionNote = $request->transaction_note;

//      try {
         
         
      
        
//          // ✅ Check if customer already exists in Stripe
//         $existingCustomers = \Stripe\Customer::all(['email' => $customerEmail, 'limit' => 1]);

//         if (!empty($existingCustomers->data)) {
//              // Customer already exists, update their ZIP code
//             $customer = $existingCustomers->data[0];
//             \Stripe\Customer::update($customer->id, [
//                 'address' => [
//                     'postal_code' => $customerZip, // Update ZIP code
//                 ],
//             ]);
//             $customerId = $customer->id;
//         } else {
//             // Customer does not exist, create a new one
//             $customer = \Stripe\Customer::create([
//                 'email' => $customerEmail,
//                 'name' => $customerName,
//                 'address' => [
//                     'postal_code' => $customerZip, // Add ZIP code to customer
//                 ],
//             ]);
//             $customerId = $customer->id;
//         }

//         // ✅ Create PaymentIntent for the Main Account
//         $paymentIntent = \Stripe\PaymentIntent::create([
//             'amount' => $request->amount * 100, // Convert to cents
//             'currency' => 'usd',
//             'customer' => $customerId, // Attach customer
//             'payment_method' => $request->payment_method_id,
//             'confirm' => true, // Auto-confirm the payment
//             'off_session' => true, // Process without customer interaction
//             'description' => $request->description,
//              'transfer_data' => [
//                 'destination' => $stripeAccount->stripe_account_id, // Send payment to the connected account
//             ],
         
//         ]);


        
        
        
        
//         $this->response_data["status"] = true;
//         $this->response_data["message"] = "Payment successfull.";
      
//         return $this->sendJsonResponse();
    
           
    
//         } catch (\Exception $e) {
            
//                 $this->response_data["status"] = false;
//                 $this->response_data["message"] = 'Error processing the payment: ' . $e->getMessage();
//                 $this->response_data["data"] = $e->getMessage();
//                 return $this->sendJsonResponse();
            
//         }
        
       
    
// }

   public function generateInvoiceLink(Request $request)
{
    // Validate request
    $validator = Validator::make($request->all(), [
        'estimate_id' => 'required|exists:estimates,id',
        'description' => 'required|string|max:255',
        'amount' => 'required|numeric|min:1',
        'payment_method_id' => 'required|string',
        // 'customer_zip' => 'required|string|max:10',
        'transaction_note' => 'nullable|string|max:255',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => false,
            'message' => $validator->errors()->first(),
        ]);
    }

    // Retrieve estimate and customer info
    $estimate = Estimate::with('customer')->find($request->estimate_id);
    if (!$estimate || !$estimate->customer) {
        return response()->json([
            'status' => false,
            'message' => "Invalid estimate or associated customer not found.",
        ]);
    }

    $user = Auth::user();
    $stripeAccount = StripeAccount::where('user_id', $user->id)->first();

    if (!$stripeAccount || !$stripeAccount->stripe_account_id) {
        
          $this->response_data["message"] = 'Please connect your stripe account first';
          return $this->sendJsonResponse();
    }
    
    

    Stripe::setApiKey(env('STRIPE_SECRET'));
    

    try {
        // ✅ Step 2: Verify from Stripe API
        $stripeAccountDetails = \Stripe\Account::retrieve($stripeAccount->stripe_account_id);

        if (!$stripeAccountDetails || !$stripeAccountDetails->charges_enabled) {
        
            $this->response_data["message"] = 'Your Stripe account is not active. Please Reconnect stripe Account Again';
            return $this->sendJsonResponse();
        }
    } catch (\Exception $e) {
        
        $this->response_data["message"] = 'Error verifying Stripe account: ' . $e->getMessage();
        return $this->sendJsonResponse();
        
    }
    

    $customerEmail = $estimate->customer->email;
    $customerName = $estimate->customer->name;
    $customerAddress = $estimate->customer->address;
    $customerCity = $estimate->customer->city;
    $customerState = $estimate->customer->state;
    // $customerZip = $request->customer_zip;
    $transactionNote = $request->transaction_note;

    try {
        // Check if customer exists in Stripe
        $existingCustomers = \Stripe\Customer::all(['email' => $customerEmail, 'limit' => 1]);
        

        if (!empty($existingCustomers->data)) {
            $customer = $existingCustomers->data[0];
            \Stripe\Customer::update($customer->id, [
                 'address' => [
                    'line1' => $customerAddress ?? 'Unknown Address',
                //   'postal_code' => $customerZip,
                    'city' => $customerCity ?? 'Unknown City',
                    'state' => $customerState ?? 'Unknown State',
                    'country' => 'US', // Change if needed
                ],
                 'tax_exempt' => 'none', // Enable tax collection
            ]);
            $customerId = $customer->id;
        } else {
            $customer = \Stripe\Customer::create([
                'email' => $customerEmail,
                'name' => $customerName,
                'address' => [
                    'line1' => $customerAddress ?? 'Unknown Address',
                    'postal_code' => "",
                    'city' => $customerCity ?? 'Unknown City',
                    'state' => $customerState ?? 'Unknown State',
                    'country' => 'US', // Change if needed
                ],
                 'tax_exempt' => 'none', // Enable tax collection
               
            ]);
            $customerId = $customer->id;
        }
     

          // ✅ Calculate Stripe fee manually
        $amount = $request->amount; //1
        $stripeFee = ($amount * 0.029) + 0.30;
        $stripeFee = round($stripeFee, 2); // Round to 2 decimal places

        // ✅ Calculate grand total
        $grandTotal = $amount + $stripeFee;

        // ✅ Create a PaymentIntent
        $paymentIntent = \Stripe\PaymentIntent::create([
            'amount' => $grandTotal * 100, // Convert to cents
            'currency' => 'usd',
            'customer' => $customerId,
            'payment_method' => $request->payment_method_id,
            'confirm' => true,
            'off_session' => true,
            'description' => $request->description,
            'transfer_data' => [
                'destination' => $stripeAccount->stripe_account_id,
            ]
        ]);

        // ✅ Store transaction details
        $transaction = ContractorTransaction::create([
            'contractor_id' => $user->id,
            'estimate_id' => $request->estimate_id,
            'customer_id' => $estimate->customer->id,
            'stripe_transaction_id' => $paymentIntent->id,
            'amount' => $amount,
            'description' => $paymentIntent->description,
            'tax' => $stripeFee,
            'grand_total' => $grandTotal,
            'status' => 'completed',
        ]);

        // ✅ Convert transaction object to array
        $transactionArray = $transaction->toArray();
        $transactionArray['customer_name'] = $estimate->customer->name;

        $this->response_data["status"] = true;
        $this->response_data["message"] = "Payment successful";
        $this->response_data["data"] = $transactionArray;

        return $this->sendJsonResponse();
            
        

    } catch (\Exception $e) {
        
        $this->response_data["message"] = 'Error processing the payment: ' . $e->getMessage();
        return $this->sendJsonResponse();
                    
        
    }
}


    public function getTransactions(Request $request)
    {
        
        $validator = Validator::make($request->all(), [
           'customer_id' => 'required|exists:customers,id',
            'estimate_id' => 'required|exists:estimates,id',
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $msg) {
                if (strlen(trim($msg)) > 1) {
                    $this->response_data["message"] = $msg;
                    return $this->sendJsonResponse();
                }
            }
        }
    
    
            // Fetch transactions
          $transactions = ContractorTransaction::with('customer') // Customer model load karein
    ->where('customer_id', $request->customer_id)
    ->where('estimate_id', $request->estimate_id)
    ->get()
    ->map(function ($transaction) {
        // Customer name ko transaction object me add karein
        $transaction->customer_name = optional($transaction->customer)->first_name . ' ' . optional($transaction->customer)->last_name;
        
        // Customer model ko response se remove karein
        unset($transaction->customer);

        return $transaction;
    });
        
            if ($transactions->isEmpty()) {
                $this->response_data["status"] = false;
                        $this->response_data["message"] = 'No transactions found for this estimate and customer';
        
                        return $this->sendJsonResponse();
                        
            
            }
    
    
            $this->response_data["status"] = true;
            $this->response_data["message"] = "Transactions Fetched";
            $this->response_data["data"] = $transactions;
            return $this->sendJsonResponse();
        
    }



    // public function checkConnectStatus(Request $request)
    // {
    //     try {
    //         // Get the authenticated user
    //         $user = Auth::user();
            
    //         // Check if the user is authenticated
    //         if (!$user) {
    //             $this->response_data["status"] = false;
    //             $this->response_data["message"] = "User not authenticated.";
    //             return $this->sendJsonResponse();
    //         }
    
    //         // Check the Stripe account in the local database
    //         $stripeAccount = StripeAccount::where('user_id', $user->id)->first();
    
    //         // Determine the connection status
    //         if (!$stripeAccount) {
    //             $this->response_data["status"] = false;
    //             $this->response_data["message"] = "Stripe account not connected.";
    //         } else {
    //             $this->response_data["status"] = true;
    //             $this->response_data["message"] = "Stripe account is connected.";
    //             $this->response_data["data"] = [
    //                 'stripe_account_id' => $stripeAccount->stripe_account_id,
    //                 'created_at' => $stripeAccount->created_at,
    //             ];
    //         }
    
    //         return $this->sendJsonResponse();
    //     } catch (\Exception $e) {
    //         // Handle exceptions
    //         $this->response_data["status"] = false;
    //         $this->response_data["message"] = "An error occurred while checking the Stripe connection status.";
    //         $this->response_data["error"] = $e->getMessage();
    //         return $this->sendJsonResponse();
    //     }
    // }

    
public function checkConnectStatus(Request $request)
{
    try {
        // Get the authenticated user
        $user = Auth::user();

        // Check if the user is authenticated
        if (!$user) {
            $this->response_data["status"] = false;
            $this->response_data["message"] = "User not authenticated.";
            return $this->sendJsonResponse();
        }

        // Check the Stripe account in the local database
        $stripeAccount = StripeAccount::where('user_id', $user->id)->first();

        if (!$stripeAccount) {
            $this->response_data["status"] = false;
            $this->response_data["message"] = "Stripe account not connected.";
        } else {
            // Default response
            $this->response_data["status"] = true;
            $this->response_data["message"] = "Stripe account is connected.";
            $this->response_data["data"] = [
                'stripe_account_id' => $stripeAccount->stripe_account_id,
                'created_at' => $stripeAccount->created_at,
                'stripe_status' => 'Unknown',
                'payouts_enabled' => false,
                'details_submitted' => false
            ];

            // Verify from Stripe API
            try {
                Stripe::setApiKey(env('STRIPE_SECRET'));

                $stripeAccountDetails = Account::retrieve($stripeAccount->stripe_account_id);

                // Update Stripe status based on API response
                $this->response_data["data"]['stripe_status'] = $stripeAccountDetails->charges_enabled ? 'Active' : 'Inactive';
                $this->response_data["data"]['payouts_enabled'] = $stripeAccountDetails->payouts_enabled;
                $this->response_data["data"]['details_submitted'] = $stripeAccountDetails->details_submitted;
            } catch (\Exception $stripeException) {
                // If Stripe API doesn't find the account, update response to "not connected"
                $this->response_data["status"] = false;
                $this->response_data["message"] = "Stripe account not connected.";
            }
        }

        return $this->sendJsonResponse();
    } catch (\Exception $e) {
        $this->response_data["status"] = false;
        $this->response_data["message"] = "An error occurred while checking the Stripe connection status.";
        $this->response_data["error"] = $e->getMessage();
        return $this->sendJsonResponse();
    }
}
    public function updateStatus(Request $request)
    {
        
        $this->response_data["status"] = false;
        
        $user = Auth::user();
        
    
        // Retrieve the user from the database
        $user = User::find($userId);
    
        // If the user doesn't exist
        if (!$user) {
            $this->response_data["message"] = "User not found.";
            return $this->sendJsonResponse();
            
        }
    
        // Stripe OAuth token exchange logic
        $code = $request->input('code');
       
    
        if (!$code) {
            
            $this->response_data["message"] = "Authorization code not provided.";
            return $this->sendJsonResponse();
           
        }
    
        try {
            // Exchange the authorization code for an access token
            $response = \Http::asForm()->post('https://connect.stripe.com/oauth/token', [
                'client_secret' => env('STRIPE_SECRET'),
                'code' => $code,
                'grant_type' => 'authorization_code',
            ]);
    
            $stripeData = $response->json();
    
            // If the stripe account ID is not found
            if (!isset($stripeData['stripe_user_id'])) {
                
                
             $this->response_data["message"] = "Failed to retrieve Stripe account details.";
             return $this->sendJsonResponse();
            
            
            }
    
            // Store the Stripe account ID in your database for the user
            $stripeAccount = StripeAccount::firstOrNew(['user_id' => $user->id]);
            $stripeAccount->stripe_account_id = $stripeData['stripe_user_id'];
            $stripeAccount->status = 'connected';
            $stripeAccount->stripe_data = $stripeData;  // Optionally store all the Stripe data
            $stripeAccount->save();
    
    
            
            
            $this->response_data["status"] = true;
            $this->response_data["message"] = "Stripe account connected successfully.";
            $this->response_data["data"] = ['stripe_account_id' => $stripeData['stripe_user_id']];
            return $this->sendJsonResponse();
            
            
           
        } catch (\Exception $e) {
            $this->response_data["status"] = false;
            $this->response_data["message"] = "Something went wrong";
            $this->response_data["data"] = $e->getMessage();
            return $this->sendJsonResponse();
        }
    }

    
    
    

    // Generate onboarding link for new Stripe account
    public function onBoardStripe(Request $request)
    {
        
        
        $user = Auth::user();

        // Check if user already has a StripeAccount entry
        $stripeAccount = StripeAccount::firstOrNew(['user_id' => $user->id]);
        
        try {
            
            if (!$stripeAccount->stripe_account_id) {
                // Create new Stripe account if none exists
                $account = Account::create([
                    'type' => 'standard',
                    'country' => 'US',
                    'email' => $request->email,
                    'capabilities' => [
                        'card_payments' => ['requested' => true],
                        'transfers' => ['requested' => true],
                    ],
                ]);

                // Save Stripe account data
                $stripeAccount->stripe_account_id = $account->id;
                $stripeAccount->status = 'created';
                $stripeAccount->stripe_data = $account->toArray();  // Store additional data
                $stripeAccount->save();
            }

            
            
            $token = Str::random(40); // random token

            // Save in a new table `onboarding_sessions` or just cache (your choice)
            Cache::put("stripe_onboard_token_{$token}", $user->id, now()->addMinutes(30));
        
            // Optional: Also store in session for convenience (especially if no query param allowed)
            session(['stripe_onboard_token' => $token]);
            
            $token = session('stripe_onboard_token');  // First try session
           
    
            // Build refresh and return URLs (with token)
            $refreshUrl = route('stripe.onboard.refreshLink');
            $returnUrl = route('stripe.onboard.callback');
            
           

            // Create Stripe Account Link for onboarding
            $accountLink = AccountLink::create([
                'account' => $stripeAccount->stripe_account_id,
                'refresh_url' => $refreshUrl,
                'return_url' => route('stripe.onboard.callback'),
                'type' => 'account_onboarding',
            ]);

            
              $this->response_data["status"] = true;
              $this->response_data["message"] = "Stripe onboarding link generated successfully.";
              $this->response_data["data"] = ['onboarding_url' => $accountLink->url];
              
                
                
                
        } catch (\Exception $e) {
                $this->response_data["status"] = false;
                $this->response_data["message"] = "Something went wrong";
                $this->response_data["data"] = $e->getMessage();
        }
        
        return $this->sendJsonResponse();
        
    }
    
    // Handle Stripe onboarding callback (onboarding completed)
    public function onBoardCallback(Request $request)
    {
        
        
        
        $this->response_data["status"] = false;
        
        // $token = session('stripe_onboard_token');  // First try session
      
    
        // if (!$token) {
            
        //     $this->response_data["message"] = "Missing onboarding token";
        //     return $this->sendJsonResponse();
            
           
        // }
    
        // // Find the user ID linked to this token
        // $userId = Cache::get("stripe_onboard_token_{$token}");
    
        // if (!$userId) {
            
        //     $this->response_data["message"] = "Invalid or expired token";
        //     return $this->sendJsonResponse();
            
        // }
    
        // // Load the user (without relying on auth)
        // $user = User::find($userId);
        // if (!$user) {
            
            
        //     $this->response_data["message"] = "User not found";
        //     return $this->sendJsonResponse();
            
           
        // }

        // // Find the StripeAccount associated with this user
        // $stripeAccount = StripeAccount::where('user_id', $userId)->first();
    
        
        // $stripeAccountId = $request->input('account_id');

        // if (!$stripeAccountId) {
            
        //     $this->response_data["message"] = "Stripe account ID is missing";
        //     return $this->sendJsonResponse();
    
        // }

        // // Find the Stripe account in your database
        // $stripeAccount = StripeAccount::where('stripe_account_id', $stripeAccountId)->first();

        // if (!$stripeAccount) {
             
        //     $this->response_data["message"] = "Stripe account not found.";
        //     return $this->sendJsonResponse();
        // }

        // // Retrieve Stripe account details
        // $account = Account::retrieve($stripeAccountId);

        // if (!$account->details_submitted || !$account->charges_enabled) {
        //     // Mark account as incomplete
        //     $stripeAccount->status = 'incomplete';
        //     $stripeAccount->save();
            
        //     $this->response_data["status"] = true;
        //     $this->response_data["message"] = "Stripe onboarding incomplete. Please restart the process";
        //     $this->response_data["data"] = ['refresh_url' => route('stripe.onboard.refreshLink')];
        //     return $this->sendJsonResponse();
              
        // }

        // // Update the Stripe account to active if onboarding is complete
        // $stripeAccount->status = 'active';
        // $stripeAccount->save();
        // Cache::forget("stripe_onboard_token_{$token}");
        
        $status = true;
        $message = "Stripe account connected successfully";
        return redirect()->away("ezestimator://stripe?state=".$status."&message=".$message);
            
        
         $this->response_data["status"] = true;
            $this->response_data["message"] = "Stripe onboarding completed successfully.";
            $this->response_data["data"] = [ 
                'stripe_account_id' => "",
                'charges_enabled' => ""
                ];
        
        return $this->sendJsonResponse();
        
    }


     public function onBoardRefreshLink(Request $request)
    {
        
        $this->response_data["status"] = false;
        
        if (!$request->hasValidSignature()) {
            $this->response_data["message"] = "Invalid or expired URL.";
           return $this->sendJsonResponse();
           
        }

        $token = $request->query('token');
        $userId = Cache::pull("stripe_refresh_token_{$token}");

        if (!$userId) {
            
            $this->response_data["message"] = "Invalid or expired token.";
            return $this->sendJsonResponse();
            
        }

        $user = User::find($userId);

        if (!$user) {
            
            $this->response_data["message"] = "User not found..";
           return  $this->sendJsonResponse();
            
        }

        $stripeAccount = StripeAccount::where('user_id', $user->id)->first();

        if (!$stripeAccount || !$stripeAccount->stripe_account_id) {
            
            $this->response_data["message"] = "No Stripe account found for this user.";
            return $this->sendJsonResponse();
            
        }

        try {
            // Generate a new onboarding link
            $accountLink = AccountLink::create([
                'account' => $stripeAccount->stripe_account_id,
                'refresh_url' => route('stripe.onboard.refreshLink'),
                'return_url' => route('stripe.onboard.callback'),
                'type' => 'account_onboarding',
            ]);


            $this->response_data["status"] = true;
            $this->response_data["message"] = "Stripe onboarding link refreshed.";
            $this->response_data["data"] = ['onboarding_url' => $accountLink->url];
             return $this->sendJsonResponse();
             
             
           
        } catch (\Exception $e) {
            $this->response_data["status"] = false;
                $this->response_data["message"] = "Something went wrong";
                $this->response_data["data"] = $e->getMessage();
        }
        return $this->sendJsonResponse();
        
    }
    
    
   
    

    public function createPaymentIntent(Request $request)
    {
        // Validate the amount input
        $validator = Validator::make($request->all(), [
            'amount' => 'required',
        ]);
    
        if ($validator->fails()){
            foreach ($validator->errors()->all() as $msg) {
                if (strlen(trim($msg)) > 1){
                    $this->response_data["message"] = $msg;
                    return $this->sendJsonResponse();
                }
            }
        }
    
        $amount = $request->input('amount');  // Amount in dollars
    
        $user = Auth::user();
    
        // Get the user's Stripe account (assuming this is a model you have)
        $stripeAccount = StripeAccount::firstOrNew(['user_id' => $user->id]);
    
        try {
            $contractorStripeAccountId = $stripeAccount->stripe_account_id;
    
            // Check if the user has a payment method attached
            $paymentMethod = ""; // Assuming you have a method to retrieve the user's Stripe payment method
    
            if (!$paymentMethod) {
                // If no payment method, create a default one (e.g., `4242 4242 4242 4242`)
                $defaultCard = '4242 4242 4242 4242';  // Stripe's test card number
    
                // Create a PaymentMethod using the test card
                $paymentMethod = \Stripe\PaymentMethod::create([
                    'type' => 'card',
                    'card' => ['number' => $defaultCard, 'exp_month' => 12, 'exp_year' => 2026, 'cvc' => '123'],
                ]);
    
                // // Attach this payment method to the customer
                // $user->addStripePaymentMethod($paymentMethod); // Assuming you have a method to associate the payment method with the user
            }
    
            // Now that we have a payment method, create the PaymentIntent
            $paymentIntent = \Stripe\PaymentIntent::create([
                'amount' => $amount * 100,  // Amount in cents
                'currency' => 'usd',
                'payment_method_types' => ['card'],
                'payment_method' => $paymentMethod->id,  // Attach the payment method
                'transfer_data' => [
                    'destination' => $contractorStripeAccountId,  // Contractor’s Stripe account ID
                ],
            ]);
    
            // Return the client secret for the frontend to confirm the payment
            $this->response_data["status"] = true;
            $this->response_data["message"] = "Payment intent created successfully.";
            $this->response_data["data"] = ['payment_intent' => $paymentIntent->id, 'client_secret' => $paymentIntent->client_secret];
            return $this->sendJsonResponse();
    
        } catch (\Exception $e) {
            // Handle any errors
            $this->response_data["status"] = false;
            $this->response_data["message"] = "Something went wrong.";
            $this->response_data["data"] = $e->getMessage();
            return $this->sendJsonResponse();
        }
    }


    // Pay contractor after receiving payment from the customer
    public function payContractor(Request $request)
    {
        $this->response_data["status"] = false;
        $validator = Validator::make($request->all(), [
            'amount' => 'required',
            'payment_intent_id'=> 'required',
        ]);
        
        if ($validator->fails()){
            foreach ($validator->errors()->all() as $msg) {
                if (strlen(trim($msg)) > 1){
                    $this->response_data["message"] = $msg;
                    return $this->sendJsonResponse();
                }
            }
        }
        
        
        $amount = $request->input('amount');  // Amount to pay the contractor
        
        $user = Auth::user();
        
        $stripeAccount = StripeAccount::firstOrNew(['user_id' => $user->id]);
        $contractorStripeAccountId = $stripeAccount->stripe_account_id;

        try {
            $paymentIntentId = $request->input('payment_intent_id');  // Optionally passed by mobile app
             
             $paymentIntent = PaymentIntent::retrieve($paymentIntentId);
            
            if ($paymentIntent->status !== 'succeeded') {
                 $this->response_data["message"] = "Payment not successful.";
                return $this->sendJsonResponse();
                
            }
            
            // Transfer to contractor’s Stripe account
            $transfer = Transfer::create([
                'amount' => $amount * 100,  // Amount in cents
                'currency' => 'usd',
                'destination' => $contractorStripeAccountId,
                'transfer_group' => 'Payment to Contractor',
            ]);
            
            
            $this->response_data["status"] = true;
            $this->response_data["message"] = "Payment has been successfully paid.";
            $this->response_data["data"] = ['transfer' => $transfer];
            return $this->sendJsonResponse();

           

        } catch (\Exception $e) {
            $this->response_data["status"] = false;
                $this->response_data["message"] = "Something went wrong";
                return $this->response_data["data"] = $e->getMessage();
        }
    }

    

   
}