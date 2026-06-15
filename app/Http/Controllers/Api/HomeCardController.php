<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Code;
use App\Models\HomeCards;
use App\Models\Estimate;
use App\Models\Template;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\User;

class HomeCardController extends ApiBaseController
{
    //

    public function index(Request $request):JsonResponse
    {
        
        $currentUser = $request->user(); // Get the current user
    
        if ($currentUser->hasFullModuleAccess()) {
           
            // Determine which user's subscriptions to fetch
            $currentUser = $currentUser->is_admin ? $currentUser : User::find($currentUser->parent_id);
    
        } 
        
        $cards = HomeCards::where("status", "=", 1)->get(["id", "title", "link", "short_detail", "type"]);
        
        
        if ($currentUser->is_admin == 1) {
            
            if((auth()->user()->hasSubscription('multi_user_access')) ) {
                $childUserIds = User::withTrashed()->where('parent_id', $currentUser->id)->pluck('id')->toArray();
            }
            else{
                $childUserIds = User::where('parent_id', $currentUser->id)->pluck('id')->toArray();
            }
        
            // Include both the current user and child users in the estimates query
            $userIds = array_merge([$currentUser->id], $childUserIds);
            $userEstimates = Estimate::whereIn("user_id", $userIds)->get();
        } else {
            // For non-admin users, show only their estimates
            $userEstimates = Estimate::where("user_id", "=", $currentUser->id)->get();
        }
        
        
        // $userEstimates = Estimate::where("company_id", "=", $request->user()->company_id)->get();
        
        $userTemplates = Template::where("company_id", "=", $request->user()->company_id)->get();

        $userCodes = Code::where("company_id", "=", $request->user()->company_id)->withoutTrashed()->get();
        
        
        $this->response_data["data"] = ["home_card"=>$cards, "estimate"=>count($userEstimates), "codes"=>count($userCodes), "template"=>count($userTemplates)];
        $this->response_data["status"] = true;
        return $this->sendJsonResponse();

    }
}
