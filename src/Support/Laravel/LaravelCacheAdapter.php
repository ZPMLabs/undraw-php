<?php

namespace Support\Laravel;

use Psr\SimpleCache\CacheInterface;

final class LaravelCacheAdapter implements CacheInterface {
    public function get($k, $d=null){ return cache()->get($k, $d); }
    public function set($k,$v,$t=null){ return cache()->put($k,$v,$t); }
    public function delete($k){ return cache()->forget($k); }
    public function clear(){ cache()->flush(); return true; }
    public function getMultiple($keys,$d=null){ return collect($keys)->mapWithKeys(fn($k)=>[$k=>cache()->get($k,$d)])->all(); }
    public function setMultiple($values,$t=null){ foreach($values as $k=>$v) cache()->put($k,$v,$t); return true; }
    public function deleteMultiple($keys){ foreach($keys as $k) cache()->forget($k); return true; }
    public function has($k){ return cache()->has($k); }
}
