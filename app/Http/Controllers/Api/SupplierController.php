<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Code;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use DB;
use App\Notifications\FirebasePushNotification;
use App\Services\FirebaseService;

class SupplierController extends ApiBaseController
{
    //

    // public function index(Request $request):JsonResponse
    // {
    //     $hd_suppliers = collect([]);
    //     $suppliers = Supplier::where("status", "=", 1)->where("user_id", "=", $request->user()->id)->get(["id", "full_name", "phone", "email", "address", "city", "state", "zip_code"]);
    //     if(auth()->user()->hasHomeDepotSupport()){
    //         $hd_suppliers = Supplier::where("subscription_slug","home_depot_support")->get(["id", "full_name", "phone", "email", "address", "city", "state", "zip_code"]);;

    //     }
    //     $suppliers = array_merge($suppliers->toArray(),$hd_suppliers->toArray());
    //     $this->response_data["status"] = true;
    //     $this->response_data["data"] = ["supplier"=>$suppliers];
    //     return $this->sendJsonResponse();
    // }
    
    public function index(Request $request): JsonResponse
{
    // Get the current logged-in user
    $currentUser = $request->user();
    
    // Initialize the supplier collection
    $suppliers = collect();
    
    // Check if the current user is an admin
    if ($currentUser->is_admin) {
        // Fetch suppliers for the admin user
        $suppliers = Supplier::where("status", 1)
            ->where("user_id", $currentUser->id)
            ->select("id", "full_name", "phone", "email", "address", "city", "state", "zip_code", 
                DB::raw("CASE WHEN subscription_slug = 'home_depot_support' THEN true ELSE false END as premium"))
            ->get();
        
        // Check for Home Depot support for the admin user
        if ($currentUser->hasHomeDepotSupport()) {
            $hd_suppliers = Supplier::where("subscription_slug", "home_depot_support")
                ->where("status", 1) // Ensure to filter based on active status if needed
                ->select("id", "full_name", "phone", "email", "address", "city", "state", "zip_code", 
                    DB::raw("true as premium"))
                ->get();
    
            // Merge the HD suppliers to the original suppliers collection
            $suppliers = $suppliers->merge($hd_suppliers);
        }
    } else {
        // The current user is a child user; find the admin user
        $adminUser = User::find($currentUser->parent_id); // Assuming parent_id is the admin's user ID
    
        if ($adminUser) {
            // Fetch suppliers for the admin user
            $suppliers = Supplier::where("status", 1)
                ->where("user_id", $adminUser->id)
                ->select("id", "full_name", "phone", "email", "address", "city", "state", "zip_code", 
                    DB::raw("CASE WHEN subscription_slug = 'home_depot_support' THEN true ELSE false END as premium"))
                ->get();
    
            // Check for Home Depot support for the admin user
            if ($adminUser->hasHomeDepotSupport()) {
                $hd_suppliers = Supplier::where("subscription_slug", "home_depot_support")
                    ->where("status", 1) // Ensure to filter based on active status if needed
                    ->select("id", "full_name", "phone", "email", "address", "city", "state", "zip_code", 
                        DB::raw("true as premium"))
                    ->get();
    
                // Merge the HD suppliers to the original suppliers collection
                $suppliers = $suppliers->merge($hd_suppliers);
            }
        }
    }

    // Prepare the response data
    $this->response_data["status"] = true;
    $this->response_data["data"] = ["supplier" => $suppliers->toArray()];  // Ensure you're returning 'suppliers', not 'supplier'

    // Return the JSON response
    return $this->sendJsonResponse();
}



    public function edit(Request $request, $id):JsonResponse
    {
        $supplier = Supplier::select("id", "full_name", "phone", "email", "address", "city", "state", "zip_code")
            ->where("id", "=", $id)->where("user_id", "=", $request->user()->id)->first();
        if (!empty($supplier)){
            $this->response_data["status"] = true;
            $this->response_data["data"] = ["supplier"=>$supplier];
        }
        $this->response_data["message"] = ((!empty($supplier)) ? "" : "Supplier not found.");
        return $this->sendJsonResponse();
    }

