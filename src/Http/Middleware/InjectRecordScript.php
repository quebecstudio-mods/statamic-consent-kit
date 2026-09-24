<?php

namespace QuebecStudioMods\ConsentKit\Statamic\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use QuebecStudioMods\ConsentKit\Core\RecordScript;
use QuebecStudioMods\ConsentKit\Statamic\Registry\Decisions;
use Statamic\Facades\Site;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Adds the script that reports a decision, only where there is a register to
 * report to. It listens for the event the core already emits, so the banner
 * markup is untouched and pages stay as cacheable as before.
 */
class InjectRecordScript
{
    public function __construct(private readonly Decisions $decisions)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (!$this->decisions->isCollecting() || !$this->isHtmlPage($response)) {
            return $response;
        }

        $html = (string)$response->getContent();
        $position = strripos($html, '</body>');

        if ($position === false) {
            return $response;
        }

        $script = '<script>' . RecordScript::build(
            route('statamic.cookie-consent-kit.record'),
            ['site' => Site::current()->handle()],
        ) . '</script>';

        $response->setContent(substr($html, 0, $position) . $script . substr($html, $position));

        return $response;
    }

    private function isHtmlPage(Response $response): bool
    {
        return !$response instanceof StreamedResponse
            && !$response instanceof BinaryFileResponse
            && !$response->isRedirection()
            && str_contains((string)$response->headers->get('Content-Type', 'text/html'), 'text/html');
    }
}
