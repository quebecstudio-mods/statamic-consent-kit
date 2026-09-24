<?php

namespace QuebecStudioMods\ConsentKit\Statamic\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use QuebecStudioMods\ConsentKit\Statamic\Registry\Decisions;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Receives one reported decision.
 *
 * No CSRF token: it would live in the HTML the banner keeps identical so pages
 * stay cacheable. The guards below stand in for it.
 */
class RecordController
{
    private const MAX_BYTES = 4096;

    private const RATE_LIMIT = 20;

    private const RATE_WINDOW = 60;

    public function __construct(private readonly Decisions $decisions)
    {
    }

    public function __invoke(Request $request): Response
    {

        $accepted = response('', 202);

        if (!$this->decisions->isCollecting() || $this->isOverRate($request)) {
            return $accepted;
        }

        $body = $request->getContent();

        if ($body === '' || strlen($body) > self::MAX_BYTES) {
            return $accepted;
        }

        try {
            $payload = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return $accepted;
        }

        if (!is_array($payload)) {
            return $accepted;
        }

        try {
            $this->decisions->record(
                (string)($payload['site'] ?? ''),
                (string)($payload['action'] ?? ''),
                (string)($payload['origin'] ?? ''),
                is_array($payload['categories'] ?? null) ? $payload['categories'] : [],
            );
        } catch (Throwable $e) {

            report($e);
        }

        return $accepted;
    }

    private function isOverRate(Request $request): bool
    {
        $ip = $request->ip();

        if ($ip === null) {
            return false;
        }

        $key = 'cookie-consent-kit:registry:' . sha1($ip);
        $count = (int)Cache::get($key, 0);

        if ($count >= self::RATE_LIMIT) {
            return true;
        }

        Cache::put($key, $count + 1, self::RATE_WINDOW);

        return false;
    }
}