    public function create(Request $request):JsonResponse
    {
        
        
     
        $validator = Validator::make($request->all(), [
            'full_name' => [
                'required',
                'string',
                Rule::unique('suppliers')->where(function($query) use ($request) {
                    return $query->where('user_id', $request->user()->id)
                                ->whereNull('deleted_at'); // Exclude soft-deleted entries
                })
            ],
           'phone' => [
                'nullable', // Allows the field to be empty
                'regex:/^\(\d{3}\) \d{3}-\d{4}$/', // Ensures the format if a value is provided
            ],
            'email' => [
                'nullable', // Add the nullable rule here
                'email'
                // Rule::unique("suppliers")->where(function($query) use($request){
                //     return $query->where("user_id", $request->user()->id)->where("email", $request->input("email"));
                // })
            ],
            'address' => 'nullable|string',
            'city' => 'nullable|string',
            'state' => 'nullable|string',
            'zip_code' => "nullable"
        ]);
        if ($validator->fails()){
            foreach ($validator->errors()->all() as $msg) {
                if (strlen(trim($msg)) > 1){
                    $this->response_data["message"] = $msg;
                    return $this->sendJsonResponse();
                }
            }
        }
        $newSupplier = new Supplier();
        $newSupplier->full_name = $request->input("full_name");
        $newSupplier->phone = $request->input("phone");
        $newSupplier->email = $request->input("email");
        $newSupplier->address = $request->input("address");
        $newSupplier->city = $request->input("city");
        $newSupplier->state = $request->input("state");
        $newSupplier->zip_code = $request->input("zip_code");
        $newSupplier->user_id = $request->user()->id;
        $newSupplier->save();
        $this->response_data["status"] = true;
        $this->response_data["message"] = "Supplier saved successfully.";
        $this->response_data["data"] = ["supplier"=>$newSupplier];
        
       
        
        
        return $this->sendJsonResponse();
    }

    public function update(Request $request, $id):JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'full_name' => 'required|string',
            'phone' => [
        'nullable', // Allows the field to be empty
        'regex:/^\(\d{3}\) \d{3}-\d{4}$/', // Ensures the format if a value is provided
    ],
            'email' => [
                'nullable', // Separate nullable from the array for email validation
                'email',
                Rule::unique("suppliers")->where(function($query) use($request, $id) {
                    return $query->where("user_id", $request->user()->id)
                                 ->where("id", "!=", $id);
                })
            ],
            'address' => 'nullable|string',
            'city' => 'nullable|string',
            'state' => 'nullable|string',
            'zip_code' => 'nullable'
        ]);
        
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $msg) {
                if (strlen(trim($msg)) > 1) {
                    $this->response_data["message"] = $msg;
                    return $this->sendJsonResponse();
                }
            }
        }
        
        $supplier = Supplier::where("id", "=", $id)->where("user_id", "=", $request->user()->id)->first();
       
        if (!empty($supplier)){
            $supplier->full_name = $request->input("full_name");
            $supplier->phone = $request->input("phone");
            $supplier->email = $request->input("email");
            $supplier->address = $request->input("address");
            $supplier->city = $request->input("city");
            $supplier->state = $request->input("state");
            $supplier->zip_code = $request->input("zip_code");
            $supplier->update();
            $this->response_data["status"] = true;

            $this->response_data["data"] = ["supplier"=>$supplier];
        }
        $this->response_data["message"] = ((!empty($supplier)) ? "Supplier update successfully." : "Data not found.");
        return $this->sendJsonResponse();
    }

    public function delete(Request $request, $id):JsonResponse
    {
        try{
            $supplier = Supplier::with("Codes")->where("id", "=", $id)->where("user_id", "=", $request->user()->id)->first();
            $this->response_data["message"] = "Data not found.";
            if (!empty($supplier)){
                $deletefiles = [];
                foreach ($supplier->Codes as $code){
                    if (!empty($code->image)){
                        $file = str_ireplace("storage/app/", "", $code->image);
                        if (Storage::exists($file)){
                            $deletefiles[] = $file;
                            Storage::delete($file);
                        }
                    }
                    $code->delete();
                }
                $supplier->delete();
                $this->response_data["data"] = $deletefiles;
                $this->response_data["status"] = true;
                $this->response_data["message"] = "Supplier delete successfully.";
            }
            return $this->sendJsonResponse();
        }catch(\Exception $ex){
            $this->response_data["message"] = $ex->getMessage();
            if($ex->getCode() == 23000 && str_contains($ex->getMessage(), 'estimate_sheets')){
                $this->response_data["message"] = "Cannot delete this one as the code with this supplier is used in estimates.";
            }
            return $this->sendJsonResponse();
        }        

    }

    public function search(){
        $suppliers = Supplier::where("status", "=", 1)
                    ->where("user_id", "=", auth()->user()->id)
                    ->where("full_name", 'like', '%' . request()->get("keyword","") . '%')
                    ->get(["id", "full_name", "phone", "email", "address", "city", "state", "zip_code"]);
        $this->response_data["status"] = true;
        $this->response_data["data"] = ["supplier"=>$suppliers];
        return $this->sendJsonResponse();
    }

}
