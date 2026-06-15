<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Membership;
use App\Models\Subscription;
use App\Services\StripeService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Notifications\FirebasePushNotification;
use App\Services\FirebaseService;
use App\Models\User; 
use Stripe\Customer;




class SubscriptionController extends ResponseController
{
    
    
    public function createPaymentMethod(Request $request,StripeService $stripeService)
    {
        
        $validator = Validator::make($request->all(), [
            'payment_token' => 'required|string',
        ]);
        
        if ($validator->fails()){
            foreach ($validator->errors()->all() as $msg) {
                if (strlen(trim($msg)) > 1){
                    $this->response_data["message"] = $msg;
                    return $this->sendJsonResponse();
                }
            }
        }
        

        try {
            $paymentToken = $request->input('payment_token');

            // Attach the PaymentMethod to the customer
            $stripeService->attachPaymentMethod($paymentToken);
            
            $this->response_data["status"] = true;
            $this->response_data["message"] = "Payment method added successfully.";
           
            return $this->sendJsonResponse();
            
        } catch (\Exception $e) {
            
            $this->response_data["status"] = false;
            $this->response_data["message"] = "Error adding payment method";
            return $this->response_data["data"] = $e->getMessage();
                
              
        }
    }
    
  
     public function getDefaultPaymentMethod(Request $request,StripeService $stripeService)
    {
        
        $this->response_data["status"] = false;
        
        
        try {
            
            $stripeCusId = $stripeService->getOrCreateCustomer();
            
            
            $user = auth()->user();
            $customer = Customer::retrieve($user->stripe_cust_id);
            
            // Get the default payment method ID
            $defaultPaymentMethodId = $customer->invoice_settings->default_payment_method;
    
            if ($defaultPaymentMethodId) {
                // Fetch the default payment method details
                $paymentMethod = $stripeService->getPaymentMethodById($defaultPaymentMethodId);

                $this->response_data["status"] = true;
                 $this->response_data["data"] = $paymentMethod;
                 return $this->sendJsonResponse();
                // Return the payment method details
                
            }
    
            $this->response_data["status"] = false;
            $this->response_data["message"] = "No default payment method found";
            return $this->sendJsonResponse();
            
    
        } catch (\Exception $e) {
           $this->response_data["status"] = false;
            $this->response_data["message"] = "Error adding payment method";
            return $this->response_data["data"] = $e->getMessage();
        }
    }


    // Get all payment methods attached to the user's account
    public function getPaymentMethods(StripeService $stripeService)
    {
        
        
        try {
            // Get the payment methods from StripeService
            $paymentMethods = $stripeService->getMyPaymentMethods();
    
            // Retrieve the default payment method from the customer
            $defaultPaymentMethodId = $stripeService->getCustomerDefaultPaymentMethod();
    
            $this->response_data["status"] = true;
            $this->response_data["message"] = "Payment methods retrieved successfully.";
            
            // Format the response with necessary details
            $formattedMethods = array_map(function ($method) use ($defaultPaymentMethodId) {
                return [
                    'id' => $method->id,
                    'brand' => $method->card->brand,
                    'last4' => $method->card->last4,
                    'exp_month' => $method->card->exp_month,
                    'exp_year' => $method->card->exp_year,
                    'default' => $method->id === $defaultPaymentMethodId, // Check if it's the default
                ];
            }, $paymentMethods);
            $this->response_data["status"] = true;
            $this->response_data["data"] = ['paymentMethods' => $formattedMethods];
            return $this->sendJsonResponse();
        } catch (\Exception $e) {
            $this->response_data["status"] = false;
            $this->response_data["message"] = "Error retrieving payment methods";
            $this->response_data["data"] = $e->getMessage();
            return $this->sendJsonResponse();
        }
    }

