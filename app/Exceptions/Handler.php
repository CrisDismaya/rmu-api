<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Throwable;
use Exception;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
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
        // Generic "not found" handler
        $this->renderable(function (ModelNotFoundException $e, $request) {
            return response()->json([
                'success' => false,
                'message' => 'Record not found.',
            ], 404);
        });

        // Database error handler
        $this->renderable(function (QueryException $e, $request) {
            return response()->json([
                'success' => false,
                'message' => 'Database error.',
            ], 500);
        });

        // Catch-all for unexpected errors
        $this->renderable(function (Exception $e, $request) {
            Log::error($e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Unexpected error occurred.',
            ], 500);
        });
    }
}
