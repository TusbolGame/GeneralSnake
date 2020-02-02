<?php

namespace App\Http\Middleware;

use Closure;

class Role
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, $roles)
    {
        $user = $request->user();

        foreach (explode('|', $roles) as $role) {
            if ($user->role == $role) {
                return $next($request);
            }
        }
        return response()->json(['error' => 'no privillege'], 500);
    }
}
