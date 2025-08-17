<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Domain;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class CheckAllowedDomain
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $excludedPaths = [
            '/',
            'license/validate',
            'license/generate',
            'seed',
            'migrate',
            'optimize-clear',
            'roles-seed',
        ];

        $excludedHosts = [
            'salla.cupun.net',
            // 'localhost',
            // '127.0.0.1', 
        ];

        if ($this->isExcludedPath($request, $excludedPaths) || in_array($request->getHost(), $excludedHosts)) {
            return $next($request);
        }

        $settings = Setting::first();

        if (!$settings) {
            abort(403, 'not allowed');
        }

        $response = Setting::where('key', 'allowed')->where('value', 1)->exists();

        if (!$response) {
            abort(403, 'not allowed');
        }   

        return $next($request);
    }

    protected function isExcludedPath(Request $request, array $excludedPaths): bool
    {
        foreach ($excludedPaths as $path) {
            if ($request->is($path)) {
                return true;
            }
        }
        return false;
    }
}