    // Set a payment method as default
    public function setDefaultPaymentMethod(Request $request,StripeService $stripeService)
    {
        
        
        $validator = Validator::make($request->all(), [
            'payment_method_id' => 'required|string',
        ]);
        
        if ($validator->fails()){
            foreach ($validator->errors()->all() as $msg) {
                if (strlen(trim($msg)) > 1){
                    $this->response_data["message"] = $msg;
                    return $this->sendJsonResponse();
                }
            }
        }
        
        
        
        try {
            $paymentMethodId = $request->input('payment_method_id');
            $stripeService->setDefaultPaymentMethod($paymentMethodId);


            $this->response_data["status"] = true;
            
        $this->response_data["message"] = "Payment method set as default successfully";
           
           return $this->sendJsonResponse();
            

          
        } catch (\Exception $e) {
            
            $this->response_data["status"] = false;
            $this->response_data["message"] = "Error setting default payment method";
            return $this->response_data["data"] = $e->getMessage();
            
        }
    }
    
    
    
  public function payNow(Request $request, StripeService $stripeService): JsonResponse
    {
        // Apply validation for distinct $request->id too
        $validator = Validator::make($request->all(), [
            'membership_ids' => 'required|array',
            'token' => 'required_if:isSave,true|string', // Token is required only if isSave is true
            'isSave' => 'sometimes|boolean', // Optional input, defaults to false
        ]);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $msg) {
                if (strlen(trim($msg)) > 1) {
                    $this->response_data["message"] = $msg;
                    return $this->sendJsonResponse();
                }
            }
        }
    
        $subscriptions = auth()->user()
            ->subscriptions()
            ->whereIn("membership_id", $request->membership_ids)
            ->where("is_cancelled", false)
            ->get();
    
        if (count($subscriptions) > 0) {
            $this->response_data["status"] = false;
            $this->response_data["message"] = "Membership already subscribed";
            return $this->sendJsonResponse();
        }
    
        try {
            $memberships = Membership::whereIn("id", $request->membership_ids)->get();
    
            // Need to do stripe work here
            $stripeCusId = $stripeService->getOrCreateCustomer();
    
            // Conditionally attach payment method
            if ($request->input('isSave', false)) { // Default to false if not provided
                $stripeService->attachPaymentMethod($request->token);
            }
    
            $successfulSubscriptions = []; // Array to track successful subscriptions
    
            foreach ($memberships as $membership) {
                $existingSubscription = auth()->user()->subscriptions()
                    ->where('membership_id', $membership->id)
                    ->where('is_cancelled', 0)
                    ->whereNull('cancelled_at')
                    ->where(function ($query) {
                        $query->where('ends_at', '>', Carbon::now())
                              ->orWhereNull('ends_at');
                    })
                    ->first();
    
                if ($existingSubscription) {
                    // Skip creating a new subscription if a valid one exists
                    continue;
                }
    
                // Create a Stripe subscription
                $subscription = $stripeService->createSubscription($membership->price_id);
    
                // Store the subscription in the database
                auth()->user()->subscriptions()->create([
                    'title' => $membership->title,
                    'user_id' => auth()->user()->id,
                    'membership_id' => $membership->id,
                    'slug' => $membership->slug,
                    'amount' => $membership->amount,
                    'renewable_date' => Carbon::now()->addYear(),
                    'subscription_id' => $subscription->id
                ]);
    
                // Add to successful subscriptions
                $successfulSubscriptions[] = $membership;
            }
    
            // Check if there are any successful subscriptions
            if (count($successfulSubscriptions) > 0) {
                $this->response_data["status"] = true;
                $this->response_data["data"] = [
                    "subscriptions" => auth()->user()->subscriptions,
                    "total" => collect($successfulSubscriptions)->sum("amount")
                ];
    
                // Send a single notification for all successful subscriptions
                $user = $request->user();
                $title = 'Billing Alert';
                $amount = number_format(collect($successfulSubscriptions)->sum("amount"), 2);
                $body = "You’ve been charged $$amount. Thank you for staying with EZ Estimater!";
                $user->notify(new FirebasePushNotification($title, $body));
            } else {
                // No subscriptions created
                $this->response_data["status"] = false;
                $this->response_data["message"] = "Faild to purchase subscriptions.";
            }
    
        } catch (\Exception $e) {
           // \Log::error($e->getMessage());
            $this->response_data["status"] = false;
            $this->response_data["message"] = $e->getMessage();
            $this->response_data["data"] = $e->getMessage();
        }
    
        return $this->sendJsonResponse();
    }


    // public function cancel(Subscription $subscription,Request $request,StripeService $stripeService):JsonResponse
    // {
    //     $this->response_data["status"] = false;
    //     $this->response_data["message"] = "unavailable to cancelled the membership";
    //     // Need to do stripe work here
    //   if ($cancelSubscription = $stripeService->cancelSubscription($subscription->subscription_id)) {
    //         $isCancelled = $subscription->update([
    //             "is_cancelled" => true,
    //             "cancelled_at" => Carbon::now()
    //         ]);
    //         if ($isCancelled) {
    //             $this->response_data["status"] = true;
    //             $this->response_data["message"] = "subscriptions is cancelled";
    //         }
    //     }
    //     return $this->sendJsonResponse();
    // }
    
    
    public function cancel(Subscription $subscription, Request $request, StripeService $stripeService): JsonResponse 
    {
        $this->response_data["status"] = false;
        $this->response_data["message"] = "Unable to cancel the membership";
    
        // Attempt to cancel the Stripe subscription at the end of the billing cycle
        $cancelSubscription = $stripeService->cancelSubscription($subscription->subscription_id);
        
        if ($cancelSubscription) {
            // Update local database to reflect cancellation status and period end date
            $isCancelled = $subscription->update([
                "is_cancelled" => true,
                "cancelled_at" => Carbon::now(),
                "ends_at" => Carbon::createFromTimestamp($cancelSubscription->current_period_end) // Get Stripe period end
            ]);
            
            if ($isCancelled) {
                
                $user = $request->user();
                $title = 'Subscription Canceled';
                $body = "Your ".$subscription->title." subscription has been canceled.  Renew anytime to continue using EZ Estimater.";
                $user->notify(new FirebasePushNotification($title, $body));
                
                
                $this->response_data["status"] = true;
                $this->response_data["message"] = "Subscription is canceled and will end at the end of the billing cycle.";
                
                
            }
        }
    
        return $this->sendJsonResponse();
    }


    public function reactivate(Subscription $subscription, Request $request, StripeService $stripeService): JsonResponse
    {
        // Attempt to reactivate the subscription in Stripe
        $reactivate = $stripeService->reactivateSubscription($subscription->subscription_id);
        
        
    
        if ($reactivate) {
            // Update local database subscription status to reflect it's active again
            $updated = $subscription->update([
                "is_cancelled" => false,
                "cancelled_at" => null,
                "ends_at" => null, // Reset the end date as it's now active
                // "renewable_date" => now()->addMonth() // Assuming you want to set a new renewable date, adjust as needed
            ]);
    
            if ($updated) {
                
                $user = $request->user();
                $title = 'Subscription Renewed';
                $body = "Thank you! Your ".$subscription->title." subscription is active, Keep using it without interruptions.";
                $user->notify(new FirebasePushNotification($title, $body));
                
                
                $this->response_data["status"] = true;
                $this->response_data["message"] = "Subscription reactivated successfully.";
                return $this->sendJsonResponse();
            }
        }
    
        $this->response_data["status"] = false; // Change to false if we couldn't reactivate
        $this->response_data["message"] = "Subscription already active or cannot be reactivated.";
        return $this->sendJsonResponse();
    }


    // public function manage(): JsonResponse
    // {
    //     // Retrieve active or non-canceled subscriptions with a future `ends_at`
    //     $activeSubscriptions = auth()->user()->subscriptions()
    //         ->where("is_active", 1)
    //         ->where(function ($query) {
    //             $query->where("is_cancelled", false)
    //                   ->orWhere(function ($subQuery) {
    //                       $subQuery->where("is_cancelled", true)
    //                               ->where("ends_at", ">", now());
    //                   });
    //         })
    //         ->get();
    
    //     // Prepare the response data with billing_cycle status
    //     $subscriptionsData = $activeSubscriptions->map(function ($subscription) {
    //         // Add the billing_cycle key to indicate if the subscription is within a valid billing period
    //         $subscription->billing_cycle = $subscription->ends_at && $subscription->ends_at > now();
    //         return $subscription;
    //     });
    
    //     // Get the minimum renewable date and calculate the next payment amount based on this date
    //     $nextPaymentDate = $activeSubscriptions->min("renewable_date");
    //     $nextPayment = $activeSubscriptions->where("renewable_date", $nextPaymentDate)->sum("amount");
    
    //     // Prepare response data
    //     $this->response_data["status"] = true;
    //     $this->response_data["data"] = [
    //         "subscriptions" => $subscriptionsData,
    //         "nextPaymentDate" => $nextPaymentDate,
    //         "nextPayment" => $nextPayment
    //     ];
    
    //     return $this->sendJsonResponse();
    // }
        


   public function manage(): JsonResponse
    {
        
        
        $user = auth()->user();
        // Determine which user's subscriptions to fetch
        $user = $user->is_admin ? $user : User::find($user->parent_id);
    
        if (!$user) {
            $this->response_data["status"] = false;
            $this->response_data["message"] = "User not found";
            return $this->sendJsonResponse();
        }
    
        // Retrieve subscriptions based on the new logic for both active and canceled-but-valid subscriptions
        $activeSubscriptions = $user->subscriptions()
            ->where("is_active", 1)
            ->where(function ($query) {
                $query->where("is_cancelled", false)
                    ->orWhere(function ($subQuery) {
                        $subQuery->where("is_cancelled", true)
                            ->where("ends_at", ">", now());
                    });
            })
            ->get();
            
            
    
        // Map active subscriptions to include the billing cycle status
        $subscriptionsData = $activeSubscriptions->map(function ($subscription) {
            $subscription->billing_cycle = $subscription->ends_at && $subscription->ends_at > now();
    
            if ($subscription->renewable_date) {
                $subscription->renewable_date = Carbon::parse($subscription->renewable_date)->format('M d, Y');
            }
    
            return $subscription;
        });
    
        // Filter active subscriptions to exclude those marked as cancelled
        $nonCancelledSubscriptions = $activeSubscriptions->where("is_cancelled", false);
    
        // Calculate the next payment date and payment amount from non-canceled subscriptions
        $nextPaymentDate = $nonCancelledSubscriptions->min("renewable_date");
        $formattedNextPaymentDate = $nextPaymentDate 
            ? Carbon::parse($nextPaymentDate)->format('M d, Y') 
            : null;
    
        $nextPayment = $nonCancelledSubscriptions->where("renewable_date", $nextPaymentDate)->sum("amount");
        $formattedNextPayment = number_format($nextPayment, 2, '.', ',');
    
        // Prepare response data
        $this->response_data["status"] = true;
        $this->response_data["data"] = [
            "subscriptions" => $subscriptionsData,
            "nextPaymentDate" => $formattedNextPaymentDate,
            "nextPayment" => $formattedNextPayment
        ];
    
        return $this->sendJsonResponse();
    }



    public function check()
    {
        
        $validator = Validator::make(request()->all(), [
            'slug' => 'required',
        ]);
        if ($validator->fails()){
            foreach ($validator->errors()->all() as $msg) {
                if (strlen(trim($msg)) > 1){
                    $this->response_data["message"] = $msg;
                    return $this->sendJsonResponse();
                }
            }
        }

        if(!(auth()->user()->hasSubscription(request()->slug)) ) {
            $this->response_data["message"] = "Please upgrade your membership.";
            $this->response_data["no_subscription"] = true;
            return $this->sendJsonResponse();
        }
        $this->response_data["message"] = "Subscription exists";
        $this->response_data["no_subscription"] = false;
        return $this->sendJsonResponse();
    }

    function checkStatus(){
       
        $data = Membership::all()->map(function ($membership){
            $membershipData["slug"] = $membership->slug;
            $membershipData["status"] = auth()->user()->hasSubscription($membership->slug);
            return $membershipData;
        });
        $this->response_data["data"] = $data;
        return $this->sendJsonResponse();
    }
    
    
    // apple and play store subscriptions code 
    
    private function getTimezoneFromCountryCode($countryCode)
    {
        $map = [
            'PK' => 'Asia/Karachi',
            'IN' => 'Asia/Kolkata',
            'US' => 'America/New_York',
            'AE' => 'Asia/Dubai',
            'GB' => 'Europe/London',
            'CA' => 'America/Toronto',
            'AU' => 'Australia/Sydney',
            'DE' => 'Europe/Berlin',
            'FR' => 'Europe/Paris',
            // Add more country codes as needed
        ];
    
        return $map[$countryCode] ?? config('app.timezone'); // fallback to default timezone
    }

