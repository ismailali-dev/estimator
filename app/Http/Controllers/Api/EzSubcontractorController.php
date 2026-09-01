<?php

namespace App\Http\Controllers\Api;

use App\Models\Estimate;
use App\Models\User;
use App\Notifications\FirebasePushNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class EzSubcontractorController extends ResponseController
{
    public function desync(Request $request): JsonResponse
    {
        $url = config('services.ezsubcontractor.desync_url');
        if (!$url) {
            $this->response_data['message'] = 'EZsubcontractor desync API is not configured.';
            return $this->sendJsonResponse(self::HTTP_INTERNAL_SERVER_ERROR);
        }

        $user = $request->user();
        $http = Http::acceptJson()->asJson()
            ->timeout((int) config('services.ezsubcontractor.timeout', 20));
        if ($token = config('services.ezsubcontractor.token')) {
            $http = $http->withToken($token);
        }

        try {
            $response = $http->post($url, [
                'source_user_id' => (int) $user->id,
                'email' => $user->email,
            ]);
        } catch (\Throwable $exception) {
            report($exception);
            $this->response_data['message'] = 'EZsubcontractor could not be reached. Please try again.';
            return $this->sendJsonResponse(self::HTTP_INTERNAL_SERVER_ERROR);
        }

        if (!$response->successful()) {
            $this->response_data['message'] = data_get($response->json(), 'message', 'EZsubcontractor rejected the desync request.');
            return $this->sendJsonResponse($response->status() >= 500 ? 502 : $response->status());
        }

        $this->response_data['status'] = true;
        $this->response_data['message'] = 'EZsubcontractor account desynced.';
        $this->response_data['data'] = ['exists' => false];
        return $this->sendJsonResponse();
    }

    public function accountStatus(Request $request): JsonResponse
    {
        $url = config('services.ezsubcontractor.status_url');
        if (!$url) {
            $this->response_data['message'] = 'EZsubcontractor account status API is not configured.';
            return $this->sendJsonResponse(self::HTTP_INTERNAL_SERVER_ERROR);
        }

        $user = $request->user();
        $http = Http::acceptJson()->asJson()
            ->timeout((int) config('services.ezsubcontractor.timeout', 20));
        if ($token = config('services.ezsubcontractor.token')) {
            $http = $http->withToken($token);
        }

        try {
            $response = $http->post($url, [
                'source_user_id' => (int) $user->id,
                'email' => $user->email,
            ]);
        } catch (\Throwable $exception) {
            report($exception);
            $this->response_data['message'] = 'EZsubcontractor could not be reached. Please try again.';
            return $this->sendJsonResponse(self::HTTP_INTERNAL_SERVER_ERROR);
        }

        if (!$response->successful()) {
            $this->response_data['message'] = data_get($response->json(), 'message', 'EZsubcontractor rejected the account status request.');
            return $this->sendJsonResponse($response->status() >= 500 ? 502 : $response->status());
        }

        $this->response_data['status'] = true;
        $this->response_data['message'] = 'EZsubcontractor account status fetched.';
        $remoteData = (array) data_get($response->json(), 'data', []);
        $this->response_data['data'] = [
            'exists' => (bool) data_get($remoteData, 'exists', false),
            'account_exists' => (bool) data_get($remoteData, 'account_exists', false),
            'is_linked_to_ezestimator' => (bool) data_get($remoteData, 'is_linked_to_ezestimator', false),
            'has_posted_jobs' => (bool) data_get($remoteData, 'has_posted_jobs', false),
            'posted_jobs_count' => (int) data_get($remoteData, 'posted_jobs_count', 0),
            'sync_status' => (string) data_get($remoteData, 'sync_status', 'not_found'),
        ];
        return $this->sendJsonResponse();
    }

    public function messageNotification(Request $request): JsonResponse
    {
        $configuredToken = (string) config('services.ezsubcontractor.token');
        $providedToken = (string) $request->bearerToken();
        if ($configuredToken === '' || $providedToken === '' || !hash_equals($configuredToken, $providedToken)) {
            $this->response_data['message'] = 'Unauthorized EZsubcontractor integration request.';
            return $this->sendJsonResponse(self::HTTP_UNAUTHORIZED);
        }

        $validator = Validator::make($request->all(), [
            'source_user_id' => 'required|integer',
            'source_estimate_id' => 'required|integer',
            'project_id' => 'required|integer',
            'sender_id' => 'required|integer',
            'sender_name' => 'required|string|max:255',
            'message_type' => 'required|in:text,attachment,json',
            'message_preview' => 'nullable|string|max:255',
        ]);
        if ($validator->fails()) {
            $this->response_data['message'] = $validator->errors()->first();
            return $this->sendJsonResponse(self::HTTP_BAD_REQUEST);
        }

        $user = User::find($request->input('source_user_id'));
        if (!$user) {
            $this->response_data['message'] = 'EZEstimator user not found.';
            return $this->sendJsonResponse(self::HTTP_NOT_FOUND);
        }

        $senderName = $request->input('sender_name');
        $body = $request->input('message_type') === 'attachment'
            ? $senderName . ' sent a file about your project.'
            : $senderName . ' sent a message about your project.';
        // Store the database notification during this request. The notification
        // class is queued by default, which caused rows to be missing whenever
        // the production queue worker was not running.
        $user->notifyNow(new FirebasePushNotification('New Project Message', $body));

        $this->response_data['status'] = true;
        $this->response_data['message'] = 'EZEstimator user notified.';
        return $this->sendJsonResponse(self::HTTP_ACCEPTED);
    }

    public function signup(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'password' => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required|string|min:8',
        ]);
        if ($validator->fails()) {
            $this->response_data['message'] = $validator->errors()->first();
            return $this->sendJsonResponse(self::HTTP_BAD_REQUEST);
        }

        $url = config('services.ezsubcontractor.signup_url');
        if (!$url) {
            $this->response_data['message'] = 'EZsubcontractor signup API is not configured.';
            return $this->sendJsonResponse(self::HTTP_INTERNAL_SERVER_ERROR);
        }

        $user = $request->user()->load('company');
        $payload = [
            'contractor' => [
                'source_user_id' => (int) $user->id,
                'source_company_id' => (int) $user->company_id,
                'email' => $user->email,
                'name' => trim((string) $user->first_name . ' ' . (string) $user->last_name),
                'company_name' => optional($user->company)->name,
                'phone' => $user->phone,
            ],
            'latitude' => $request->input('latitude'),
            'longitude' => $request->input('longitude'),
            'password' => $request->input('password'),
            'password_confirmation' => $request->input('password_confirmation'),
        ];

        $http = Http::acceptJson()->asJson()
            ->timeout((int) config('services.ezsubcontractor.timeout', 20))
            ->withHeaders(['Idempotency-Key' => hash('sha256', 'signup|' . $user->id)]);
        if ($token = config('services.ezsubcontractor.token')) {
            $http = $http->withToken($token);
        }

        try {
            $response = $http->post($url, $payload);
        } catch (\Throwable $exception) {
            report($exception);
            $this->response_data['message'] = 'EZsubcontractor could not be reached. Please try again.';
            return $this->sendJsonResponse(self::HTTP_INTERNAL_SERVER_ERROR);
        }

        if (!$response->successful()) {
            $this->response_data['message'] = data_get($response->json(), 'message', 'EZsubcontractor rejected the signup request.');
            $this->response_data['data'] = $response->json() ?: $response->body();
            return $this->sendJsonResponse($response->status() >= 500 ? 502 : $response->status());
        }

        $this->response_data['status'] = true;
        $this->response_data['message'] = 'EZsubcontractor account is ready.';
        $this->response_data['data'] = $response->json();
        return $this->sendJsonResponse();
    }

    public function trades(Request $request, $estimate): JsonResponse
    {
        $estimate = $this->ownedEstimate($request, $estimate);
        if (!$estimate) {
            return $this->notFoundResponse();
        }

        $this->response_data['status'] = true;
        $this->response_data['data'] = $this->buildPayload($estimate);
        return $this->sendJsonResponse();
    }

    public function publish(Request $request, $estimate): JsonResponse
    {
        $estimate = $this->ownedEstimate($request, $estimate);
        if (!$estimate) {
            return $this->notFoundResponse();
        }

        $validator = Validator::make($request->all(), [
            'trade_ids' => 'required|array|min:1',
            'trade_ids.*' => 'required|integer|distinct',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'estimate_due_date' => 'required|date',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'contact_options' => 'nullable|array|min:1',
            'contact_options.*' => 'required|in:chat,email,phone|distinct',
        ]);
        if ($validator->fails()) {
            $this->response_data['message'] = $validator->errors()->first();
            return $this->sendJsonResponse(self::HTTP_BAD_REQUEST);
        }

        $requestedIds = collect($request->input('trade_ids'))->map(fn ($id) => (int) $id)->unique();
        $payload = $this->buildPayload(
            $estimate,
            $requestedIds->all(),
            (float) $request->input('latitude'),
            (float) $request->input('longitude'),
            (string) $request->input('estimate_due_date'),
            (string) $request->input('start_date'),
            (string) $request->input('end_date'),
            $request->input('contact_options', ['chat', 'email', 'phone'])
        );
        $foundIds = collect($payload['trades'])->pluck('id');
        $missingIds = $requestedIds->diff($foundIds)->values();
        if ($missingIds->isNotEmpty()) {
            $this->response_data['message'] = 'One or more selected trades do not belong to this estimate.';
            $this->response_data['data'] = ['invalid_trade_ids' => $missingIds];
            return $this->sendJsonResponse(self::HTTP_BAD_REQUEST);
        }

        $url = config('services.ezsubcontractor.publish_url');
        if (!$url) {
            $this->response_data['message'] = 'EZsubcontractor API is not configured.';
            return $this->sendJsonResponse(self::HTTP_INTERNAL_SERVER_ERROR);
        }

        $http = Http::acceptJson()->asJson()
            ->timeout((int) config('services.ezsubcontractor.timeout', 20))
            ->withHeaders(['Idempotency-Key' => hash('sha256', $estimate->id . '|' . $requestedIds->sort()->implode(','))]);
        if ($token = config('services.ezsubcontractor.token')) {
            $http = $http->withToken($token);
        }

        try {
            $response = $http->post($url, $payload);
        } catch (\Throwable $exception) {
            report($exception);
            $this->response_data['message'] = 'EZsubcontractor could not be reached. Please try again.';
            return $this->sendJsonResponse(self::HTTP_INTERNAL_SERVER_ERROR);
        }

        if (!$response->successful()) {
            $this->response_data['message'] = 'EZsubcontractor rejected the job.';
            $this->response_data['data'] = [
                'remote_status' => $response->status(),
                'remote_response' => $response->json() ?: $response->body(),
            ];
            return $this->sendJsonResponse($response->status() >= 500 ? 502 : self::HTTP_BAD_REQUEST);
        }

        $this->response_data['status'] = true;
        $this->response_data['message'] = 'Selected trades posted to EZsubcontractor successfully.';
        $this->response_data['data'] = [
            'estimate_id' => $estimate->id,
            'posted_trade_ids' => $foundIds->values(),
            'ezsubcontractor' => $response->json(),
        ];
        return $this->sendJsonResponse();
    }

    private function ownedEstimate(Request $request, $estimateId): ?Estimate
    {
        return Estimate::with(['customer', 'user.company', 'sheet.code.ProductGroup'])
            ->where('id', $estimateId)
            ->where('company_id', $request->user()->company_id)
            ->first();
    }

    private function buildPayload(
        Estimate $estimate,
        ?array $tradeIds = null,
        ?float $latitude = null,
        ?float $longitude = null,
        ?string $estimateDueDate = null,
        ?string $startDate = null,
        ?string $endDate = null,
        ?array $contactOptions = null
    ): array
    {
        $selectedIds = $tradeIds === null ? null : collect($tradeIds)->map(fn ($id) => (int) $id)->unique();
        $sheets = $estimate->sheet->filter(function ($sheet) use ($selectedIds) {
            $tradeId = optional(optional($sheet->code)->ProductGroup)->id;
            return $sheet->quantity > 0 && $tradeId
                && ($selectedIds === null || $selectedIds->contains((int) $tradeId));
        });

        $trades = $sheets->groupBy(fn ($sheet) => optional(optional($sheet->code)->ProductGroup)->id)
            ->map(function ($items) {
                $group = optional($items->first()->code)->ProductGroup;
                return [
                    'id' => (int) $group->id,
                    'name' => $group->group_name,
                    'specifications' => $items->values()->map(fn ($sheet) => [
                        'estimate_sheet_id' => (int) $sheet->id,
                        'description' => (string) optional($sheet->code)->description,
                        'quantity' => $sheet->quantity,
                        'unit' => $sheet->unit,
                    ])->all(),
                ];
            })->values()->all();

        $customer = $estimate->customer;
        return [
            'source' => 'ezestimater',
            'source_estimate_id' => (int) $estimate->id,
            'job_number' => $estimate->key ?: (string) $estimate->id,
            'title' => 'Estimate ' . ($estimate->key ?: $estimate->id),
            'description' => (string) $estimate->estimate_scope,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'street' => optional($customer)->address,
            'city' => optional($customer)->customer_city ?: optional($customer)->city,
            'state' => optional($customer)->customer_state ?: optional($customer)->state,
            'zip' => optional($customer)->zip_code,
            'estimate_due_date' => $estimateDueDate,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'contact_options' => $contactOptions ?? ['chat', 'email', 'phone'],
            'contractor' => [
                'source_user_id' => (int) $estimate->user_id,
                'source_company_id' => (int) $estimate->company_id,
                'email' => optional($estimate->user)->email,
                'name' => trim((string) optional($estimate->user)->first_name . ' ' . (string) optional($estimate->user)->last_name),
                'company_name' => optional(optional($estimate->user)->company)->name,
                'phone' => optional($estimate->user)->phone,
            ],
            'customer' => [
                'name' => $customer ? ($customer->full_name ?: $customer->name) : null,
                'email' => optional($customer)->email,
                'phone' => optional($customer)->phone,
                'address' => optional($customer)->address,
            ],
            'trades' => $trades,
        ];
    }

    private function notFoundResponse(): JsonResponse
    {
        $this->response_data['message'] = 'Estimate not found.';
        return $this->sendJsonResponse(self::HTTP_NOT_FOUND);
    }
}
