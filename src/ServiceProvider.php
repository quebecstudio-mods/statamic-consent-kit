<?php

namespace QuebecStudioMods\ConsentKit\Statamic;

use Illuminate\Support\Facades\Schedule;
use QuebecStudioMods\ConsentKit\Core\Paths;
use QuebecStudioMods\ConsentKit\Register\Connection;
use QuebecStudioMods\ConsentKit\Statamic\Console\PurgeRegistry;
use QuebecStudioMods\ConsentKit\Statamic\Http\Middleware\InjectRecordScript;
use QuebecStudioMods\ConsentKit\Statamic\Query\Filters;
use QuebecStudioMods\ConsentKit\Statamic\Registry\Decisions;
use QuebecStudioMods\ConsentKit\Statamic\Settings\SettingsBlueprint;
use QuebecStudioMods\ConsentKit\Statamic\Settings\SettingsStore;
use QuebecStudioMods\ConsentKit\Statamic\Tags\ConsentTags;
use QuebecStudioMods\ConsentKit\Statamic\Utilities\RegistryPurge;
use Statamic\CP\Navigation\Nav as Navigation;
use Statamic\Facades\Addon;
use Statamic\Facades\CP\Nav;
use Statamic\Facades\Permission;
use Statamic\Facades\Utility;
use Statamic\Providers\AddonServiceProvider;

/**
 * Registers the addon with Statamic. The banner is injected by
 * `laravel-consent-kit`, whose middleware runs on Statamic's `web` group.
 */
class ServiceProvider extends AddonServiceProvider
{
    protected $tags = [
        ConsentTags::class,
    ];

    protected $commands = [
        PurgeRegistry::class,
    ];

    /** The register listing filters, declared the way Statamic declares its own. */
    protected $scopes = [
        Filters\DecidedAt::class,
        Filters\Outcome::class,
        Filters\Site::class,
    ];

    protected $routes = [
        'actions' => __DIR__ . '/../routes/actions.php',
        'cp' => __DIR__ . '/../routes/cp.php',
    ];

    /** Shipped compiled: Composer runs no npm. */
    protected $vite = [
        'input' => ['resources/js/addon.js', 'resources/css/addon.css'],
        'publicDirectory' => 'resources/dist',
    ];

    /** After the package middleware, so the banner is already on the page. */
    protected $middlewareGroups = [
        'web' => [InjectRecordScript::class],
    ];

    public function register(): void
    {
        parent::register();

        $this->loadJsonTranslationsFrom(Paths::cpLang());

        $this->app->singleton(Connection::class);
    }

    public function bootAddon(): void
    {

        $this->loadMigrationsFrom(Connection::migrations());

        Schedule::command(PurgeRegistry::class)->daily();
        $this->registerPermissions();

        Utility::extend(fn () => RegistryPurge::register(self::icon()));

        $store = app(SettingsStore::class);

        $this->registerSettingsBlueprint(fn () => SettingsBlueprint::contents(
            $store->all(),
            $store->fixedKeys(),
            Edition::isPro(),
        ));

        $store->apply();

        Nav::extend(function (Navigation $nav): void {
            $addon = Addon::get(SettingsStore::PACKAGE);

            if ($addon === null) {
                return;
            }

            /** @var string $name Fluent getter and setter, so typed as both. */
            $name = $addon->name();

            $nav->remove('Tools', 'Addons', $name);
            $nav->remove('Tools', $name);

            $nav->create($name)
                ->section('Settings')
                ->url($addon->settingsUrl())
                ->icon(self::icon())
                ->can('editSettings', $addon);

            if (app(Decisions::class)->isVisible()) {
                $nav->create(__('Consent'))
                    ->section('Tools')
                    ->route('cookie-consent-kit.registry.index')
                    ->icon(self::icon())
                    ->can('view consent register');
            }
        });
    }

    /** Export and purge nest under the permission to view. */
    private function registerPermissions(): void
    {
        Permission::extend(function (): void {
            Permission::group('cookie_consent_kit', 'Cookie Consent Kit', function (): void {
                Permission::register('view consent register')
                    ->label(__('View the consent register'))
                    ->children([
                        Permission::make('export consent register')->label(__('Export the consent register')),
                        Permission::make('purge consent register')->label(__('Purge the consent register')),
                    ]);
            });
        });
    }

    /** The suite's mark, monochrome so the panel can colour it. */
    public static function icon(): string
    {
        return (string)file_get_contents(Paths::icon());
    }
}
