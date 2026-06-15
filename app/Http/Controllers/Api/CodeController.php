<?php

namespace App\Http\Controllers\Api;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Code;
use App\Models\ProductGroup;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CodeController extends ApiBaseController
{
    //
   public function index(Request $request): JsonResponse
    {
       
       
         $codes = Code::join('product_groups', 'product_groups.id', '=', 'codes.product_group_id') // Join product_groups
        ->where('codes.status', 1) // Filter active codes
        ->where('codes.company_id', '=', $request->user()->company_id) // Specify table for company_id
        // ->where("product_groups.company_id", $request->user()->company_id)
        ->where('product_groups.status',1) // Specify table for company_id
         ->withoutTrashed()
        ->select('codes.*', 'product_groups.group_order') // Select codes and group_order
        ->orderBy('product_groups.group_order', 'asc') // Sort by group_order
        ->get();
   

    $products = [];
    foreach ($codes as $code) {
        $products[] = [
            "id" => $code->id,
            "code_name" => $code->code_name,
            "code_date" => $code->code_date,
            "product_group_id" => $code->product_group_id,
            "supplier_id" => $code->supplier_id,
            "product_name" => $code->product_name,
            "sku_no" => $code->sku_no,
            "model_no" => $code->model_no,
            "unit_of_measure" => $code->unit_of_measure,
            "labor_cost" => $code->labor_cost,
            "material_cost" => $code->material_cost,
            "misc_cost" => $code->misc_cost,
            "description" => $code->description,
            "image" => $code->full_image,
            "type" => $code->type,
            "operator"       => $code->operator,
            "adjust_num"     => $code->adjust_num,
            "adjusted_cost"  => $code->adjusted_cost,
        ];
    }

    $this->response_data["status"] = true;
    $this->response_data["data"] = ["product" => $products];
    return $this->sendJsonResponse();
}

    public function checkSupplierSupport(Supplier $supplier){
        $authorize = true;
        if(!empty($supplier->subscription_slug)){
            $authorize = auth()->user()->hasSubscription($supplier->subscription_slug);
        }
        return $authorize;
    }

    public function checkHomeDepotSupport(){

        $this->response_data["status"] = true;
        if(!auth()->user()->hasHomeDepotSupport()){
            $this->response_data["status"] = false;
            $this->response_data["message"] = ["Upgrade your plan"];
            $this->response_data["no_subscription"] = true;
        }
        return $this->sendJsonResponse();

    }

    public function isSupplierSupport(Supplier $supplier){
        $this->response_data["status"] = true;
        if(!$this->checkSupplierSupport($supplier)){
            $this->response_data["status"] = false;
            $this->response_data["message"] = ["Upgrade your plan"];
            $this->response_data["no_subscription"] = true;
        }
        return $this->sendJsonResponse();
    }


    public function create(Request $request): JsonResponse
    {
        
            $validator = Validator::make($request->all(), [
            'code_name' => [
                'required',
                'string',
                Rule::unique("codes")->where(function ($query) use ($request) {
                    return $query->where("code_name", $request->input("code_name"))
                                 ->where("product_group_id", $request->input('product_group_id'))
                                 ->where("supplier_id", $request->input("supplier_id"))
                                 ->where("user_id", $request->user()->id)
                                 ->where('deleted_at',null);
                }),
            ],
            'code_date' => 'nullable|required|iso_date',
            'product_group_id' => 'required|numeric|min:1',
            'supplier_id' => 'sometimes|nullable|numeric|min:1',
            'product_name' => 'nullable|string', // Initially nullable
            'sku_no' => 'nullable|string',
            'model_no' => 'nullable|string',
            'unit_of_measure' => 'required|string',
            'labor_cost' => 'nullable|numeric',
            'material_cost' => 'nullable|numeric',
            'misc_cost' => 'nullable|numeric',
            'description' => 'nullable',
            'image' => "nullable",
            'type' => 'sometimes|in:supplier,customer,both', // Optional type validation
            // new rules
            'operator' => 'nullable|in:divide,multiply',
            'adjust_num' => 'nullable|numeric',
            'adjusted_cost' => 'nullable|numeric',

        ]);
        
        $validator->after(function ($validator) use ($request) {
            $materialCost = $request->input('material_cost');
        
            // Check if both labor_cost and material_cost are missing
            if (is_null($request->input('labor_cost')) && is_null($materialCost)) {
                $validator->errors()->add('labor_cost', 'Either labor cost or material cost must be provided.');
                $validator->errors()->add('material_cost', 'Either labor cost or material cost must be provided.');
            }
        
            // Conditionally require product_name if material_cost > 0
            if ($materialCost > 0 && is_null($request->input('product_name'))) {
                $validator->errors()->add('product_name', 'Product name is required when material cost is greater than 0.');
            }
        
            // Conditionally check the type field if material_cost > 0
            if ($materialCost > 0 && !in_array($request->input('type'), ['supplier', 'customer', 'both'])) {
                $validator->errors()->add('type', 'Invalid type. Must be supplier, customer, or both when material cost is greater than 0.');
            }
        });

    
        // Handle validation failures
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $msg) {
                if (strlen(trim($msg)) > 1) {
                    $this->response_data["message"] = $msg;
                    return $this->sendJsonResponse();
                }
            }
        }
        
        
        // Check if user does not have 'template' subscription
        // if (!auth()->user()->hasSubscription('template')) {
        //     // Check how many codes the user has already created
        //     if (auth()->user()->codes()->count() >= 3) {
        //         $this->response_data["message"] = "Please upgrade your subscription";
        //         return $this->sendJsonResponse();
               
        //     }
        // }

    
        // Proceed to create the new Code record
        $newCode = new Code();
        $newCode->code_name = $request->input("code_name");
        $newCode->code_date = $request->input("code_date");
        $newCode->sort_order = 1;
        $newCode->supplier_id = $request->input("supplier_id");
        $newCode->product_group_id = $request->input("product_group_id");
        $newCode->product_name = $request->input("product_name");
        $newCode->sku_no = $request->input("sku_no");
        $newCode->model_no = $request->input("model_no");
        $newCode->unit_of_measure = $request->input("unit_of_measure");
        $newCode->labor_cost = $request->input("labor_cost", 0);
        $newCode->material_cost = $request->input("material_cost") ?? null;
        $newCode->misc_cost = $request->input("misc_cost");
        $newCode->description = $request->input("description");
        $newCode->type = $request->input("type");
        $newCode->operator = $request->input("operator");
        $newCode->adjust_num = $request->input("adjust_num");
        $newCode->adjusted_cost = $request->input("adjusted_cost");

        // Handle image upload or URL
        if ($request->hasFile('image')) {
            $newCode->image = Helper::file_upload($request, 'image', 'code');
        } elseif (filter_var($request->image, FILTER_VALIDATE_URL)) {
            $newCode->image = $request->image;
        }
    
        $newCode->user_id = $request->user()->id;
        $newCode->company_id = $request->user()->company_id;
        $newCode->save();
    
        // Return success response
        $this->response_data["status"] = true;
        $this->response_data["message"] = $newCode->code_name . " saved successfully.";
        $this->response_data["data"] = ["code" => $newCode];
        return $this->sendJsonResponse();
    }


    public function edit(Request $request, $id):JsonResponse
    {
        $data = Code::where("id", "=", $id)->where("company_id", "=", $request->user()->company_id)->first();
        $code = [];
        $this->response_data["message"] = "Data not found.";
        if (!empty($data)){
            $code = [
                "code_name" => $data->code_name,
                "code_date" => $data->code_date,
                "product_group_id" => $data->product_group_id,
                "sort_order" => 1,
                "supplier_id" => $data->supplier_id,
                "product_name" => $data->product_name,
                "sku_no" => $data->sku_no,
                "model_no" => $data->model_no,
                "unit_of_measure" => $data->unit_of_measure,
                "labor_cost" => $data->labor_cost,
                "material_cost" => $data->material_cost,
                "misc_cost" => $data->misc_cost,
                "description" => $data->description,
                "image" => $data->full_image,
                "type"=>$data->type,
                "operator"       => $data->operator,
                "adjust_num"     => $data->adjust_num,
                "adjusted_cost"  => $data->adjusted_cost,
                
            ];
            $this->response_data["status"] = true;
            $this->response_data["message"] = "";
            $this->response_data["data"] = ["code"=>$code];
        }

        return $this->sendJsonResponse();
    }

    public function update(Request $request, $id): JsonResponse
    {
        
            $validator = Validator::make($request->all(), [
            'code_name' => [
                'required',
                'string',
                Rule::unique("codes")->where(function($query) use($request, $id) {
                    return $query->where("code_name", $request->input("code_name"))
                        ->where("product_group_id", $request->input("product_group_id"))
                        ->where("user_id", $request->user()->id)
                        ->where("supplier_id", $request->input("supplier_id"))
                        ->where("id", "!=", $id); // Exclude the current record
                })
            ],
            'code_date' => 'required|iso_date',
            'product_group_id' => 'required|numeric|min:1',
            'supplier_id' => 'required|numeric|min:1',
            'product_name' => 'nullable|string', // Initially nullable
            'sku_no' => 'nullable|string',
            'model_no' => 'nullable|string',
            'unit_of_measure' => 'required|string',
            'labor_cost' => 'nullable|numeric',
            'material_cost' => 'nullable|numeric',
            'misc_cost' => 'nullable|numeric',
            'description' => 'nullable',
            'type' => 'nullable|in:supplier,customer,both', // Optional type field check
            'image' => 'nullable',
            'operator' => 'nullable|in:divide,multiply',
            'adjust_num' => 'nullable|numeric',
            'adjusted_cost' => 'nullable|numeric',
        ]);
        


            $validator->after(function ($validator) use ($request) {
                $materialCost = $request->input('material_cost');
            
                // Check if both labor_cost and material_cost are missing
                if (is_null($request->input('labor_cost')) && is_null($materialCost)) {
                    $validator->errors()->add('labor_cost', 'Either labor cost or material cost must be provided.');
                    $validator->errors()->add('material_cost', 'Either labor cost or material cost must be provided.');
                }
            
                // Conditionally require product_name if material_cost > 0
                if ($materialCost > 0 && is_null($request->input('product_name'))) {
                    $validator->errors()->add('product_name', 'Product name is required when material cost is greater than 0.');
                }
            
        
            });
        
    
        // Return validation errors
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $msg) {
                if (strlen(trim($msg)) > 1) {
                    $this->response_data["message"] = $msg;
                    return $this->sendJsonResponse();
                }
            }
        }
    
        // Fetch the existing code based on user_id and code_id
        $oldCode = Code::where("id", "=", $id)->where("user_id", "=", $request->user()->id)->first();
        if (!empty($oldCode)) {
            // Update fields
            $oldCode->code_name = $request->input("code_name");
            $oldCode->code_date = $request->input("code_date");
            $oldCode->product_group_id = $request->input("product_group_id");
            $oldCode->supplier_id = $request->input("supplier_id");
            $oldCode->product_name = $request->input("product_name");
            $oldCode->sku_no = $request->input("sku_no");
            $oldCode->model_no = $request->input("model_no");
            $oldCode->unit_of_measure = $request->input("unit_of_measure");
            $oldCode->labor_cost = !empty($request->input("labor_cost")) ? $request->input("labor_cost", 0) : 0;
            $oldCode->material_cost = $request->input("material_cost") ?? null;
            $oldCode->misc_cost = $request->input("misc_cost");
            $oldCode->description = $request->input("description");
            $oldCode->type = $request->input("type");
            $oldCode->operator        = $request->input("operator");
            $oldCode->adjust_num      = $request->input("adjust_num");
            $oldCode->adjusted_cost   = $request->input("adjusted_cost");
        
            // Handle image file upload
            if ($request->hasFile('image')) {
                // Delete old image if exists
                if (!empty($oldCode['image'])) {
                    $file = str_ireplace("storage/app/", '', $oldCode['image']);
                    if (Storage::exists($file)) {
                        Storage::delete($file);
                    }
                }
                $oldCode['image'] = Helper::file_upload($request, 'image', 'code');
            } elseif (filter_var($request->image, FILTER_VALIDATE_URL)) {
                // Handle URL for the image
                $oldCode['image'] = $request->image;
            }
    
            // Save the updated model
            $oldCode->update();
            $this->response_data["status"] = true;
            $this->response_data["message"] = $oldCode->code_name . " updated successfully.";
            $this->response_data["data"] = ["code" => $oldCode];
        } else {
            $this->response_data["message"] = "Data not found.";
        }
    
        return $this->sendJsonResponse();
    }


    public function delete(Request $request, $id): JsonResponse
    {
        try {
            // Fetch the code record based on user ID and code ID
            $code = Code::where("user_id", $request->user()->id)->where("id", $id)->first();
    
            // Set the response message for not found case
            $this->response_data["message"] = "Data not found.";
    
            // Check if the code exists
            if ($code) {
                // Check if the code is soft-deleted
                if ($code->trashed()) {
                    $this->response_data["message"] = "This code has already been soft deleted.";
                } else {
                    // Soft delete the code (will set deleted_at timestamp)
                    $code->delete();
                    $this->response_data["message"] = $code->code . " marked as deleted successfully.";
                    $this->response_data["status"] = true;
                }
            }
    
            return $this->sendJsonResponse();
    
        } catch (\Exception $ex) {
            // Capture and handle exceptions
            $this->response_data["message"] = $ex->getMessage();
            if ($ex->getCode() == 23000 && str_contains($ex->getMessage(), 'estimate_sheets')) {
                $this->response_data["message"] = "Cannot delete this one as this code is used in estimates.";
            }
            return $this->sendJsonResponse();
        }        
    }



    public function search(){
        $keyword = \request()->get("q","");
        $code = Code::where('code_name', 'like', '%' . $keyword . '%')->get();
        $this->response_data["status"] = true;
        $this->response_data["data"] = $code;
        return  $this->sendJsonResponse();
    }



}
