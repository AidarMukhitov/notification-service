<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;

class LogRequests
{
    public function handle($request, Closure $next)
    {
        Log::info('Request received: ' . $request->method() . ' ' . $request->path());
        Log::info('Request body: ' . $request->getContent());
        
        $response = $next($request);
        
        Log::info('Response status: ' . $response->status());
        
        return $response;
    }
}