<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class ProductGroupController extends ApiBaseController
{
    //

   public function index(Request $request): JsonResponse
    {
        $productGroups = ProductGroup::where("company_id", $request->user()->company_id)
                                     ->where("status", 1)
                                     ->orderBy('group_order', 'asc') // Order by group_order in ascending order
                                     ->get(["id", "group_name"]);
    
        $this->response_data["status"] = true;
        $this->response_data["data"] = ["product_group" => $productGroups];
    
        return $this->sendJsonResponse();
    }

   public function create(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'group_name' => [
                'required',
                'string',
                Rule::unique("product_groups")->where(function ($query) use ($request) {
                    return $query->where("group_name", $request->input("group_name"))
                                 ->where("company_id", "=", $request->user()->company_id)
                                  ->whereNull('deleted_at'); // Exclude soft-deleted entries
                })
            ]
        ]);
    
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $msg) {
                if (strlen(trim($msg)) > 1) {
                    $this->response_data["message"] = $msg;
                    return $this->sendJsonResponse();
                }
            }
        }
    
        // Calculate the new group order
        $maxOrder = ProductGroup::where('company_id', $request->user()->company_id)
                                ->max('group_order');
        $newOrder = $maxOrder + 1;
    
        $productGroup = new ProductGroup();
        $productGroup->group_name = $request->input("group_name");
        $productGroup->status = 1;
        $productGroup->user_id = $request->user()->id;
        $productGroup->company_id = $request->user()->company_id;
        $productGroup->group_order = $newOrder; // Set the group_order value
        $productGroup->save();
    
        $this->response_data["status"] = true;
        $this->response_data["message"] = $productGroup->group_name . " saved successfully.";
        $this->response_data["data"] = ["product_group" => $productGroup];
    
        return $this->sendJsonResponse();
    }

   public function edit(Request $request, $id): JsonResponse
    {
        // Fetch the product group data by ID and company ID
        $data = ProductGroup::where("id", $id)
                            ->where("company_id", $request->user()->company_id)
                            ->first();
    
        // Prepare an empty response in case the data is not found
        $this->response_data["message"] = "Data not found.";
    
        if ($data) {
            // Prepare the product group data for editing
            $productGroup = [
                "group_name" => $data->group_name,
                // You can include additional fields if necessary
            ];
    
            // Update the response data with success status and product group information
            $this->response_data["status"] = true;
            $this->response_data["message"] = "";
            $this->response_data["data"] = ["product_group" => $productGroup];
        }
    
        // Return the JSON response
        return $this->sendJsonResponse();
    }

    public function update(Request $request, $id):JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'group_name' => [
                'required',
                'string',
                Rule::unique("product_groups")->where(function($query) use($request){
                    return $query->where("group_name", $request->input("group_name"))->where("company_id", "=", $request->user()->company_id)->where("id", "!=", $request->id);
                })
            ],
        ]);
        if ($validator->fails()){
            foreach ($validator->errors()->all() as $msg) {
                if (strlen(trim($msg)) > 1){
                    $this->response_data["message"] = $msg;
                    return $this->sendJsonResponse();
                }
            }
        }
        $productGroup = ProductGroup::where("id", "=", $id)->where("company_id", "=", $request->user()->company_id)->first();
        if (!empty($productGroup)){
            $productGroup->group_name = $request->input("group_name");
            $productGroup->update();
            $this->response_data["status"] = true;
            $this->response_data["message"] = "Category updated successfully";
            $this->response_data["data"] = ["product_group"=>$productGroup];
        }else{
            $this->response_data["data"] = "Data not found.";
        }
        return $this->sendJsonResponse();
    }

    public function delete(Request $request, $id):JsonResponse
    {
        $productGroup = ProductGroup::where("company_id", "=", $request->user()->company_id)->where("id", "=", $id)->first();
        $this->response_data["message"] = "Data not found.";
        if (!empty($productGroup)){

            $this->response_data["message"] = "Category deleted successfully";
            $productGroup->delete();
            $this->response_data["status"] = true;
        }
        return  $this->sendJsonResponse();
    }

    public function search(){
        $productGroup = ProductGroup::where("company_id", "=", auth()->user()->company_id)
                        ->where("status", "=", 1)
                        ->where("group_name", 'like', '%' . request()->get("keyword","") . '%')
                        ->get(["id", "group_name"]);
        $this->response_data["status"] = true;
        $this->response_data["data"] = ["supplier"=>$productGroup];
        return $this->sendJsonResponse();
    }
    
   public function reorder(Request $request): JsonResponse
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|exists:product_groups,id',
            'group_order' => 'required|integer|min:1',
        ]);
    
        // Handle validation errors
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $msg) {
                if (strlen(trim($msg)) > 1) {
                    $this->response_data["message"] = $msg;
                    return $this->sendJsonResponse();
                }
            }
        }
    
        $userCompanyId = $request->user()->company_id;
        $productId = $request->input('id');
        $newOrder = $request->input('group_order');
    
        // Fetch total count of product groups in the company
        $totalGroups = ProductGroup::where('company_id', $userCompanyId)->count();
    
        // Ensure the new group_order is within the valid range
        if ($newOrder > $totalGroups) {
            $this->response_data["status"] = false;
            $this->response_data["message"] = "The group order must be within the range of available groups.";
            return $this->sendJsonResponse();
        }
    
        // Fetch the product group to reorder and its old order
        $productGroup = ProductGroup::where('id', $productId)
                                    ->where('company_id', $userCompanyId)
                                    ->first();
    
        if (empty($productGroup)) {
            $this->response_data["status"] = false;
            $this->response_data["message"] = "Data not found.";
            return $this->sendJsonResponse();
        }
    
        $oldOrder = $productGroup->group_order;
    
        DB::beginTransaction(); // Start the transaction
    
        try {
            if ($newOrder > $oldOrder) {
                // Move down: decrement the group_order of all items between old and new position
                ProductGroup::where('company_id', $userCompanyId)
                            ->whereBetween('group_order', [$oldOrder + 1, $newOrder])
                            ->decrement('group_order');
            } elseif ($newOrder < $oldOrder) {
                // Move up: increment the group_order of all items between new and old position
                ProductGroup::where('company_id', $userCompanyId)
                            ->whereBetween('group_order', [$newOrder, $oldOrder - 1])
                            ->increment('group_order');
            }
    
            // Update the dragged item's group_order to the new position
            $productGroup->group_order = $newOrder;
            $productGroup->save();
    
            // Ensure that group_order values remain sequential (optional but recommended)
            $this->reindexGroupOrders($userCompanyId);
    
            $this->response_data["status"] = true;
            $this->response_data["message"] = $productGroup->group_name . " reordered successfully.";
            $this->response_data["data"] = ["product_group" => $productGroup];
    
            DB::commit(); // Commit the transaction
    
        } catch (\Exception $e) {
            // Rollback the transaction if something goes wrong
            DB::rollBack();
    
            $this->response_data["status"] = false;
            $this->response_data["message"] = "Failed to reorder product groups.";
            $this->response_data["data"] = null;
            $this->response_data["error"] = $e->getMessage();
        }
    
        return $this->sendJsonResponse();
    }
    
    /**
     * Reindex the group orders to maintain sequential order.
     */
    private function reindexGroupOrders(int $companyId)
    {
        $groups = ProductGroup::where('company_id', $companyId)
                              ->orderBy('group_order')
                              ->get();
    
        $counter = 1;
        foreach ($groups as $group) {
            $group->group_order = $counter++;
            $group->save();
        }
    }


}

