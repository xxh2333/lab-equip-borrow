<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * 链路追踪中间件：为每个请求生成唯一 TraceId
 */
class TraceIdMiddleware
{
    public function handle($request, Closure $next)
    {
        $traceId = (string) Str::uuid();

        $request->attributes->set('trace_id', $traceId);

        Log::withContext([
            'trace_id' => $traceId,
        ]);

        return $next($request);
    }
}
