<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SwaggerAuth
{
    public function handle(Request $request, Closure $next)
    {
        if (!config('l5-swagger.enabled')) {
            abort(404);
        }

        $configUser = config('l5-swagger.swagger_auth.user');
        $configPass = config('l5-swagger.swagger_auth.pass');

        if (!$configUser || !$configPass) {
            abort(403, 'Swagger authentication is not configured.');
        }

        $user = $request->getUser();
        $pass = $request->getPassword();

        if (
            !$user ||
            !$pass ||
            !hash_equals((string) $configUser, (string) $user) ||
            !hash_equals((string) $configPass, (string) $pass)
        ) {
            return response('Unauthorized', 401, [
                'WWW-Authenticate' => 'Basic realm="Swagger"',
            ]);
        }

        return $next($request);
    }
}
