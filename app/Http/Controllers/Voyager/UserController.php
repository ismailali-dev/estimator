<?php

namespace App\Http\Controllers\Voyager;

use Exception;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use TCG\Voyager\Database\Schema\SchemaManager;
use TCG\Voyager\Events\BreadDataAdded;
use TCG\Voyager\Events\BreadDataDeleted;
use TCG\Voyager\Events\BreadDataRestored;
use TCG\Voyager\Events\BreadDataUpdated;
use TCG\Voyager\Events\BreadImagesDeleted;
use TCG\Voyager\Facades\Voyager;
use TCG\Voyager\Http\Controllers\Traits\BreadRelationshipParser;
use App\Models\User;
use App\Models\Company;
use App\Models\UserInvoiceSetting;
use App\Helpers\Helper;

class UserController extends \TCG\Voyager\Http\Controllers\VoyagerUserController
{
public function update(Request $request, $id)
{
    try {
        // Find the user or fail with an error
        $user = User::with('company')->findOrFail($id);
       
    
        // Retrieve the related invoice setting or create a new instance
        $invoiceSetting = $user->invoiceSetting ?? new UserInvoiceSetting();

        // Handle the invoice logo upload using the custom helper
        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            
            // Use the custom helper for file upload
            $path = Helper::file_upload($request, 'logo', 'user');
            
            // Check if the upload was successful before proceeding
            if (!$path) {
                throw new \Exception('File upload failed.');
            }

            // Save the new logo path
            $invoiceSetting->logo = $path;
        }

        // Check if the user does not have a company_id, create or update the company
        if (!$user->company) {
           
            // Check for missing input fields and throw exceptions if necessary
            $companyName = $request->input('company_name');
            $companyAddress = $request->input('company_address');
            $licenseNo = $request->input('license_no');
            
            if (!$companyName || !$companyAddress) {
                return redirect()->back()->withErrors(['company_details' => 'Company details are incomplete.'])->withInput();
            }
            
            // Create the company and associate it with the user
            $company = Company::create([
                'user_id' => $user->id,
                'name' => $companyName,
                'address' => $companyAddress,
                'license_no' => $licenseNo,
            ]);
            
            // Set the company_id in the user model
            $user->company_id = $company->id;
            $user->save(); // Save user with updated company_id
        } else {
            // If the user already has a company, update the company details
            $company = $user->company;
           

            // Check if the company details have changed
            $companyName = $request->input('company_name');
            $companyAddress = $request->input('company_address');
            $licenseNo = $request->input('license_no');
            
            if ($companyName && $companyName !== $company->name) {
                $company->name = $companyName;
            }

            if ($companyAddress && $companyAddress !== $company->address) {
                $company->address = $companyAddress;
            }

            if ($licenseNo && $licenseNo !== $company->license_no) {
                $company->license_no = $licenseNo;
            }

            // Save the updated company details
            $company->save();
        }

        // Update or create the user address with the state_id as provided in the input field
        $city = $request->input('city');
        $stateId = $request->input('state_id');
        $zipCode = $request->input('zip_code');
        
        if (!$city || !$stateId || !$zipCode) {
            return redirect()->back()->withErrors(['address_details' => 'Address details are incomplete.'])->withInput();
        }

        // Create or update user address
        $user->userAddress()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'city' => $city,
                'state_id' => $stateId, // Save state_id directly
                'zip_code' => $zipCode,
            ]
        );
        
        // Ensure company_id is set before saving invoiceSetting
        $invoiceSetting->company_id = $user->company_id;

        // Handle the color input (ensuring a valid color value is provided)
        $color = ltrim($request->input("color", "ffffff"), '#'); // Remove # if exists
        if (!preg_match('/^[a-f0-9]{6}$/i', $color)) {
            return redirect()->back()->withErrors(['color' => 'Invalid color code.'])->withInput();
        }
        $invoiceSetting->color = $color;

        // Associate the invoice setting with the user and save it
        $invoiceSetting->user_id = $user->id;
        $invoiceSetting->save();

         // Call the parent update method
         return parent::update($request, $id);

    } catch (\Exception $e) {
        // Log the error for debugging purposes
        \Log::error('Error updating user settings: ' . $e->getMessage());
        
        // Return a friendly error message and redirect back
        return redirect()->back()->withErrors(['error' => 'An error occurred while updating user settings.'])->withInput();
    }
}


public function store(Request $request)
{
        // dd($request->all());

    return DB::transaction(function () use ($request) {

        // ✅ Step 1: Prepare user data
        if ($request->filled('password')) {
            $request->request->set('password', \Hash::make($request->password));
        } else {
            $request->request->set('password', \Hash::make('12345678'));
        }

        $request->request->set(
            'name',
            $request->first_name . ' ' . $request->last_name
        );

        // ✅ Step 2: Create user using Voyager
        $response = parent::store($request);

        // 🔥 IMPORTANT: get latest created user
        $user = \App\Models\User::latest()->first();

        // -------------------------
        // ✅ Step 3: Create Company
        // -------------------------
        if ($request->filled('company_name') && $request->filled('company_address')) {

            $company = \App\Models\Company::create([
                'user_id' => $user->id,
                'name' => $request->company_name,
                'address' => $request->company_address,
                'license_no' => $request->license_no,
            ]);

            // attach company to user
            $user->company_id = $company->id;
            $user->save();
        }

        // -------------------------
        // ✅ Step 4: Create Address
        // -------------------------
        if ($request->filled('city') && $request->filled('state_id') && $request->filled('zip_code')) {

            $user->userAddress()->create([
                'city' => $request->city,
                'state_id' => $request->state_id,
                'zip_code' => $request->zip_code,
            ]);
        }

        // -------------------------
        // ✅ Step 5: Invoice Setting
        // -------------------------
        // $invoiceSetting = new \App\Models\UserInvoiceSetting();

        // if ($request->hasFile('logo')) {
        //     $path = Helper::file_upload($request, 'logo', 'user');
        //     $invoiceSetting->logo = $path;
        // }

        // $invoiceSetting->user_id = $user->id;
        // $invoiceSetting->company_id = $user->company_id;
        // $invoiceSetting->color = ltrim($request->input('color', 'ffffff'), '#');

        // $invoiceSetting->save();

        return $response;
    });
}
}
