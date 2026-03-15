<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LogRequest
{
    private const MASKED_VALUE = '[MASKED]';

    private const SENSITIVE_KEYS = [
        'password',
        'password_confirmation',
        'current_password',
        'token',
        'access_token',
        'refresh_token',
        'authorization',
    ];

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
            'input' => $this->sanitizeInput($request->all()),
        ]);

        return $next($request);
    }

    private function sanitizeInput(array $input): array
    {
        foreach ($input as $key => $value) {
            if (in_array(strtolower((string) $key), self::SENSITIVE_KEYS, true)) {
                $input[$key] = self::MASKED_VALUE;
                continue;
            }

            if (is_array($value)) {
                $input[$key] = $this->sanitizeInput($value);
            }
        }

        return $input;
    }
}
