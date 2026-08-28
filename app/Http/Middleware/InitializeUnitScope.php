<?php

namespace App\Http\Middleware;

use App\Support\UnitScope;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InitializeUnitScope
{
    public function __construct(private UnitScope $unitScope) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && session(UnitScope::SESSION_KEY) === null) {
            $this->unitScope->initializeForUser($user);
        }

        return $next($request);
    }
}
