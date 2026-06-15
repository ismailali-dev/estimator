<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Cache;

class ProductController extends ResponseController
{
    
    
    function index(ProductService $productService){
        $validator = Validator::make(request()->all(), [
            'q' => 'required|string'
        ]);
        if ($validator->fails()){
            foreach ($validator->errors()->all() as $msg) {
                if (strlen(trim($msg)) > 1){
                    $this->response_data["message"] = $msg;
                    return $this->sendJsonResponse();
                }
            }
        }
        $data = $productService->getHomeDepotProducts(\request()->get('q'));
        $this->response_data["status"] = true;
        $this->response_data["data"] = json_decode($data);
        return $this->sendJsonResponse();
    }
    
    
    public function search(ProductService $productService)
    {
        
        
      
        Cache::flush(); // This will remove all cache keys
    
        $validator = Validator::make(request()->all(), [
            'q' => 'required|string',
            'page' => 'sometimes|integer|min:1', // Optional page parameter
        ]);
    
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $msg) {
                if (strlen(trim($msg)) > 1) {
                    $this->response_data["message"] = $msg;
                    return $this->sendJsonResponse();
                }
            }
        }
    
        // Get the page from the request, default to 1
        $page = request()->get('page', 1);
        $limit = request()->get('limit', 24);
    
        // Get products using the ProductService with pagination
        
        $zip_code = optional(auth()->user()->userAddress)->zip_code ?? null;
         
        
        $data = $productService->searchableProducts(request()->get('q'), request()->get('page', 1), $limit,$zip_code);
    
        // Check if data is not empty and respond accordingly
        $this->response_data["status"] = true;
        $this->response_data["data"] = $data;
    
        return $this->sendJsonResponse();
        
    }

    

    function getProducts(ProductService $productService){
        $validator = Validator::make(request()->all(), [
            'q' => 'required|string'
        ]);
        if ($validator->fails()){
            foreach ($validator->errors()->all() as $msg) {
                if (strlen(trim($msg)) > 1){
                    $this->response_data["message"] = $msg;
                    return $this->sendJsonResponse();
                }
            }
        }
        $keyword = request()->get('q');
        $this->response_data["status"] = true;
        $products = $productService->searchProducts($keyword);

        if(count($products) == 0){

            $productService->syncProductData($keyword);
            $products = $productService->searchProducts($keyword);
            $this->response_data["data"] = $products;
        }else{
            $this->response_data["data"] = $products;
        }
        return $this->sendJsonResponse();
    }




}
