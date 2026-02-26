<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class LogUserActivity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only log for authenticated users
        if (Auth::check()) {
            $method = $request->method();
            $path = $request->path();

            // Skip high-frequency internal requests to keep UI snappy.
            if (
                $request->is('livewire/*') ||
                $request->expectsJson() ||
                $request->ajax() ||
                $request->is('up') ||
                $request->is('login') ||
                $request->is('logout') ||
                $request->is('admin') ||
                $request->is('admin/dashboard') ||
                $request->is('home')
            ) {
                return $response;
            }

            // Reduce DB write load: only log data-changing requests.
            if ($method === 'GET') {
                return $response;
            }
            
            // Skip logging for debugbar, horizon, telescope if any, or assets
            $action = match ($method) {
                'GET' => 'Visited Page',
                'POST' => 'Form Submission',
                'PUT', 'PATCH' => 'Updated Data',
                'DELETE' => 'Deleted Data',
                default => 'Action'
            };

            // Enhance description for distinctiveness
            $description = "$method /" . $path;

            try {
                ActivityLog::record($action, $description);
            } catch (\Throwable $e) {
                // Never block user flow because audit log insert fails.
            }
        }

        return $response;
    }
}
