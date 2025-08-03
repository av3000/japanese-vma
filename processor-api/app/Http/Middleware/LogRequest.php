<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class LogRequest
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        Log::channel('stderr')->info('Request Logged', [
            'url' => $request->url(),
            'method' => $request->method(),
            'input' => $request->all(),
        ]);

        return $next($request);
    }
}
