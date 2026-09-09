<?php

namespace App\Http\Controllers\Api;

use App\Models\Company;
use App\Models\Setting;
use App\Models\SettingType;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class EzSubcontractorAccountController extends ResponseController
{
    public function sync(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email|max:255',
            'company_name' => 'sometimes|required|string|max:255',
            'position' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'state_id' => 'required|integer|exists:states,id',
            'zip_code' => 'required|string|max:255',
        ]);
        $url = config('services.ezsubcontractor.verify_account_url');
        $token = config('services.ezsubcontractor.token');
        if (!$url || !$token) {
            return response()->json(['status' => false, 'message' => 'EZSubcontractor account verification is not configured.'], 503);
        }
        try {
            $response = \Illuminate\Support\Facades\Http::acceptJson()->asJson()->withToken($token)
                ->timeout((int) config('services.ezsubcontractor.timeout', 20))
                ->post($url, ['email' => $credentials['email']]);
        } catch (\Illuminate\Http\Client\ConnectionException $exception) {
            return response()->json(['status' => false, 'message' => 'EZSubcontractor could not be reached. Please retry.'], 502);
        }
        if (!$response->successful()) {
            return response()->json(['status' => false, 'message' => $response->json('message') ?: 'EZSubcontractor verification failed.'],
                $response->status() >= 500 ? 502 : $response->status());
        }
        $profile = $response->json('data');
        $validator = \Illuminate\Support\Facades\Validator::make(is_array($profile) ? $profile : [], [
            'source_user_id' => 'required|integer|min:1', 'email' => 'required|email|max:255',
            'name' => 'required|string|max:255', 'company_name' => 'required|string|max:255',
            'company_address' => 'nullable|string|max:255', 'phone' => 'nullable|string|max:255',
            'email_verified' => 'required|boolean',
        ]);
        if ($response->json('success') !== true || $validator->fails()
            || strcasecmp((string) ($profile['email'] ?? ''), $credentials['email']) !== 0) {
            return response()->json(['status' => false, 'message' => 'Invalid account verification response.'], 502);
        }
        $data = $validator->validated();
        $data['company_name'] = $credentials['company_name'] ?? $data['company_name'];
        $data['position'] = $credentials['position'];
        $data['city'] = $credentials['city'];
        $data['state_id'] = $credentials['state_id'];
        $data['zip_code'] = $credentials['zip_code'];

        try {
            $generatedPassword = null;
            $result = DB::transaction(function () use ($data, &$generatedPassword) {
                $user = User::withTrashed()->where('ezsubcontractor_user_id', $data['source_user_id'])->lockForUpdate()->first();
                if ($user) {
                    if ($user->trashed() || (!$user->status && $user->email_verified_at !== null) || !$user->company_id) {
                        return response()->json(['status' => false, 'message' => 'Linked account is unavailable.'], 409);
                    }
                    return $this->synced($user, false);
                }
                if (User::withTrashed()->where('email', $data['email'])->exists()) {
                    return response()->json(['status' => false, 'message' => 'An EZEstimater account already exists with this email. Sign in to that account to link it.'], 409);
                }
                $names = preg_split('/\s+/', trim($data['name']), 2);
                $generatedPassword = \Illuminate\Support\Str::random(8);
                $user = new User();
                $user->forceFill([
                    'ezsubcontractor_user_id' => $data['source_user_id'],
                    'first_name' => $names[0], 'last_name' => $names[1] ?? '',
                    'email' => $data['email'], 'phone' => $data['phone'] ?? null,
                    'password' => Hash::make($generatedPassword),
                    'type' => 'APP-USER', 'job_type' => 1, 'position' => $data['position'],
                    'status' => 1, 'is_admin' => 1,
                    'email_verified_at' => now(), 'temp_email' => $data['email'],
                ])->save();
                $company = Company::create([
                    'user_id' => $user->id, 'name' => $data['company_name'],
                    'address' => $data['company_address'] ?? '',
                ]);
                $user->company_id = $company->id;
                $user->save();
                $user->userAddress()->create([
                    'city' => $data['city'], 'state_id' => $data['state_id'], 'zip_code' => $data['zip_code'],
                ]);
                foreach (Setting::SLUGS as $slug => $title) {
                    $user->settings()->create([
                        'title' => $title, 'slug' => $slug, 'value' => 0,
                        'company_id' => $company->id, 'type' => SettingType::SETTING_TYPE_FOR_ESTIMATE,
                    ]);
                }
                return $this->synced($user, true);
            });
            if ($generatedPassword !== null && $result->getStatusCode() === 201) {
                $payload = $result->getData(true);
                try {
                    \Illuminate\Support\Facades\Mail::to($data['email'])->send(
                        new \App\Mail\ImportedAccountCredentials($data['email'], $data['name'], $generatedPassword)
                    );
                    $payload['data']['credentials_email_sent'] = true;
                } catch (\Throwable $exception) {
                    $payload['data']['credentials_email_sent'] = false;
                    $payload['message'] = 'Account created, but the credentials email could not be sent. Use Forgot Password to access the account.';
                }
                $result->setData($payload);
            }
            return $result;
        } catch (\Illuminate\Database\QueryException $exception) {
            if (in_array((string) $exception->getCode(), ['23000', '23505'], true)) {
                return response()->json(['status' => false, 'message' => 'Account conflict. Retry the sync or sign in to your existing account.'], 409);
            }
            throw $exception;
        }
    }

    private function synced(User $user, bool $created)
    {
        return response()->json(['status' => true, 'message' => $created ? 'EZEstimater account synced.' : 'Account already synced.', 'data' => [
            'account_exists' => true, 'account_created' => $created,
            'user_id' => (int) $user->id, 'company_id' => (int) $user->company_id,
            'email_verification_required' => $user->email_verified_at === null,
            'password_setup_required' => !$user->status,
            'credentials_email_sent' => false,
            'company_name' => optional($user->company)->name,
            'position' => $user->position,
            'city' => optional($user->userAddress)->city,
        ]], $created ? 201 : 200);
    }
}