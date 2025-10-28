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
use Undraw\Factory\UndrawFactory;
use Undraw\UndrawClient;
use Undraw\Support\Laravel\LaravelCacheAdapter;

app()->bind(UndrawClient::class, function () {
    return UndrawFactory::create(new LaravelCacheAdapter());
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
