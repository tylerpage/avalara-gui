<?php

namespace App\Http\Middleware;

use App\Services\DashboardContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDashboard
{
    public function __construct(
        private readonly DashboardContext $dashboards,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->dashboards->current();

        return $next($request);
    }
}
