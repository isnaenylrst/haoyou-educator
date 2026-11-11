<?php

namespace App\Http\Middleware;

use App\Models\OwnerActionLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogOwnerAction
{
    /**
     * Catat setiap aksi Owner di fitur role lain (Admin, Kurikulum, Guru).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $user = Auth::user();

        // Hanya catat jika user adalah Owner
        if ($user && ($user->level?->nama_level === 'Owner')) {
            $path = $request->path();
            $area = null;

            if (str_starts_with($path, 'admin')) {
                $area = 'Admin';
            } elseif (str_starts_with($path, 'kurikulum')) {
                $area = 'Curriculum';
            } elseif (str_starts_with($path, 'guru')) {
                $area = 'Teacher';
            }

            // Catat jika aksi berada di area peran lain
            if ($area) {
                $method = $request->method();
                $url = $request->fullUrl();
                $routeAction = $request->route()?->getName() ?: ($request->route()?->getActionName() ?: $path);

                $details = [
                    'route'      => $routeAction,
                    'status'     => $response->getStatusCode(),
                    'parameters' => $request->except(['password', 'password_confirmation', '_token']),
                ];

                try {
                    OwnerActionLog::create([
                        'user_id'    => $user->id,
                        'role_area'  => $area,
                        'action'     => "{$method} [{$area}] {$routeAction}",
                        'method'     => $method,
                        'url'        => mb_substr($url, 0, 500),
                        'details'    => json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        'ip_address' => $request->ip(),
                        'created_at' => now(),
                    ]);
                } catch (\Throwable $e) {
                    Log::warning("Gagal menyimpan OwnerActionLog: " . $e->getMessage());
                }

                Log::info("Owner Action: User #{$user->id} ({$user->username}) mengakses {$area} via {$method} {$url}");
            }
        }

        return $response;
    }
}

