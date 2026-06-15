<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ValidateCodeMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $subscriptions = auth()->user()->subscriptions()
                             ->where([
                                 "title" => "3x Code Template",
                                 "is_cancelled" => false
                             ])
                            ->get();

/*        if(count($subscriptions) > 0){

        }*/

        if(auth()->user()->codes()->count() == 3){
            response()->json([
                "message" => "You cannot create more than 3 Codes"
            ],422);
        }
        // dd(auth()->user()->codes()->count());

        return $next($request);
    }
}
