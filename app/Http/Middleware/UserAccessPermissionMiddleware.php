<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserAccessPermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next,$isUser=false)
    {
        // $isAdmin => $isUser
        $allowPaths = [
            '/me/',
            '/logout',
            '/customer/',
            '/template/',
            '/estimate/',
            '/profit-budget/',
            '/material-list/',
            '/forgot-password',
            'api/material-list/{estimate}/email',
            '/export-excel/material/',
            '/global-setting/user/update-annual-job-cost'
        ];
        $isAllow = false;
        $route = $request->route();
        $routePath = $route->uri();



        if(
            (!$request->isMethod('get'))
            && auth()->user()
            && (!$isUser)
            && auth()->user()->is_admin == 0
        ){
            
            foreach ($allowPaths as $allowPath) {
                if (str_contains($routePath, $allowPath)) {
                    $isAllow = true;
                }
            }
            if(!$isAllow){
                return response()->json([
                    'status' => false,
                    'message' => 'You are not Allowed to Access'
                ], 401);
            }

        }


        /*if($isAdmin && auth()->user()->is_admin == 0){
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 401);
        }
*/

        // $estimateSheet = $request->route('estimateSheet');
     
        // $estimate = $request->route('estimate');
         
        // $response = $this->checkAuthorization($request, $estimateSheet, $estimate);
        // if ($response) {
        //     return $response; // Return unauthorized response if not authorized
        // }
        
        return $next($request);
    }
    
    
  private function checkAuthorization($request, $estimateSheet = null, $estimate = null) {
    
    $currentUser = auth()->user();
  
    
    // Check if the user is an admin
    if (@$currentUser->is_admin) {
        // Admin has access to all estimates and estimate sheets
        return null; 
    }

    // Check for child user access to EstimateSheet
    if ($estimateSheet) {
        $estimateSheetUserId = @$estimateSheet->user_id;
        
        // Ensure the child user can only access their own EstimateSheet
        if (@$estimateSheetUserId !== @$currentUser->id) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized to access this estimate sheet'
            ], 401);
        }
    }

    // Check for child user access to Estimate
    if (@$estimate) {
        $estimateUserId = @$estimate->user_id;
        // Ensure the child user can only access their own Estimate
        if (@$estimateUserId !== @$currentUser->id) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized to access this estimate'
            ], 401);
        }
    }

    return null; // Return null if authorized
    
    
}



}
