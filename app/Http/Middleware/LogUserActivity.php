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
                $request->is('up')
            ) {
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

            ActivityLog::record($action, $description);
        }

        return $response;
    }
}
