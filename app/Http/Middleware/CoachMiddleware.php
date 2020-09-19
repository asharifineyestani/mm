<?php

namespace App\Http\Middleware;

use Closure;
use Auth;

class CoachMiddleware
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
        if(Auth::user()->isRole('coach'))
            return $next($request);
        else
            abort(403, __('auth.forbidden'));
    }
}
