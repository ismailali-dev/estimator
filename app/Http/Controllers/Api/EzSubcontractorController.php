<?php

namespace App\Http\Controllers\Api;

use App\Models\Estimate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class EzSubcontractorController extends ResponseController
{
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
            (string) $request->input('end_date')
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
        ?string $endDate = null
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
