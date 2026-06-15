<?php
// webhook.php
//
// Use this sample code to handle webhook events in your integration.
//
// 1) Paste this code into a new file (webhook.php)
//
// 2) Install dependencies
//   composer require stripe/stripe-php
//
// 3) Run the server on http://localhost:4242
//   php -S localhost:4242

require 'vendor/autoload.php';

// The library needs to be configured with your account's secret key.
// Ensure the key is kept out of any version control system you might be using.
$stripe = new \Stripe\StripeClient('sk_test_...');

// This is your Stripe CLI webhook secret for testing your endpoint locally.
$endpoint_secret = 'whsec_8fa393d5523f47c9e1c68f9ad7b6730b633d89c6092793b99ed6364466d93631';

$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
$event = null;

try {
  $event = \Stripe\Webhook::constructEvent(
    $payload, $sig_header, $endpoint_secret
  );
} catch(\UnexpectedValueException $e) {
  // Invalid payload
  http_response_code(400);
  exit();
} catch(\Stripe\Exception\SignatureVerificationException $e) {
  // Invalid signature
  http_response_code(400);
  exit();
}

// Handle the event
switch ($event->type) {
  case 'customer.subscription.created':
    $subscription = $event->data->object;
  case 'customer.subscription.deleted':
    $subscription = $event->data->object;
  case 'customer.subscription.paused':
    $subscription = $event->data->object;
  case 'customer.subscription.pending_update_applied':
    $subscription = $event->data->object;
  case 'customer.subscription.pending_update_expired':
    $subscription = $event->data->object;
  case 'customer.subscription.resumed':
    $subscription = $event->data->object;
  case 'customer.subscription.trial_will_end':
    $subscription = $event->data->object;
  case 'customer.subscription.updated':
    $subscription = $event->data->object;
  case 'invoice.created':
    $invoice = $event->data->object;
  case 'invoice.deleted':
    $invoice = $event->data->object;
  case 'invoice.finalization_failed':
    $invoice = $event->data->object;
  case 'invoice.finalized':
    $invoice = $event->data->object;
  case 'invoice.marked_uncollectible':
    $invoice = $event->data->object;
  case 'invoice.overdue':
    $invoice = $event->data->object;
  case 'invoice.paid':
    $invoice = $event->data->object;
  case 'invoice.payment_action_required':
    $invoice = $event->data->object;
  case 'invoice.payment_failed':
    $invoice = $event->data->object;
  case 'invoice.payment_succeeded':
    $invoice = $event->data->object;
  case 'invoice.sent':
    $invoice = $event->data->object;
  case 'invoice.upcoming':
    $invoice = $event->data->object;
  case 'invoice.updated':
    $invoice = $event->data->object;
  case 'invoice.voided':
    $invoice = $event->data->object;
  case 'invoice.will_be_due':
    $invoice = $event->data->object;
  case 'subscription_schedule.aborted':
    $subscriptionSchedule = $event->data->object;
  case 'subscription_schedule.canceled':
    $subscriptionSchedule = $event->data->object;
  case 'subscription_schedule.completed':
    $subscriptionSchedule = $event->data->object;
  case 'subscription_schedule.created':
    $subscriptionSchedule = $event->data->object;
  case 'subscription_schedule.expiring':
    $subscriptionSchedule = $event->data->object;
  case 'subscription_schedule.released':
    $subscriptionSchedule = $event->data->object;
  case 'subscription_schedule.updated':
    $subscriptionSchedule = $event->data->object;
  // ... handle other event types
  default:
    echo 'Received unknown event type ' . $event->type;
}

http_response_code(200);