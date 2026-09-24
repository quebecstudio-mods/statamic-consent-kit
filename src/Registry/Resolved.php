<?php

namespace QuebecStudioMods\ConsentKit\Statamic\Registry;

use QuebecStudioMods\ConsentKit\Core\Resolver;
use QuebecStudioMods\ConsentKit\Core\SiteContext;
use QuebecStudioMods\ConsentKit\Laravel\CookieConsent;
use QuebecStudioMods\ConsentKit\Statamic\Settings\SettingsStore;
use Statamic\Facades\Site;

/**
 * The banner configuration as it would be served for one site.
 *
 * The register recomputes it rather than trusting the report, so a fingerprint
 * attests what the site was showing. Resolved per site because the wording
 * follows the site's locale.
 */
class Resolved
{
    /** @var array<string, array<string, mixed>> */
    private array $cache = [];

    public function __construct(private readonly SettingsStore $settings)
    {
    }

    /** @return array<string, mixed> */
    public function configFor(string $siteHandle): array
    {
        return $this->cache[$siteHandle] ??= $this->resolve($siteHandle);
    }

    /** @return array<string, mixed> */
    private function resolve(string $siteHandle): array
    {
        $site = Site::get($siteHandle);
        $locale = $site === null ? config('app.locale') : $site->shortLocale();

        $resolver = new Resolver(
            $this->settings->all(),
            new SiteContext(1, $siteHandle, str_replace('_', '-', (string)$locale)),
            null,
            CookieConsent::languages(),
        );

        return $resolver->bannerConfig($resolver->policyUrl());
    }
}
