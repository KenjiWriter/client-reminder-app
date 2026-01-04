<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackPageVisits
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Exclude asset paths and other non-page requests
        if ($request->is('build/*', 'storage/*', 'favicon.ico', 'robots.txt')) {
            return $next($request);
        }

        // Only track GET requests
        if ($request->isMethod('GET')) {
            try {
                // Try to extract client_id from public route parameter
                $clientId = null;
                if ($request->route('publicUid')) {
                    $client = \App\Models\Client::where('public_uid', $request->route('publicUid'))->first();
                    $clientId = $client?->id;
                }

                \App\Models\SiteVisit::create([
                    'ip_address' => $request->ip(),
                    'user_id' => $request->user()?->id,
                    'client_id' => $clientId,
                    'url' => $request->fullUrl(),
                    'user_agent' => substr($request->userAgent() ?? '', 0, 255),
                ]);
            } catch (\Exception $e) {
                // Fail silently to not impact user experience
                report($e);
            }
        }

        return $next($request);
    }
}
