<?php

namespace Undraw\Cache;

use Psr\SimpleCache\CacheInterface;
use DateInterval;

class ArrayCache implements CacheInterface
{
    /** @var array<string, array{v:mixed, e:int|null}> */
    private array $store = [];

    public function get($key, $default = null)
    {
        $this->assertKey($key);
        if (!isset($this->store[$key])) {
            return $default;
        }
        $e = $this->store[$key]['e'];
        if ($e !== null && $e < time()) {
            unset($this->store[$key]);
            return $default;
        }
        return $this->store[$key]['v'];
    }

    public function set($key, $value, $ttl = null): bool
    {
        $this->assertKey($key);
        $expires = null;
        if ($ttl instanceof DateInterval) {
            $ttl = (new \DateTimeImmutable())->add($ttl)->getTimestamp() - time();
        }
        if (is_int($ttl)) {
            $expires = time() + $ttl;
        } elseif ($ttl === null) {
            $expires = null;
        }
        $this->store[$key] = ['v' => $value, 'e' => $expires];
        return true;
    }

    public function delete($key): bool
    {
        $this->assertKey($key);
        unset($this->store[$key]);
        return true;
    }

    public function clear(): bool
    {
        $this->store = [];
        return true;
    }

    public function getMultiple($keys, $default = null): iterable
    {
        $out = [];
        foreach ($keys as $k) {
            $out[$k] = $this->get($k, $default);
        }
        return $out;
    }

    public function setMultiple($values, $ttl = null): bool
    {
        foreach ($values as $k => $v) {
            $this->set($k, $v, $ttl);
        }
        return true;
    }

    public function deleteMultiple($keys): bool
    {
        foreach ($keys as $k) {
            $this->delete($k);
        }
        return true;
    }

    public function has($key): bool
    {
        $this->assertKey($key);
        if (!isset($this->store[$key])) return false;
        $e = $this->store[$key]['e'];
        if ($e !== null && $e < time()) {
            unset($this->store[$key]);
            return false;
        }
        return true;
    }

    private function assertKey($key): void
    {
        if (!is_string($key) || $key === '') {
            throw new \InvalidArgumentException('Invalid cache key');
        }
    }
}