//   public function handleRevenueCatWebhook(Request $request)
//     {
//         $data = $request->all(); // JSON data receive karein
    
//         // User ko email se find karna
//         $user = User::where('email', @$data['event']['app_user_id'])->first();
    
//         if (!$user) {
//             return response()->json(['message' => 'User not found'], 404);
//         }
    
//         // Platform identify karna
//         $platform = strtolower(@$data['event']['store']); // "PLAY_STORE" ya "APP_STORE"
        
//         if ($platform === 'play_store') {
            
//             $membership = Membership::where('play_store_product_id', @$data['event']['product_id'])->first();
         
//             if (!$membership) {
//                 return response()->json(['message' => 'Membership  not found OR Invalid Membership Id '], 404);
//             }
        
        
//             return $this->handleGoogleSubscription(@$data['event'], $user,$membership);
            
            
//         } elseif ($platform === 'app_store') {
            
//             $membership = Membership::where('app_store_product_id', @$data['event']['product_id'])->first();
         
//             if (!$membership) {
//                 return response()->json(['message' => 'Membership  not found OR Invalid Membership Id '], 404);
//             }
            
//             return $this->handleAppleSubscription(@$data['event'], $user,$membership);
//         }
        
        
    
//         return response()->json(['message' => 'Invalid platform'], 400);
//     }
    
    
    public function handleRevenueCatWebhook(Request $request)
    {
        $data = $request->all();
    
        $user = User::where('email', @$data['event']['app_user_id'])->first();
    
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }
    
        $platform = strtolower(@$data['event']['store']);
        $productId = @$data['event']['product_id'];
    
        if ($platform === 'play_store') {
            $membership = Membership::where(function ($q) use ($productId) {
                $q->where('play_store_monthly_product_id', $productId)
                  ->orWhere('play_store_yearly_product_id', $productId);
            })->first();
    
            if (!$membership) {
                return response()->json(['message' => 'Membership not found OR Invalid Membership Id'], 404);
            }
    
            return $this->handleGoogleSubscription(@$data['event'], $user, $membership, $platform, $productId);
    
        } elseif ($platform === 'app_store') {
            $membership = Membership::where(function ($q) use ($productId) {
                $q->where('app_store_monthly_product_id', $productId)
                  ->orWhere('app_store_yearly_product_id', $productId);
            })->first();
    
            if (!$membership) {
                return response()->json(['message' => 'Membership not found OR Invalid Membership Id'], 404);
            }
    
            return $this->handleAppleSubscription(@$data['event'], $user, $membership, $platform, $productId);
        }
    
        return response()->json(['message' => 'Invalid platform'], 400);
    }
    
     // Google Subscription Process
    // private function handleGoogleSubscription($data, $user, $membership)
    // {
        
        
    //      $now =  now();
        
       
    //     $subscription = Subscription::where('subscription_id', @$data['original_transaction_id'])
    //         ->where('platform', 'google')
    //         ->first();
    
    //     $status = $this->mapGoogleStatus(@$data['type']);
    
    //     $expires_at = isset($data['expiration_at_ms'])
    //         ? Carbon::createFromTimestampMs($data['expiration_at_ms'])->setTimezone('UTC')
    //         : null;
    
    //     $endsAt = $expires_at;
        
       
    
    //     if (!$subscription) {
            
    //         $datasubscription=[
    //             'user_id' => $user->id,
    //             'company_id' => @$user->company->id,
    //             'title' => @$data['entitlement_ids'][0],
    //             'membership_id' => $membership->id,
    //             'slug' => $membership->slug,
    //             'amount' => @$data['price'],
    //             'platform' => 'google',
    //             'renewable_date' => $expires_at,
    //             'subscription_id' => @$data['original_transaction_id'],
    //             'status' => $status,
    //             'ends_at' => $endsAt,
    //             'is_active' => $status === 'expired' ? 0 : 1,
    //             'is_cancelled' => 0,
    //             'cancelled_at' => null,
    //         ];
            
           
    //         $subscription = Subscription::create($datasubscription);
    
    //         $subscriptionName = @$data['entitlement_ids'][0];
    //         if ($subscriptionName == 'AI Profit- under construction') {
    //             $subscriptionName = "AI Profit";
    //         }
    
    //         $title = 'Billing Alert';
    //         $amount = number_format(@$data['price'], 2);
    //         $body = "You’ve been charged $$amount for $subscriptionName Subscription. Thank you for staying with EZ Estimater!";
    //         $user->notify(new FirebasePushNotification($title, $body));
    
    //     } else {
    //         $updateData = [
    //             'status' => $status,
    //             'ends_at' => $endsAt,
    //         ];
    
    //         $notifications = [
    //             'expired' => [
    //                 'is_active' => 0,
    //                 'title' => 'Subscription Expired',
    //                 'message' => 'subscription has been expired. Renew anytime to continue using EZ Estimater.'
    //             ],
    //             'cancelled' => [
    //                 'is_cancelled' => 1,
    //                 'cancelled_at' => Carbon::now('UTC'),
    //                 'message' => 'subscription has been canceled. Renew anytime to continue using EZ Estimater.'
    //             ]
    //         ];
    
    //         if (isset($notifications[$status])) {
    //             $updateData = array_merge($updateData, $notifications[$status]);
    //             $subscriptionName = @$data['entitlement_ids'][0];
    //             if ($subscriptionName == 'AI Profit- under construction') {
    //                 $subscriptionName = "AI Profit";
    //             }
    //             $body = "Your $subscriptionName {$notifications[$status]['message']}";
    //             $user->notify(new FirebasePushNotification($subscriptionName, $body));
    //         }
    
    //         $subscription->update($updateData);
    //     }
    
    //     return response()->json(['message' => 'Google Subscription Updated']);
    // }


    private function handleGoogleSubscription($data, $user, $membership, $platform, $productId)
    {
        $now = now();
        $status = $this->mapGoogleStatus(@$data['type']);
        $expires_at = isset($data['expiration_at_ms']) ? Carbon::createFromTimestampMs($data['expiration_at_ms'])->setTimezone('UTC') : null;
        $endsAt = $expires_at;
        $renewableType = $membership->getRenewableTypeForProduct($platform, $productId);
    
        $subscription = Subscription::where('subscription_id', @$data['original_transaction_id'])
            ->where('platform', 'google')
            ->first();
    
        if (!$subscription) {
            $datasubscription = [
                'user_id' => $user->id,
                'company_id' => @$user->company->id,
                'title' => @$data['entitlement_ids'][0],
                'membership_id' => $membership->id,
                'slug' => $membership->slug,
                'amount' => @$data['price'],
                'platform' => 'google',
                'renewable_type' => $renewableType,
                'renewable_date' => $expires_at,
                'subscription_id' => @$data['original_transaction_id'],
                'status' => $status,
                'ends_at' => $endsAt,
                'is_active' => $status === 'expired' ? 0 : 1,
                'is_cancelled' => 0,
                'cancelled_at' => null,
            ];
    
            $subscription = Subscription::create($datasubscription);
    
            $subscriptionName = @$data['entitlement_ids'][0];
            if ($subscriptionName === 'AI Profit- under construction') {
                $subscriptionName = "AI Profit";
            }
    
            $title = 'Billing Alert';
            $amount = number_format(@$data['price'], 2);
            $body = "You’ve been charged $$amount for $subscriptionName Subscription. Thank you for staying with EZ Estimater!";
            $user->notify(new FirebasePushNotification($title, $body));
    
        } else {
            $updateData = [
                'status' => $status,
                'ends_at' => $endsAt,
            ];
    
            $notifications = [
                'expired' => [
                    'is_active' => 0,
                    'title' => 'Subscription Expired',
                    'message' => 'subscription has been expired. Renew anytime to continue using EZ Estimater.'
                ],
                'cancelled' => [
                    'is_cancelled' => 1,
                    'cancelled_at' => Carbon::now('UTC'),
                    'message' => 'subscription has been canceled. Renew anytime to continue using EZ Estimater.'
                ]
            ];
    
            if (isset($notifications[$status])) {
                $updateData = array_merge($updateData, $notifications[$status]);
                $subscriptionName = @$data['entitlement_ids'][0];
                if ($subscriptionName === 'AI Profit- under construction') {
                    $subscriptionName = "AI Profit";
                }
                $body = "Your $subscriptionName {$notifications[$status]['message']}";
                $user->notify(new FirebasePushNotification($subscriptionName, $body));
            }
    
            $subscription->update($updateData);
        }
    
        return response()->json(['message' => 'Google Subscription Updated']);
    }

    
    // Apple Subscription Process
    // private function handleAppleSubscription($data, $user, $membership)
    // {
    //     $subscription = Subscription::where('subscription_id', $data['original_transaction_id'])
    //         ->where('platform', 'apple')
    //         ->first();
    
    //     $status = $this->mapAppleStatus($data['type']);
    
    //     $expires_at = isset($data['expiration_at_ms'])
    //         ? Carbon::createFromTimestampMs($data['expiration_at_ms'])->setTimezone('UTC')
    //         : null;
    
    //     $endsAt = $expires_at;
    
    //     if (!$subscription) {
    //         $subscription = Subscription::create([
    //             'user_id' => $user->id,
    //             'title' => $data['entitlement_ids'][0],
    //             'membership_id' => $membership->id,
    //             'slug' => $membership->slug,
    //             'amount' => $data['price'],
    //             'platform' => 'apple',
    //             'renewable_date' => $expires_at,
    //             'subscription_id' => $data['original_transaction_id'],
    //             'status' => $status,
    //             'ends_at' => $endsAt,
    //             'is_active' => $status === 'expired' ? 0 : 1,
    //             'is_cancelled' => 0,
    //             'cancelled_at' => null,
    //         ]);
    
    //         $title = 'Billing Alert';
    //         $body = "You’ve been charged $" . $data['price'] . ". Thank you for staying with EZ Estimater!";
    //         $user->notify(new FirebasePushNotification($title, $body));
    
    //     } else {
    //         $updateData = [
    //             'status' => $status,
    //             'ends_at' => $endsAt,
    //         ];
    
    //         $notifications = [
    //             'expired' => [
    //                 'is_active' => 0,
    //                 'title' => 'Subscription Expired',
    //                 'message' => 'subscription has been expired. Renew anytime to continue using EZ Estimater.'
    //             ],
    //             'cancelled' => [
    //                 'is_cancelled' => 1,
    //                 'cancelled_at' => Carbon::now('UTC'),
    //                 'title' => 'Subscription Canceled',
    //                 'message' => 'subscription has been canceled. Renew anytime to continue using EZ Estimater.'
    //             ]
    //         ];
    
    //         if (isset($notifications[$status])) {
    //             $updateData = array_merge($updateData, $notifications[$status]);
    //             $subscriptionName = @$data['entitlement_ids'][0];
    //             $body = "Your $subscriptionName {$notifications[$status]['message']}";
    //             $user->notify(new FirebasePushNotification($notifications[$status]['title'], $body));
    //         }
    
    //         $subscription->update($updateData);
    //     }
    
    //     return response()->json(['message' => 'Apple Subscription Updated']);
    // }
    
    private function handleAppleSubscription($data, $user, $membership, $platform, $productId)
{
    $status = $this->mapAppleStatus($data['type']);
    $expires_at = isset($data['expiration_at_ms']) ? Carbon::createFromTimestampMs($data['expiration_at_ms'])->setTimezone('UTC') : null;
    $endsAt = $expires_at;
    $renewableType = $membership->getRenewableTypeForProduct($platform, $productId);

    $subscription = Subscription::where('subscription_id', $data['original_transaction_id'])
        ->where('platform', 'apple')
        ->first();

    if (!$subscription) {
        $subscription = Subscription::create([
            'user_id' => $user->id,
            'title' => $data['entitlement_ids'][0],
            'membership_id' => $membership->id,
            'slug' => $membership->slug,
            'amount' => $data['price'],
            'platform' => 'apple',
            'renewable_type' => $renewableType,
            'renewable_date' => $expires_at,
            'subscription_id' => $data['original_transaction_id'],
            'status' => $status,
            'ends_at' => $endsAt,
            'is_active' => $status === 'expired' ? 0 : 1,
            'is_cancelled' => 0,
            'cancelled_at' => null,
        ]);

        $title = 'Billing Alert';
        $amount = number_format($data['price'], 2);
        $body = "You’ve been charged $$amount. Thank you for staying with EZ Estimater!";
        $user->notify(new FirebasePushNotification($title, $body));

    } else {
        $updateData = [
            'status' => $status,
            'ends_at' => $endsAt,
        ];

        $notifications = [
            'expired' => [
                'is_active' => 0,
                'title' => 'Subscription Expired',
                'message' => 'subscription has been expired. Renew anytime to continue using EZ Estimater.'
            ],
            'cancelled' => [
                'is_cancelled' => 1,
                'cancelled_at' => Carbon::now('UTC'),
                'title' => 'Subscription Canceled',
                'message' => 'subscription has been canceled. Renew anytime to continue using EZ Estimater.'
            ]
        ];

        if (isset($notifications[$status])) {
            $updateData = array_merge($updateData, $notifications[$status]);
            $subscriptionName = @$data['entitlement_ids'][0];
            $body = "Your $subscriptionName {$notifications[$status]['message']}";
            $user->notify(new FirebasePushNotification($notifications[$status]['title'], $body));
        }

        $subscription->update($updateData);
    }

    return response()->json(['message' => 'Apple Subscription Updated']);
}


    
    // Google Status Mapping
    // private function mapGoogleStatus($periodType)
    // {
       
    //     switch ($periodType) {
    //         case 'INITIAL_PURCHASE': return 'active';
    //         case 'CANCELLATION': return 'cancelled';
    //         case 'EXPIRATION': return 'expired';
    //         case 'EXPIRED': return 'expired';
    //         case 'RENEWAL' : return 'active';
    //         case 'TRIAL': return 'pending';
    //         default: return 'pending';
    //     }
    // }
    
    // // Apple Status Mapping
    // private function mapAppleStatus($type)
    // {
    //     switch ($type) {
    //          case 'INITIAL_PURCHASE': return 'active';
    //         case 'CANCELLATION': return 'cancelled';
    //         case 'EXPIRATION': return 'expired';
    //         case 'EXPIRED': return 'expired';
    //         case 'RENEWAL' : return 'active';
    //         case 'TRIAL': return 'pending';
    //         default: return 'pending';
    //     }
    // }
    
    private function mapGoogleStatus($periodType)
    {
        switch ($periodType) {
            case 'INITIAL_PURCHASE': return 'active';
            case 'CANCELLATION': return 'cancelled';
            case 'EXPIRATION': return 'expired';
            case 'EXPIRED': return 'expired';
            case 'RENEWAL' : return 'active';
            case 'TRIAL': return 'pending';
            default: return 'pending';
        }
    }
    
    private function mapAppleStatus($type)
    {
        switch ($type) {
            case 'INITIAL_PURCHASE': return 'active';
            case 'CANCELLATION': return 'cancelled';
            case 'EXPIRATION': return 'expired';
            case 'EXPIRED': return 'expired';
            case 'RENEWAL' : return 'active';
            case 'TRIAL': return 'pending';
            default: return 'pending';
        }
    }







}
