<?php

namespace App\Services;


use Stripe\Stripe;

use Stripe\Customer;
use Stripe\Subscription as StripeSubscription;
use Stripe\PaymentMethod;
use App\Models\Subscription;

class StripeService
{

    public function __construct()
    {
        Stripe::setApiKey(env('STRIPE_SECRET'));
        
       
    }

    function getOrCreateCustomer(){
        try {
            $user = auth()->user();
            if(!empty($user->stripe_cust_id)){
                return $user->stripe_cust_id;
            }else{
                $customer = Customer::create([
                    'email' => auth()->user()->email,
                ]);
                $user->stripe_cust_id = $customer->id;
                $user->save();
            }
            return $user->stripe_cust_id;
        }catch (\Exception $e){
            throw new \Exception($e->getMessage());
        }
    }


    function attachPaymentMethod($paymentToken){
        try {
            
            
            $user = auth()->user();
            $customer = Customer::retrieve($user->stripe_cust_id);

            $paymentMethod = PaymentMethod::create([
                'type' => 'card',
                'card' => [
                    'token' => $paymentToken,//'tok_1OxovuItMIhfPIHRvjf3CmlX', //$paymentToken // Token representing the card details
                ],
            ]);
            $paymentMethod->attach(['customer' => $customer->id]);
            $customer->invoice_settings->default_payment_method = $paymentMethod->id;
            $customer->save();
            
            
        }catch (\Exception $e){
            throw new \Exception($e->getMessage());
        }
    }
    
    
    // Set a specific payment method as the default
    function setDefaultPaymentMethod($paymentMethodId)
    {
        try {
            $user = auth()->user();
            $customer = Customer::retrieve($user->stripe_cust_id);

            // Attach the payment method to the customer
            $paymentMethod = PaymentMethod::retrieve($paymentMethodId);
            $paymentMethod->attach(['customer' => $customer->id]);

            // Set the payment method as the default
            $customer->invoice_settings->default_payment_method = $paymentMethod->id;
            $customer->save();

            return $paymentMethod;
        } catch (\Exception $e) {
            throw new \Exception("Error setting default payment method: " . $e->getMessage());
        }
    }
    
    
    
    

    // Get all payment methods attached to the customer
    function getMyPaymentMethods()
    {
        try {
            $user = auth()->user();
            $customer = Customer::retrieve($user->stripe_cust_id);

            $paymentMethods = PaymentMethod::all([
                'customer' => $customer->id,
                'type' => 'card',  // Only retrieve card payment methods
            ]);

           return $paymentMethods->data; // Return the list of payment methods
        } catch (\Exception $e) {
            throw new \Exception("Error retrieving payment methods: " . $e->getMessage());
        }
    }
    
    public function getPaymentMethodById($paymentMethodId)
{
    try {
        // Retrieve the payment method from Stripe
        $paymentMethod = PaymentMethod::retrieve($paymentMethodId);

        // Return the payment method details
        return [
            'id' => $paymentMethod->id,
            'brand' => $paymentMethod->card->brand,
            'last4' => $paymentMethod->card->last4,
            'exp_month' => $paymentMethod->card->exp_month,
            'exp_year' => $paymentMethod->card->exp_year,
        ];
    } catch (\Exception $e) {
        throw new \Exception("Error retrieving payment method: " . $e->getMessage());
    }
}
    
    public function getCustomerDefaultPaymentMethod()
    {
        try {
            $user = auth()->user();
    
            if (empty($user->stripe_cust_id)) {
                throw new \Exception("No Stripe customer found for the user.");
            }
    
            // Retrieve the customer details
            $customer = \Stripe\Customer::retrieve($user->stripe_cust_id);
    
            // Return the default payment method ID if set
            return $customer->invoice_settings->default_payment_method ?? null;
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }
    

    function createSubscription($priceId){
        try {
            $user = auth()->user();
            $subscription = StripeSubscription::create([
                'customer' => $user->stripe_cust_id,
                'items' => [
                    [
                        'price' => $priceId,//"price_1OxceLItMIhfPIHRyfyWmJvw", // Stripe price ID associated with the product
                    ],
                ],
            ]);
            return $subscription;
        }catch (\Exception $e){
            throw new \Exception($e->getMessage());
        }
    }

    function cancelSubscription($subscriptionId) {
        try {
            // Retrieve and cancel the subscription
            $subscription = StripeSubscription::retrieve($subscriptionId);
         
        
          if ($subscription->status === 'canceled') {
              
               return $subscription;
               
          }
          
          $subscription->cancel();
            // Cancel it and set cancellation at the period end
            
            
            // Return the subscription object to access current_period_end and other details
            return $subscription;
    
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }
    
    
  public function reactivateSubscription($subscriptionId)
{
    try {
        // Retrieve the subscription from Stripe
        $subscription = StripeSubscription::retrieve($subscriptionId);

        // Check if the subscription is canceled
        if ($subscription->status === 'canceled') {
          
            // Compare the expiration date with the current timestamp
            if (isset($subscription->current_period_end) && $subscription->current_period_end <= now()->timestamp) {
                 
                $localSubscription = Subscription::where('id', $subscriptionId)->first();
                if ($localSubscription) {
                    $localSubscription->update([
                        "is_cancelled" => false,
                        "cancelled_at" => null,
                        "ends_at" => null, // Reset the end date as it's now active
                    ]);
                }

                return true; // Reactivation successful
            }

            // If still within the current period, check if it can be updated
            if ($subscription->cancel_at_period_end) {
                // Reactivate the subscription by disabling cancellation at period end
                $subscription->cancel_at_period_end = false;
                $subscription->save();
                return true; // Reactivation successful
            }

            return true; 
        }


        return false; // Subscription is not canceled, no action needed
        
    } catch (\Exception $e) {
        // Handle exceptions and log the error
        throw new \Exception("Error reactivating subscription: " . $e->getMessage());
    }
}



public function createStripeAccount($email)
{
    try {
        // Create a new Stripe account
        $account = \Stripe\Account::create([
            'type' => 'standard',
            'country' => 'US', // Adjust according to your country
            'email' => $email,
        ]);

        return $account; // Return account details
    } catch (\Exception $e) {
        // Log or handle the exception
        throw new \Exception("Error creating Stripe account: " . $e->getMessage());
    }
}

    
   public function createAccountLink($accountId)
{
    try {
        // Create an AccountLink to redirect the contractor for connection
        $accountLink = \Stripe\AccountLink::create([
            'account' => $accountId,
            'refresh_url' => route('contractor.stripe.connect'), // URL to redirect after failure
            'return_url' => route('contractor.stripe.callback'),  // URL to redirect after success
            'type' => 'account_onboarding',
        ]);

        return $accountLink->url; // Return URL to redirect contractor to Stripe
    } catch (\Exception $e) {
        // Log or handle the exception
        throw new \Exception("Error creating account link: " . $e->getMessage());
    }
}

public function processPayment($contractor, $amount, $paymentMethodId)
{
    try {
        // Create a PaymentIntent to charge the customer
        $paymentIntent = \Stripe\PaymentIntent::create([
            'amount' => $amount, // Amount in cents
            'currency' => 'usd', // Adjust based on the contractor's currency
            'payment_method' => $paymentMethodId,
            'confirm' => true,
            'transfer_data' => [
                'destination' => $contractor->stripe_account_id, // Contractor's Stripe account ID
            ],
        ]);

        return $paymentIntent; // Return payment intent details
    } catch (\Exception $e) {
        // Log or handle the exception
        throw new \Exception("Error processing payment: " . $e->getMessage());
    }
}


}
