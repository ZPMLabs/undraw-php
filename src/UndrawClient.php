<?php

namespace Undraw;

use Psr\Http\Client\ClientInterface as HttpClient;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\SimpleCache\CacheInterface;
use Undraw\DTO\Illustration;
use Undraw\Exceptions\HttpException;

final class UndrawClient
{
    private string $base = 'https://undraw.co';

    public function __construct(
        private readonly HttpClient $http,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly CacheInterface $cache,
        private readonly ?BuildIdResolver $resolver = null,
        private readonly int $searchTtlSeconds = 21600 // 6h
    ) {}

    /**
     * Search undraw by term. Returns array of Illustration DTOs.
     */
    public function search(string $term, int $limit = 20): array
    {
        $term = trim($term);
        if ($term === '') {
            return [];
        }

        $buildId = $this->resolver?->resolve() ?? $this->resolveBuildIdFallback();

        $url = sprintf(
            '%s/_next/data/%s/search/%s.json?term=%s',
            $this->base,
            rawurlencode($buildId),
            rawurlencode($term),
            rawurlencode($term)
        );

        $cacheKey = "undraw:search:$buildId:" . md5($url);
        $cached = $this->cache->get($cacheKey);
        if (is_array($cached)) {
            return $this->hydrateIllustrations($cached, $limit);
        }

        $req = $this->requestFactory->createRequest('GET', $url);
        $res = $this->http->sendRequest($req);

        // On 404, invalidate buildId and retry once
        if ($res->getStatusCode() === 404 && $this->resolver) {
            $this->resolver->invalidate();
            $buildId = $this->resolver->resolve();
            $url = sprintf(
                '%s/_next/data/%s/search/%s.json?term=%s',
                $this->base,
                rawurlencode($buildId),
                rawurlencode($term),
                rawurlencode($term)
            );
            $req = $this->requestFactory->createRequest('GET', $url);
            $res = $this->http->sendRequest($req);
        }

        $code = $res->getStatusCode();
        if ($code < 200 || $code >= 300) {
            throw new HttpException("Non-2xx from undraw search: {$code}", $req, $res);
        }

        $json = (string) $res->getBody();
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        $items = $data['pageProps']['initialResults'] ?? [];
        $this->cache->set($cacheKey, $items, $this->searchTtlSeconds);

        return $this->hydrateIllustrations($items, $limit);
    }

    /**
     * Fetch SVG code by Illustration DTO or direct media URL.
     */
    public function getSvg(Illustration|string $illustrationOrUrl): string
    {
        $url = $illustrationOrUrl instanceof Illustration
            ? $illustrationOrUrl->mediaUrl
            : $illustrationOrUrl;

        $req = $this->requestFactory->createRequest('GET', $url);
        $res = $this->http->sendRequest($req);

        $code = $res->getStatusCode();
        if ($code < 200 || $code >= 300) {
            throw new HttpException("Non-2xx when fetching SVG: {$code}", $req, $res);
        }

        $ct = $res->getHeaderLine('Content-Type');
        // Some CDNs send text/plain; just return the body anyway.
        if (!str_contains(strtolower($ct), 'svg')) {
            // Not fatal; you may decide to throw. We'll allow.
        }

        return (string) $res->getBody();
    }

    private function hydrateIllustrations(array $items, int $limit): array
    {
        $out = [];
        foreach ($items as $it) {
            $id    = (string)($it['_id'] ?? '');
            $title = (string)($it['title'] ?? '');
            $slug  = (string)($it['newSlug'] ?? '');
            $media = (string)($it['media'] ?? '');

            if ($id !== '' && $title !== '' && $media !== '') {
                $out[] = new Illustration($id, $title, $slug, $media);
                if (count($out) >= $limit) break;
            }
        }
        return $out;
    }

    private function resolveBuildIdFallback(): string
    {
        // Minimal fallback (no cache invalidation). Prefer providing BuildIdResolver.
        $req = $this->requestFactory->createRequest('GET', $this->base);
        $res = $this->http->sendRequest($req);
        $code = $res->getStatusCode();
        if ($code < 200 || $code >= 300) {
            throw new HttpException("Non-2xx fetching undraw homepage: {$code}", $req, $res);
        }
        $html = (string) $res->getBody();
        if (!preg_match('/"buildId":"([^"]+)"/', $html, $m)) {
            throw new \RuntimeException('Could not resolve undraw buildId (fallback)');
        }
        return $m[1];
    }
}
