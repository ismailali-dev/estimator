<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CustomerController extends ApiBaseController
{

    public function index(Request $request):JsonResponse
    {
        $customers = Customer::where("status", "=", 1)->where("user_id", "=", $request->user()->id)->get(["id", "first_name",'last_name', "phone", "email", "address", "city", "state", "zip_code","note"]);

        $this->response_data["data"] = ["customer"=>$customers];
        $this->response_data["status"] = true;
        return $this->sendJsonResponse();
    }

    public function edit(Request $request, $id):JsonResponse
    {
        $customer = Customer::select("id", "first_name",'last_name', "phone", "email", "address", "city", "state", "zip_code","note")
            ->where("id", "=", $id)->where("user_id", "=", $request->user()->id)->first();
        if (!empty($customer)){
            $this->response_data["status"] = true;
            $this->response_data["data"] = ["customer"=>$customer];
        }
        $this->response_data["message"] = ((!empty($customer)) ? "Data not found." : "Customer not found.");
        return $this->sendJsonResponse();
    }

    public function create(Request $request):JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string',
            'last_name' => 'required|string',
           'phone' => [
                'required',
                'regex:/^\(\d{3}\) \d{3}-\d{4}$/', // Ensures the phone format matches (675) 975-9759
            ],
            'email' => [
                'required',
                'email',
                // Rule::unique("customers")->where(function($query) use($request){
                //     return $query->where("user_id", $request->user()->id)->where("email", $request->input("email"));
                // })
            ],
            'address' => 'required|string',
            'city' => 'required|string',
            'state' => 'required|string',
            'zip_code' => "nullable",
            'note'=>"nullable"
        ]);
        if ($validator->fails()){
            foreach ($validator->errors()->all() as $msg) {
                if (strlen(trim($msg)) > 1){
                    $this->response_data["message"] = $msg;
                    return $this->sendJsonResponse();
                }
            }
        }
        $newCustomer = new Customer();
        $newCustomer->first_name = $request->input("first_name");
        $newCustomer->last_name = $request->input("last_name");
        $newCustomer->phone = $request->input("phone");
        $newCustomer->email = $request->input("email");
        $newCustomer->address = $request->input("address");
        $newCustomer->city = $request->input("city");
        $newCustomer->state = $request->input("state");
        $newCustomer->zip_code = $request->input("zip_code");
        $newCustomer->user_id = $request->user()->id;
        $newCustomer->note = @$request->note;
        $newCustomer->save();
        $this->response_data["status"] = true;
        $this->response_data["message"] = "Customer saved successfully.";
        $this->response_data["data"] = ["customer"=>$newCustomer];
        return $this->sendJsonResponse();
    }

    public function update(Request $request, $id):JsonResponse
    {
        
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'phone' => [
                'required',
                'regex:/^\(\d{3}\) \d{3}-\d{4}$/', // Ensures the phone format matches (675) 975-9759
            ],
            'email' => [
                'required',
                'email',
                // Rule::unique("customers")->where(function($query) use($request){
                //     return $query->where("user_id", $request->user()->id)->where("email", $request->input("email"))->where("id", "!=", $request->id);
                // })
            ],
            'address' => 'required|string',
            'city' => 'required|string',
            'state' => 'required|string',
            'zip_code' => "nullable",
            'note'=> "nullable"
        ]);
        if ($validator->fails()){
            foreach ($validator->errors()->all() as $msg) {
                if (strlen(trim($msg)) > 1){
                    $this->response_data["message"] = $msg;
                    return $this->sendJsonResponse();
                }
            }
        }
       
        $customer = Customer::where("id", "=", $id)->where("user_id", "=", $request->user()->id)->first();
        $this->response_data["message"] = "Data not found.";
        if (!empty($customer)){
            $customer->first_name = $request->input("first_name");
            $customer->last_name = $request->input("last_name");
            $customer->phone = $request->input("phone");
            $customer->email = $request->input("email");
            $customer->address = $request->input("address");
            $customer->city = $request->input("city");
            $customer->state = $request->input("state");
            $customer->zip_code = $request->input("zip_code");
            $customer->note = @$request->note;
            $customer->update();
            $this->response_data["status"] = true;
            $this->response_data["message"] = "Customer update successfully.";
            $this->response_data["data"] = ["customer"=>$customer];
        }
        return $this->sendJsonResponse();
    }
    
    
   public function updateNote(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,id',
            'note' => 'required|string',
        ]);
    
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $msg) {
                if (strlen(trim($msg)) > 1){
                    return response()->json([
                        'status' => false,
                        'message' => $msg,
                        'data' => null,
                    ]);
                }
            }
        }
        
       
    
        $customer = Customer::where('id', $request->customer_id)
            ->where('user_id', $request->user()->id)
            ->first();
    
        if (!$customer) {
            return response()->json([
                'status' => false,
                'message' => 'Customer not found.',
                'data' => null,
            ]);
        }
    
        $customer->note = $request->note;
        $customer->save();
    
        return response()->json([
            'status' => true,
            'message' => 'Note updated successfully.',
            'data' => ['customer' => $customer],
        ]);
    }
    

    public function delete(Request $request, $id):JsonResponse
    {
        $customer = Customer::where("id", "=", $id)->where("user_id", "=", $request->user()->id)->first();
        $this->response_data["message"] = "Data not found.";
        if (!empty($customer)){
            $customer->delete();
            $this->response_data["status"] = true;
            $this->response_data["message"] = "Customer delete successfully.";
        }
        return $this->sendJsonResponse();
    }

    public function search(){
        $productGroup = Customer::where("user_id", "=", auth()->user()->id)
            ->where("status", "=", 1)
            ->where("first_name", 'like', '%' . request()->get("keyword","") . '%')
            ->orWhere("last_name", 'like', '%' . request()->get("keyword","") . '%')
            ->get(["id", "first_name",'last_name', "phone", "email", "address", "city", "state", "zip_code"]);
        $this->response_data["status"] = true;
        $this->response_data["data"] = ["supplier"=>$productGroup];
        return $this->sendJsonResponse();
    }

}
