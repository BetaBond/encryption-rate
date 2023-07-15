<?php

namespace App\Http\Middleware;

use Colorful\Preacher\Preacher;
use Closure;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * 鉴权中间件
 *
 * @author beta
 */
class Authenticate extends Middleware
{
    
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  Request  $request
     *
     * @return null
     */
    protected function redirectTo($request)
    {
        return null;
    }
    
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  Closure  $next
     * @param  null  $guards
     *
     * @return mixed
     */
    public function handle($request, Closure $next, ...$guards): mixed
    {
        foreach ($guards as $guard) {
            if (!Auth::guard($guard)->check()) {
                
                return Preacher::msgCode(
                    Preacher::RESP_CODE_AUTH,
                    '未登录或登录已失效'
                )->export()->json();
                
            }
        }
        
        return $next($request);
    }
    
}
