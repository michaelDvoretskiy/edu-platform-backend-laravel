<?php

namespace App\Http\Middleware;

use Closure;

class SwitchRequestHeader
{
    public function handle($request, Closure $next)
    {
        if ($request->header('userauth')) {
            $request->headers->add(['authorization' => $request->header('userauth')]);
        }
        return $next($request);
    }
}
