<?php

namespace App\Services\Analytics\Traits;

use Illuminate\Support\Facades\Cache;

trait Cacheable
{
    protected function remember(string $key, int $ttl, callable $callback)
    {
        return Cache::remember($key, $ttl, $callback);
    }
    
    protected function forget(string $key): void
    {
        Cache::forget($key);
    }
    
    protected function forgetByPattern(string $pattern): void
    {
        $keys = Cache::getRedis()->keys($pattern);
        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }
    
    protected function getCacheKey(string $prefix, ...$parts): string
    {
        return self::CACHE_PREFIX . $prefix . '_' . implode('_', $parts);
    }
}