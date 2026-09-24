<?php

namespace QuebecStudioMods\ConsentKit\Statamic\Settings;

use Illuminate\Support\Facades\File;
use QuebecStudioMods\ConsentKit\Core\Registry;
use Statamic\Facades\YAML;

/**
 * Bridges the addon settings screen to the configuration the renderer reads.
 *
 * Three sources, each overriding the one before: the package defaults, the
 * settings saved from the control panel, and the keys the site fixed in
 * `config/cookie-consent.php`.
 */
class SettingsStore
{
    public const PACKAGE = 'quebecstudio-mods/statamic-consent-kit';

    public const SLUG = 'statamic-consent-kit';

    /** @return array<string, mixed> */
    public function all(): array
    {
        return array_merge($this->defaults(), $this->saved(), $this->fixed());
    }

    /** Puts the resolved settings where `laravel-consent-kit` reads them. */
    public function apply(): void
    {
        config()->set('cookie-consent', $this->all());
    }

    /** The settings screen cannot override what the site fixed in its config file. */
    public function isFixed(string $key): bool
    {
        return array_key_exists($key, $this->fixed());
    }

    /** @return array<string, bool> */
    public function fixedKeys(): array
    {
        return array_map(static fn () => true, $this->fixed());
    }

    /**
     * Read from the file Statamic writes, not through `Addon::get()`: the
     * addon repository boots the addons, and this runs while booting.
     *
     * @return array<string, mixed>
     */
    public function saved(): array
    {
        $path = resource_path('addons/' . self::SLUG . '.yaml');

        if (!is_file($path)) {
            return [];
        }

        return array_filter(
            (array)YAML::parse(File::get($path)),
            static fn (mixed $value) => $value !== null && $value !== '' && $value !== [],
        );
    }

    /**
     * Read on its own rather than through `config()`, which has already merged
     * the package defaults in and could no longer tell them apart.
     *
     * @return array<string, mixed>
     */
    private function fixed(): array
    {
        $path = config_path('cookie-consent.php');

        return is_file($path) ? (array)require $path : [];
    }

    /**
     * The package defaults, already merged with the site's file by Laravel.
     * Putting `fixed()` back last makes the merge idempotent, so applying it
     * twice cannot drift.
     *
     * @return array<string, mixed>
     */
    private function defaults(): array
    {
        return array_merge(Registry::DEFAULTS, (array)config('cookie-consent', []));
    }
}
