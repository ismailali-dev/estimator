<?php

namespace App\Services;

use App\Models\Product;
use GuzzleHttp\Client;
use PHPUnit\Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;

class ProductService
{
    
    protected $cacheTime = 3600; // Cache time in seconds
    
    public function searchableProducts($keyword, $page = 1, $ps = 10,$zip_code = null)
    {
        
        
        $isValidZip = $zip_code && preg_match('/^\d{5}$/', $zip_code);
        // Create a unique cache key based on the search keyword and page
        $cacheKey = 'home_depot_search_' . md5($keyword . '_page_' . $page);
    
        // Check if the results are cached
        $cachedResults = Cache::get($cacheKey);
        if ($cachedResults) {
            return $cachedResults; // Return cached results directly as they are already an array
        }
    
        try {
            $client = new Client();
            
            // Calculate the offset based on the page number
            $offset = ($page - 1) * $ps; // e.g., page 1 -> offset 0, page 2 -> offset 10, etc.
    
          
            $query = [
            'engine' => 'home_depot',
            'q' => $keyword,
            'api_key' => 'a6217cf4987727e0374a30ab9efb70e1a4e9ba1e54a11c6ec6355a27e356ed0f', // Replace with your actual API key
            'page' => $page, // Page number
            'ps' => $ps, // Number of items per page
            'offset' => $offset // Offset for products result
        ];

        // Add zip code to the query if it's valid
        if ($isValidZip) {
            $query['zip_code'] = $zip_code; // Add zip code as a search parameter
        }

        // Send the request to the Home Depot API
        $response = $client->request('GET', 'https://serpapi.com/search.json', [
            'query' => $query
        ]);
        
    
            $statusCode = $response->getStatusCode();
            $body = json_decode($response->getBody()->getContents());
    
            // Handle the response and extract necessary fields
            if ($statusCode === 200 && isset($body->products)) {
                $products = $this->extractProductData($body->products);
    
                // Cache the results temporarily for future use
                Cache::put($cacheKey, $products, 300); // Cache for 5 minutes (300 seconds)
                return $products; // Return the structured product data
            } else {
                // Handle error statuses
                return ['error' => 'Failed to fetch data from Home Depot API.'];
            }
        } catch (Exception $ex) {
            // Handle exceptions
            return ['error' => $ex->getMessage()];
        }
    }
    
    private function extractProductData($products)
    {
        $extractedData = [];
    
        foreach ($products as $product) {
            $extractedData[] = [
                "title" => $product->title,
                "brand" => $product->brand ?? '',
                "thumbnail" => $product->thumbnails[0][0] ?? '', // Safely access the thumbnail
                "model_number" => $product->model_number ?? '',
                "engine" => 'home_depot', // Assuming the engine is constant
                "favorite" => $product->favorite ?? 0,
                "rating" => $product->rating ?? 0,
                "price" => $product->price ?? 0,
                "reviews" => $product->reviews ?? 0,
            ];
        }
    
        return $extractedData;
    }
    
    
    function getHomeDepotProducts($keyword){
        try{
            $client = new Client();
            $response = $client->request('GET', 'https://serpapi.com/search.json', [
                'query' => [
                    'engine' => 'home_depot',
                    'q' => $keyword,
                    'api_key' => 'a6217cf4987727e0374a30ab9efb70e1a4e9ba1e54a11c6ec6355a27e356ed0f',
                ]
            ]);

            $statusCode = $response->getStatusCode();
            $body = $response->getBody()->getContents();

            // Handle the response as per your requirements
            // For example, you can return the response body
            return $body;
        }catch (Exception $ex){
            dump($ex->getMessage());
            throw new $ex->getMessage();
        }

    }



    function syncProductData($keyword = ''){
        $jsonContent = $this->getHomeDepotProducts($keyword);
//        $jsonFilePath = storage_path('/products.json');
//        if (!File::exists($jsonFilePath))
//            return false;
//
//        $jsonContent = File::get($jsonFilePath);
        $jsonData = json_decode($jsonContent);
        $engine = $jsonData->search_parameters->engine;
        $products = $jsonData->products;
        foreach ($products as $product){
            $this->syncProduct($product,$engine);
        }

    }

    function syncProduct($product,$engine){

        Product::updateOrCreate([
            "product_id" => $product->product_id
        ],[
            "title" => $product->title,
            "brand" => $product->brand ?? '',
            "thumbnail" => $product->thumbnails[0][0],
            "model_number" => $product->model_number,
            "engine" => $engine,
            "favorite" => $product->favorite ?? 0,
            "rating" => $product->rating ?? 0,
            "price" => $product->price ?? 0,
            "reviews" => $product->reviews ?? 0,
        ]);
    }

    function searchProducts($keyword){
        return Product::where(function ($query) use ($keyword) {
            $query->where('title', 'like', '%' . $keyword . '%')
                ->orWhere('model_number', 'like', '%' . $keyword . '%');
        })->get();
    }
}
