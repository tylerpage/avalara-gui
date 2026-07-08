<?php

namespace App\Http\Middleware;

use App\Services\PasscodeService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequirePasscode
{
    public function __construct(
        private readonly PasscodeService $passcode,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->passcode->isRequired() || $this->passcode->isAuthenticated($request)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Passcode required.'], 401);
        }

        return redirect()->guest(route('passcode.show'));
    }
}
