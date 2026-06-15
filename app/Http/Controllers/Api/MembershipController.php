<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Membership;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\Customer;
use Stripe\Subscription as StripeSubscription;
use Stripe\PaymentMethod;

class MembershipController extends ResponseController
{
    //

    public function index(): JsonResponse
    {
        $memberships = Membership::all();
    
        $user = auth()->user();
        
        foreach ($memberships as $membership) {
            $membership->is_subscribed = $user ? $user->hasSubscription($membership->slug) : false;
        }
    
        $this->response_data["status"] = true;
        $this->response_data["data"] = $memberships;
    
        return $this->sendJsonResponse();
    }
    
 public function checkSubscriptions(Request $request): JsonResponse
{
    // Validate request
    $request->validate([
        'product_ids' => 'required|array',
        'product_ids.*' => 'required|string'
    ]);

    $user = auth()->user();
    $results = [];

    // Preload all active subscriptions for the user, grouped by slug + type
    $userSubscriptions = $user
        ? $user->subscriptions()
            ->where('is_active', 1)
            ->where(function ($query) {
                $query->where(function ($q) {
                    $q->where('is_cancelled', 0)
                      ->where(function ($q2) {
                          $q2->whereNull('ends_at')
                             ->orWhere('ends_at', '>=', now());
                      });
                })->orWhere(function ($q) {
                    $q->where('is_cancelled', 1)
                      ->where('ends_at', '>=', now());
                });
            })
            ->get()
            ->groupBy(function ($sub) {
                return $sub->slug . '_' . $sub->renewable_type; // unique key per slug/type
            })
        : collect();

    foreach ($request->product_ids as $productId) {
        // Determine subscription type from product ID
        $type = str_contains($productId, 'mon_') ? 'month' : 'year';

        // Find the membership by matching product ID
        $membership = Membership::where(function ($query) use ($productId) {
            $query->where('app_store_yearly_product_id', $productId)
                  ->orWhere('app_store_monthly_product_id', $productId)
                  ->orWhere('play_store_yearly_product_id', $productId)
                  ->orWhere('play_store_monthly_product_id', $productId);
        })->first();

        // If membership not found, mark as unsubscribed
        if (!$membership) {
            $results[] = [
                'product_id' => $productId,
                'membership_slug' => null,
                'type' => $type,
                'is_subscribed' => false,
                'is_admin_allowed' => false,
            ];
            continue;
        }

        // Lookup subscription from preloaded grouped subscriptions
        $key = $membership->slug . '_' . $type;
        $subscription = $userSubscriptions->get($key)?->first();

        $results[] = [
            'product_id' => $productId,
            'membership_slug' => $membership->slug,
            'type' => $type,
            'is_subscribed' => $subscription ? true : false,
            'is_admin_allowed' => $subscription ? (bool)$subscription->is_admin_allowed : false,
        ];
    }

    $this->response_data["status"] = true;
    $this->response_data["data"] = $results;

    return $this->sendJsonResponse();
}
    

    public function membershipById(Request $request):JsonResponse
    {
        $memberships = Membership::whereIn("id",$request->id)->get();
        $this->response_data["status"] = true;
        $this->response_data["data"] = [
            "memberships" => $memberships,
            "total" => $memberships->sum("amount")
        ];
        return  $this->sendJsonResponse();
    }

    public function testStripe(Request $request)
    {
        $paymentMethod = null;
        Stripe::setApiKey(env('STRIPE_SECRET'));
        $customer = Customer::retrieve("cus_PnDQw4mgpV47hC");



/*
        $paymentMethod = PaymentMethod::create([
            'type' => 'card',
            'card' => [
                'token' => 'tok_1OxovuItMIhfPIHRvjf3CmlX', // Token representing the card details
            ],
        ]);

        $paymentMethod->attach(['customer' => $customer->id]);

        $customer->invoice_settings->default_payment_method = $paymentMethod->id;

        $customer->invoice_settings->default_payment_method = $paymentMethod->id;
        $customer->save();*/
//
//        $subscription = null;
        $subscription = StripeSubscription::create([
            'customer' => $customer->id,
            'items' => [
                [
                    'price' => "price_1OxceLItMIhfPIHRyfyWmJvw", // Stripe price ID associated with the product
                ],
            ],
        ]);

//        $customer = Customer::create([
//            'email' => auth()->user()->email,
//        ]);
        dd($subscription,$paymentMethod,$customer);
    }






}
