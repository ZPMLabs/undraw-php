# undraw-php

Lightweight client for **undraw.co** search (using Next.js data routes).  
Returns typed DTOs and lets you fetch **SVG code**.

## Install

```bash
composer require ZPMLabs/undraw-php
# You also need a PSR-18 client & PSR-17 factories (Discovery will find them).
# Example:
composer require guzzlehttp/guzzle nyholm/psr7
```

## Usage

```php
use Undraw\Factory\UndrawFactory;

$client = UndrawFactory::create(); // in-memory cache by default

$results = $client->search('music', 10); // array of Undraw\DTO\Illustration

foreach ($results as $i) {
    echo $i->title . ' => ' . $i->mediaUrl . PHP_EOL;
    $svg = $client->getSvg($i); // raw SVG string
}
```

## With Laravel

```php
use Undraw\UndrawClient;
use Undraw\BuildIdResolver;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;

app()->bind(UndrawClient::class, function () {
    $http = Psr18ClientDiscovery::find();
    $req  = Psr17FactoryDiscovery::findRequestFactory();
    $str  = Psr17FactoryDiscovery::findStreamFactory();
    $cache = new class implements \Psr\SimpleCache\CacheInterface {
        public function get($key, $default = null){ return cache()->get($key, $default); }
        public function set($key, $value, $ttl = null){ return cache()->put($key, $value, $ttl); }
        public function delete($key){ return cache()->forget($key); }
        public function clear(){ cache()->flush(); return true; }
        public function getMultiple($keys, $default = null){ return collect($keys)->mapWithKeys(fn($k)=>[$k=>cache()->get($k, $default)])->all(); }
        public function setMultiple($values, $ttl = null){ foreach($values as $k=>$v) cache()->put($k,$v,$ttl); return true; }
        public function deleteMultiple($keys){ foreach($keys as $k) cache()->forget($k); return true; }
        public function has($key){ return cache()->has($key); }
    };

    $resolver = new BuildIdResolver('https://undraw.co', $http, $req, $cache);
    return new UndrawClient($http, $req, $str, $cache, $resolver);
});
```

## With Filament

```php
    Select::make('undraw_media')
        ->searchable()
        ->getSearchResultsUsing(function (string $search) {
            /** @var \Undraw\UndrawClient $u */
            $u = app(\Undraw\UndrawClient::class);
            return collect($u->search($search, 20))
                ->mapWithKeys(fn($i) => [$i->mediaUrl => $i->title])
                ->all();
        })
        ->hint('Search undraw…');
```
