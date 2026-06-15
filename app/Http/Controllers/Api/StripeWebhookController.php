<?php

namespace App\Http\Controllers\Api;

use App\Models\Estimate;
use App\Models\EstimateType;
use App\Models\Subscription;
use App\Models\User;
use App\Services\DownloadEmailService;
use App\Services\EstimateService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class StripeWebhookController extends ResponseController
{

    public function webhook(Request $request){
        $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));

        // This is your Stripe CLI webhook secret for testing your endpoint locally.
        $endpoint_secret = 'whsec_8fa393d5523f47c9e1c68f9ad7b6730b633d89c6092793b99ed6364466d93631';

        $payload = @file_get_contents('php://input');
        $payload = json_decode($payload,true);
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
        $event = null;
        // Handle the event
        \Log::info(print_r("=================================",1));
        \Log::info(print_r($payload["type"],1));
        \Log::info(print_r("=================================",1));

/*        switch ($payload["type"]) {
            case 'customer.subscription.created':
                $subscription = $payload["data"]["object"];
                \Log::info(print_r($subscription,1));
            case 'customer.subscription.deleted':
                $subscription = $payload["data"]["object"];
                //$this->cancelSubscrition($subscription);
                \Log::info(print_r($subscription,1));
            case 'customer.subscription.paused':
                $subscription = $payload["data"]["object"];
                \Log::info(print_r($subscription,1));
            case 'customer.subscription.pending_update_applied':
                $subscription = $payload["data"]["object"];
                \Log::info(print_r($subscription,1));
            case 'customer.subscription.pending_update_expired':
                $subscription = $payload["data"]["object"];
                \Log::info(print_r($subscription,1));
            case 'customer.subscription.resumed':
                $subscription = $payload["data"]["object"];
                \Log::info(print_r($subscription,1));
            case 'customer.subscription.trial_will_end':
                $subscription = $payload["data"]["object"];
                \Log::info(print_r($subscription,1));
            case 'customer.subscription.updated':
                $subscription = $payload["data"]["object"];
                \Log::info(print_r($subscription,1));
            case 'invoice.created':
                $invoice = $payload["data"]["object"];
                \Log::info(print_r($invoice,1));
            case 'invoice.deleted':
                $invoice = $payload["data"]["object"];
                \Log::info(print_r($invoice,1));
            case 'invoice.finalization_failed':
                $invoice = $payload["data"]["object"];
                \Log::info(print_r($invoice,1));
            case 'invoice.finalized':
                $invoice = $payload["data"]["object"];
                \Log::info(print_r($invoice,1));
            case 'invoice.marked_uncollectible':
                $invoice = $payload["data"]["object"];
                \Log::info(print_r($invoice,1));
            case 'invoice.overdue':
                $invoice = $payload["data"]["object"];
                \Log::info(print_r($invoice,1));
            case 'invoice.paid':
                $invoice = $payload["data"]["object"];
                \Log::info(print_r($invoice,1));
            case 'invoice.payment_action_required':
                $invoice = $payload["data"]["object"];
                \Log::info(print_r($invoice,1));
            case 'invoice.payment_failed':
                $invoice = $payload["data"]["object"];
                \Log::info(print_r($invoice,1));
            case 'invoice.payment_succeeded':
                $invoice = $payload["data"]["object"];
                \Log::info(print_r($invoice,1));
            case 'invoice.sent':
                $invoice = $payload["data"]["object"];
                \Log::info(print_r($invoice,1));
            case 'invoice.upcoming':
                $invoice = $payload["data"]["object"];
                \Log::info(print_r($invoice,1));
            case 'invoice.updated':
                $invoice = $payload["data"]["object"];
                \Log::info(print_r($invoice,1));
            case 'invoice.voided':
                $invoice = $payload["data"]["object"];
                \Log::info(print_r($invoice,1));
            case 'invoice.will_be_due':
                $invoice = $payload["data"]["object"];
                \Log::info(print_r($invoice,1));
            case 'subscription_schedule.aborted':
                $subscriptionSchedule = $payload["data"]["object"];
                \Log::info(print_r($subscriptionSchedule,1));
            case 'subscription_schedule.canceled':
                $subscriptionSchedule = $payload["data"]["object"];
                \Log::info(print_r($subscriptionSchedule,1));

            case 'subscription_schedule.completed':
                $subscriptionSchedule = $payload["data"]["object"];
                \Log::info(print_r($subscriptionSchedule,1));

            case 'subscription_schedule.created':
                $subscriptionSchedule = $payload["data"]["object"];
                \Log::info(print_r($subscriptionSchedule,1));

            case 'subscription_schedule.expiring':
                $subscriptionSchedule = $payload["data"]["object"];
                \Log::info(print_r($subscriptionSchedule,1));

            case 'subscription_schedule.released':
                $subscriptionSchedule = $payload["data"]["object"];
                \Log::info(print_r($subscriptionSchedule,1));

            case 'subscription_schedule.updated':
                $subscriptionSchedule = $payload["data"]["object"];
                \Log::info(print_r($subscriptionSchedule,1));

            // ... handle other event types
            default:
                echo 'Received unknown event type ' . $payload["type"];
        }*/




        http_response_code(200);

    }


    public function cancelSubscrition($subscription)
    {
        $subscription = Subscription::where("subscription_id",$subscription["id"])->first();
        if($subscription){
            $subscription->update([
                "is_cancelled" => true,
                "cancelled_at" => Carbon::now()
            ]);
        }

    }
}
