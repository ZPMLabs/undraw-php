<?php

namespace Undraw\Factory;

use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Psr\SimpleCache\CacheInterface;
use Undraw\BuildIdResolver;
use Undraw\Cache\ArrayCache;
use Undraw\UndrawClient;

final class UndrawFactory
{
    public static function create(?CacheInterface $cache = null, string $baseUrl = 'https://undraw.co'): UndrawClient
    {
        $http     = Psr18ClientDiscovery::find();
        $reqFact  = Psr17FactoryDiscovery::findRequestFactory();
        $strFact  = Psr17FactoryDiscovery::findStreamFactory();

        $cache ??= new ArrayCache();

        $resolver = new BuildIdResolver($baseUrl, $http, $reqFact, $cache, 60 * 60 * 12);

        return new UndrawClient($http, $reqFact, $strFact, $cache, $resolver);
    }
}
