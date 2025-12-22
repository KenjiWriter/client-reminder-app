<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class DashboardCache
{
    public static function clear()
    {
        // For simple drivers like 'file' or 'database' that don't support tags,
        // we can't easily clear by wildcard. 
        // For MVP, we'll use a cache key versioning or a simple 'last_updated' key
        // to invalidate the cache.
        
        Cache::increment('dashboard_cache_version');
    }

    public static function getVersion()
    {
        return Cache::get('dashboard_cache_version', 1);
    }
}
