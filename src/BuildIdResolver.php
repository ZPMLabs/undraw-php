<?php

namespace Undraw;

use Psr\Http\Client\ClientInterface as HttpClient;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\SimpleCache\CacheInterface;
use Undraw\Exceptions\BuildIdResolveException;
use Undraw\Exceptions\HttpException;

final class BuildIdResolver
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly HttpClient $http,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly CacheInterface $cache,
        private readonly int $ttlSeconds = 43200 // 12h
    ) {}

    public function resolve(): string
    {
        $cacheKey = 'undraw_build_id';

        $cached = $this->cache->get($cacheKey);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $req = $this->requestFactory->createRequest('GET', $this->baseUrl);
        try {
            $res = $this->http->sendRequest($req);
        } catch (\Throwable $e) {
            throw new BuildIdResolveException('Failed to fetch undraw homepage', 0, $e);
        }

        $status = $res->getStatusCode();
        if ($status < 200 || $status >= 300) {
            throw new HttpException("Non-2xx when resolving buildId: {$status}", $req, $res);
        }

        $html = (string) $res->getBody();

        if (!preg_match('/"buildId":"([^"]+)"/', $html, $m)) {
            throw new BuildIdResolveException('Could not find buildId in homepage HTML');
        }

        $buildId = $m[1];
        $this->cache->set($cacheKey, $buildId, $this->ttlSeconds);
        return $buildId;
    }

    public function invalidate(): void
    {
        $this->cache->delete('undraw_build_id');
    }
}
