<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $availableLocales = ['pl', 'en'];
        
        // Resolve locale in order: request(?lang) -> session -> config default
        $locale = $request->input('lang') 
            ?? $request->session()->get('locale') 
            ?? config('app.locale', 'pl');
        
        // Only allow whitelisted locales
        if (!in_array($locale, $availableLocales)) {
            $locale = config('app.locale', 'pl');
        }
        
        // Set locale for app and Carbon
        app()->setLocale($locale);
        \Carbon\Carbon::setLocale($locale);
        
        // Persist to session if lang parameter provided
        if ($request->has('lang')) {
            $request->session()->put('locale', $locale);
        }
        
        return $next($request);
    }
}
