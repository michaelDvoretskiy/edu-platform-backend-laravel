<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Arr;

class Locale
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
        if ($request->method() === 'GET') {
            $locale = $request->query('locale');
            if(!$locale || !in_array($locale, config('app.locales'))) {
                $locale = config('app.fallback_locale');
            }
            app()->setLocale($locale);
        }
        return $next($request);
    }
}
