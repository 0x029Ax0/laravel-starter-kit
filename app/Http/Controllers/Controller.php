<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use Throwable;

abstract class Controller
{
    public $inProduction;

    public function __construct()
    {
        $this->inProduction = env('APP_ENV') === 'production';
    }

    protected function handle(callable $callback)// : JsonResponse
    {
        try {
            return call_user_func($callback);
        } catch (Throwable $e) {
            // Log error
            Log::error($e);

            // Return generic error message
            $response = [
                'status' => 'error',
                'message' => 'Internal Server Error',
            ];

            // Add error to response if not in production
            if (! $this->inProduction) {
                $response['error'] = $e->getMessage();
            }

            // Return json response with status code 500
            return response()->json($response, 500);
        }
    }
}
