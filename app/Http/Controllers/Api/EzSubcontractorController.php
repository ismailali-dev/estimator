<?php

namespace App\Http\Controllers\Api;

use App\Models\Estimate;
use App\Models\SettingDocument;
use App\Models\User;
use App\Models\UserInvoiceSetting;
use App\Notifications\FirebasePushNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class EzSubcontractorController extends ResponseController
{
    public function sync(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'sometimes|required|email|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);
        if ($validator->fails()) {
            $this->response_data['message'] = $validator->errors()->first();
            return $this->sendJsonResponse(self::HTTP_BAD_REQUEST);
        }

        $url = config('services.ezsubcontractor.sync_url');
        if (!$url) {
            $this->response_data['message'] = 'EZsubcontractor sync API is not configured.';
            return $this->sendJsonResponse(self::HTTP_INTERNAL_SERVER_ERROR);
        }

        $user = $request->user()->load('company');
        $http = Http::acceptJson()->asJson()
            ->timeout((int) config('services.ezsubcontractor.timeout', 20));
        if ($token = config('services.ezsubcontractor.token')) {
            $http = $http->withToken($token);
        }

        try {
            $response = $http->post($url, [
                'source_user_id' => (int) $user->id,
                'source_company_id' => (int) $user->company_id,
                'email' => $request->input('email', $user->email),
                'name' => trim((string) $user->first_name . ' ' . (string) $user->last_name),
                'company_name' => optional($user->company)->name,
                'phone' => $user->phone,
                'profile_image' => $this->profileImageUrl($user),
                'latitude' => $request->input('latitude'),
                'longitude' => $request->input('longitude'),
            ]);
        } catch (\Throwable $exception) {
            report($exception);
            $this->response_data['message'] = 'EZsubcontractor could not be reached. Please try again.';
            return $this->sendJsonResponse(self::HTTP_INTERNAL_SERVER_ERROR);
        }

        if (!$response->successful()) {
            $this->response_data['message'] = data_get($response->json(), 'message', 'EZsubcontractor rejected the sync request.');
            $this->response_data['data'] = $response->json() ?: null;
            return $this->sendJsonResponse($response->status() >= 500 ? 502 : $response->status());
        }

        $this->response_data['status'] = true;
        $this->response_data['message'] = 'EZsubcontractor account synced.';
        $remoteData = (array) data_get($response->json(), 'data', []);
        $this->response_data['data'] = [
            'exists' => true,
            'account_exists' => true,
            'is_linked_to_ezestimator' => true,
            'account_created' => (bool) data_get($remoteData, 'account_created', false),
            'credentials_email_sent' => (bool) data_get($remoteData, 'credentials_email_sent', false),
            'subscription_created' => (bool) data_get($remoteData, 'subscription_created', false),
            'subscription' => data_get($remoteData, 'subscription'),
        ];
        return $this->sendJsonResponse();
    }

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

    public function checkEmail(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
        ]);
        if ($validator->fails()) {
            $this->response_data['message'] = $validator->errors()->first();
            return $this->sendJsonResponse(self::HTTP_BAD_REQUEST);
        }

        $url = config('services.ezsubcontractor.check_email_url');
        if (!$url) {
            $this->response_data['message'] = 'EZsubcontractor email check API is not configured.';
            return $this->sendJsonResponse(self::HTTP_INTERNAL_SERVER_ERROR);
        }

        $email = $request->input('email');
        $http = Http::acceptJson()->asJson()
            ->timeout((int) config('services.ezsubcontractor.timeout', 20));
        if ($token = config('services.ezsubcontractor.token')) {
            $http = $http->withToken($token);
        }

        try {
            $response = $http->post($url, ['email' => $email]);
        } catch (\Throwable $exception) {
            report($exception);
            $this->response_data['message'] = 'EZsubcontractor could not be reached. Please try again.';
            return $this->sendJsonResponse(self::HTTP_INTERNAL_SERVER_ERROR);
        }

        if (!$response->successful()) {
            $this->response_data['message'] = data_get($response->json(), 'message', 'EZsubcontractor rejected the email check request.');
            return $this->sendJsonResponse($response->status() >= 500 ? 502 : $response->status());
        }

        $remoteData = (array) data_get($response->json(), 'data', []);
        $exists = (bool) data_get($remoteData, 'exists', false);
        $accountType = strtolower(trim((string) data_get($remoteData, 'account_type', '')));

        if ($exists && in_array($accountType, ['subcontractor', 'affiliate'], true)) {
            $this->response_data['message'] = 'This email already exists as a subcontractor or Affiliate.';
            $this->response_data['data'] = [
                'exists' => true,
                'account_type' => $accountType,
                'can_sync' => false,
            ];
            return $this->sendJsonResponse(409);
        }

        if ($exists && $accountType === 'general_contractor') {
            $this->response_data['status'] = true;
            $this->response_data['message'] = 'This email already exists as a General Contractor on EZsubcontractor. You can log in with your existing password or set a new one.';
            $this->response_data['data'] = [
                'exists' => true,
                'account_type' => 'general_contractor',
                'can_sync' => true,
                'can_login_with_existing_password' => true,
                'can_set_new_password' => true,
            ];
            return $this->sendJsonResponse();
        }

        $this->response_data['status'] = true;
        $this->response_data['message'] = 'Email is available to sync with EZsubcontractor.';
        $this->response_data['data'] = [
            'exists' => false,
            'account_type' => null,
            'can_sync' => true,
        ];
        return $this->sendJsonResponse();
    }

    public function signup(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'sometimes|required|email|max:255',
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
                'email' => $request->input('email', $user->email),
                'name' => trim((string) $user->first_name . ' ' . (string) $user->last_name),
                'company_name' => optional($user->company)->name,
                'phone' => $user->phone,
                'profile_image' => $this->profileImageUrl($user),
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
        $this->response_data['data']['attachments'] = $this->estimateAttachments($estimate)->map(fn ($document) => [
            'id' => (int) $document->id,
            'file_url' => Storage::disk('public')->url($document->file_path),
            'file_type' => $document->file_type,
            'description' => $document->document_name,
        ])->values()->all();
        return $this->sendJsonResponse();
    }

    public function publish(Request $request, $estimate): JsonResponse
    {
        $estimate = $this->ownedEstimate($request, $estimate);
        if (!$estimate) {
            return $this->notFoundResponse();
        }

        $validator = Validator::make($request->all(), [
            'trade_ids' => 'required_without:estimate_sheet_id|prohibits:estimate_sheet_id|array|min:1',
            'trade_ids.*' => 'required|integer|distinct',
            'estimate_sheet_id' => is_array($request->input('estimate_sheet_id'))
                ? 'required_without:trade_ids|array|min:1'
                : 'required_without:trade_ids|integer|min:1',
            'estimate_sheet_id.*' => 'required|integer|min:1|distinct',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'estimate_due_date' => 'required|date',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'contact_options' => 'nullable|array|min:1',
            'contact_options.*' => 'required|in:chat,email,phone|distinct',
            'attachments' => 'nullable|array|max:10',
            'attachments.*' => 'required|array',
            'attachments.*.file' => 'required|file|mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx,txt,csv,zip|max:10240',
            'attachments.*.description' => 'nullable|string|max:1000',
        ]);
        if ($validator->fails()) {
            $this->response_data['message'] = $validator->errors()->first();
            return $this->sendJsonResponse(self::HTTP_BAD_REQUEST);
        }

        $sheetIds = $request->filled('estimate_sheet_id')
            ? collect((array) $request->input('estimate_sheet_id'))->map(fn ($id) => (int) $id)->values()
            : null;
        $sheetId = $sheetIds !== null && !is_array($request->input('estimate_sheet_id')) ? $sheetIds->first() : null;
        $requestedIds = collect($request->input('trade_ids', []))->map(fn ($id) => (int) $id)->unique();
        $payload = $this->buildPayload(
            $estimate,
            $sheetIds === null ? $requestedIds->all() : null,
            (float) $request->input('latitude'),
            (float) $request->input('longitude'),
            (string) $request->input('estimate_due_date'),
            (string) $request->input('start_date'),
            (string) $request->input('end_date'),
            $request->input('contact_options', ['chat', 'email', 'phone']),
            $sheetIds === null ? null : $sheetIds->all()
        );
        if ($sheetIds !== null) {
            $foundSheetIds = collect($payload['trades'])->flatMap(fn ($trade) => $trade['specifications'])->pluck('estimate_sheet_id');
            $missingSheetIds = $sheetIds->diff($foundSheetIds)->values();
            if ($missingSheetIds->isNotEmpty()) {
                $this->response_data['message'] = 'One or more selected specifications do not belong to this estimate or are not eligible for publishing.';
                $this->response_data['data'] = ['invalid_estimate_sheet_ids' => $missingSheetIds];
                return $this->sendJsonResponse(self::HTTP_BAD_REQUEST);
            }
            $payload['estimate_sheet_id'] = $sheetId ?? $sheetIds->all();
        }
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

        $http = Http::acceptJson()
            ->timeout((int) config('services.ezsubcontractor.timeout', 20))
            ->withHeaders(['Idempotency-Key' => hash('sha256', $estimate->id . '|' . ($sheetIds === null ? $requestedIds->sort()->implode(',') : 'specification|' . $sheetIds->sort()->implode(',')))]);
        if ($token = config('services.ezsubcontractor.token')) {
            $http = $http->withToken($token);
        }

        try {
            $documents = $this->estimateAttachments($estimate);
            $uploads = $request->file('attachments', []);
            if ($documents->count() + count($uploads) > 10) {
                $this->response_data['message'] = 'A maximum of 10 attachments can be published, including existing estimate attachments.';
                return $this->sendJsonResponse(self::HTTP_BAD_REQUEST);
            }
            foreach ($documents as $document) {
                if (!Storage::disk('public')->exists($document->file_path)) {
                    $this->response_data['message'] = 'An estimate attachment is missing. Please upload it again before publishing.';
                    return $this->sendJsonResponse(self::HTTP_BAD_REQUEST);
                }
                if (Storage::disk('public')->size($document->file_path) > 10240 * 1024) {
                    $this->response_data['message'] = 'Estimate attachments must be 10 MB or smaller to publish.';
                    return $this->sendJsonResponse(self::HTTP_BAD_REQUEST);
                }
            }
            if ($documents->isNotEmpty() || $uploads) {
                $http = $http->asMultipart();
                $parts = [];
                foreach (\Illuminate\Support\Arr::dot($payload) as $key => $value) {
                    if ($value === null || is_array($value)) {
                        continue;
                    }
                    $segments = explode('.', $key);
                    $name = array_shift($segments);
                    foreach ($segments as $segment) {
                        $name .= '[' . $segment . ']';
                    }
                    $parts[] = ['name' => $name, 'contents' => (string) $value];
                }
                $attachmentIndex = 0;
                foreach ($uploads as $index => $attachment) {
                    $file = $attachment['file'];
                    $http->attach('attachments[' . $attachmentIndex . '][file]', file_get_contents($file->getRealPath()), $file->getClientOriginalName());
                    $parts[] = [
                        'name' => 'attachments[' . $attachmentIndex . '][description]',
                        'contents' => (string) $request->input('attachments.' . $index . '.description', ''),
                    ];
                    $attachmentIndex++;
                }
                foreach ($documents as $document) {
                    $http->attach('attachments[' . $attachmentIndex . '][file]', Storage::disk('public')->get($document->file_path), basename($document->file_path));
                    $parts[] = [
                        'name' => 'attachments[' . $attachmentIndex . '][description]',
                        'contents' => (string) $document->document_name,
                    ];
                    $attachmentIndex++;
                }
                $payload = $parts;
            }
            $response = $http->post($url, $payload);
        } catch (\Throwable $exception) {
            report($exception);
            $this->response_data['message'] = 'EZsubcontractor could not be reached. Please try again.';
            return $this->sendJsonResponse(self::HTTP_INTERNAL_SERVER_ERROR);
        }

        if (!$response->successful()) {
            $remoteMessage = $response->json('message');
            if (is_array($remoteMessage)) {
                $remoteMessage = collect($remoteMessage)->flatten()->first();
            }
            $this->response_data['message'] = $response->status() < 500
                && is_string($remoteMessage) && $remoteMessage !== ''
                ? $remoteMessage
                : 'EZsubcontractor rejected the job.';
            $this->response_data['data'] = [
                'remote_status' => $response->status(),
                'remote_response' => $response->json() ?: $response->body(),
            ];
            return $this->sendJsonResponse($response->status() >= 400 && $response->status() < 500 ? $response->status() : 502);
        }

        $this->response_data['status'] = true;
        $this->response_data['message'] = $sheetIds === null ? 'Selected trades posted to EZsubcontractor successfully.' : 'Selected specifications posted to EZsubcontractor successfully.';
        $this->response_data['data'] = [
            'estimate_id' => $estimate->id,
            'posted_trade_ids' => $foundIds->values(),
            'posted_estimate_sheet_id' => $sheetId,
            'posted_estimate_sheet_ids' => $sheetIds ?? collect(),
            'ezsubcontractor' => $response->json(),
        ];
        return $this->sendJsonResponse();
    }

    private function estimateAttachments(Estimate $estimate)
    {
        return SettingDocument::where('estimate_id', $estimate->id)
            ->where('company_id', $estimate->company_id)
            ->whereIn('document_type', ['estimate_upload', 'sub_estimate_upload'])
            ->orderBy('sort_order')->orderBy('id')->get();
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
        ?array $contactOptions = null,
        ?array $estimateSheetIds = null
    ): array
    {
        $selectedIds = $tradeIds === null ? null : collect($tradeIds)->map(fn ($id) => (int) $id)->unique();
        $sheets = $estimate->sheet->filter(function ($sheet) use ($selectedIds, $estimateSheetIds) {
            $tradeId = optional(optional($sheet->code)->ProductGroup)->id;
            return $sheet->quantity > 0 && $tradeId
                && ($estimateSheetIds === null || in_array((int) $sheet->id, $estimateSheetIds, true))
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
                'profile_image' => $this->profileImageUrl($estimate->user),
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

    private function profileImageUrl(?User $user): ?string
    {
        // Do not sync Voyager's default avatar as a real profile photo.
        $avatar = trim((string) ($user ? $user->getRawOriginal('avatar') : ''));
        if ($avatar === '' || $avatar === config('voyager.user.default_avatar', 'users/default.png')) {
            if (!$user) {
                return null;
            }

            // The app's uploaded account image is the invoice/company logo.
            // Use the same owner selection as the login response for team members.
            $ownerId = $user->is_admin ? $user->id : ($user->parent_id ?: $user->id);
            $setting = UserInvoiceSetting::where('user_id', $ownerId)->first();
            $logo = trim((string) optional($setting)->logo);
            if (preg_match('~^https?://~i', $logo)) {
                return $logo;
            }

            return $setting ? ($setting->full_logo ?: null) : null;
        }

        if (preg_match('~^https?://~i', $avatar)) {
            return $avatar;
        }

        return url(\TCG\Voyager\Facades\Voyager::image($avatar));
    }

    private function notFoundResponse(): JsonResponse
    {
        $this->response_data['message'] = 'Estimate not found.';
        return $this->sendJsonResponse(self::HTTP_NOT_FOUND);
    }
}
