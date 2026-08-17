<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        $this->renderable(function (ModelNotFoundException $exception, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'data' => [],
                    'message' => 'Resource not found.',
                    'status' => false,
                ], 404);
            }
        });
    }

    protected function unauthenticated($request, AuthenticationException $ex){
        if( $request->is('api/*') ) { // for routes starting with `/api`
            return response()->json(['data' => [], 'message' => "Unauthorized", 'status' => false], 401);
        }
        return redirect('/login'); // for normal routes
    }
}
