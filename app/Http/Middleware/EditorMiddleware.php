<?php

namespace App\Http\Middleware;

use Closure;
use Auth;

class EditorMiddleware
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
        if(Auth::user()->isRole('editor') || Auth::user()->isRole('admin'))
            return $next($request);
        else
            abort(403, __('auth.forbidden'));
    }
}
