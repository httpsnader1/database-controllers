<?php

namespace Httpsnader1\DatabaseControllers\Middleware;

use Closure;
use Illuminate\Http\Request;

class DatabaseControllersAuth
{
    public function handle(Request $request, Closure $next)
    {
        $password = config('database-controllers.password');
        
        // If password is not set, no auth is needed
        if (empty($password)) {
            return $next($request);
        }

        // If the user is already authenticated via session
        if ($request->session()->get('db_controller_auth') === true) {
            return $next($request);
        }

        // If trying to access login routes
        if ($request->routeIs('database-controllers.login') || $request->routeIs('database-controllers.login.post')) {
            return $next($request);
        }

        return redirect()->route('database-controllers.login');
    }
}
