<?php

namespace App\Http\Middleware;

use App\Apikey;
use Closure;

class VerifyApiToken
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $apiKeyToken = $request->header('X-API-KEY') ?: $request->input('apikey');

        $access = Apikey::where('api_key', $apiKeyToken)->first();

        if (empty($access)) {
            return response()->json('Invalid Api Key', 403)->send();
        }

        return $next($request);
    }
}
