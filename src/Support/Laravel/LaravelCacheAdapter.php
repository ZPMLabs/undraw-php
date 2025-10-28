<?php

namespace Undraw\Support\Laravel;

use Psr\SimpleCache\CacheInterface;

final class LaravelCacheAdapter implements CacheInterface {
    public function get(string $key, mixed $default = null): mixed {
        return cache()->get($key, $default);
    }

    public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool {
        return cache()->put($key, $value, $ttl);
    }

    public function delete(string $key): bool {
        return cache()->forget($key);
    }

    public function clear(): bool {
        cache()->flush();
        return true;
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable {
        return collect($keys)->mapWithKeys(fn($k) => [$k => cache()->get($k, $default)])->all();
    }
    public function setMultiple(iterable $values, null|int|\DateInterval $ttl = null): bool {
        foreach($values as $k=>$v) cache()->put($k,$v,$ttl); return true; }
    public function deleteMultiple(iterable $keys): bool {
        foreach($keys as $k) cache()->forget($k); return true; }
    public function has(string $key): bool {
        return cache()->has($key);
    }
}
