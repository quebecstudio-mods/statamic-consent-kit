# Cookie Consent Kit for Statamic — `quebecstudio-mods/statamic-consent-kit`

Cookie consent banner for Statamic 6, built for Quebec's Law 25 and usable
under the GDPR.

Statamic is a Laravel application, so the addon is powered by
[`quebecstudio-mods/laravel-consent-kit`](https://github.com/quebecstudio-mods/laravel-consent-kit),
which injects the banner, and by `quebecstudio-mods/consent-kit-core`, which
renders it. The addon adds the Antlers tags, the control panel screens and the
consent register.


- No third-party cookie is set before consent.
- The HTML is the same for every visitor; pages stay cacheable, including under
  static caching.
- English and French included; a site adds any language with one file.
- YouTube videos load on click, with the thumbnail served by the site.

## Installation

Requires PHP 8.2 and Statamic 6.

```bash
composer require quebecstudio-mods/statamic-consent-kit
php artisan migrate
```

The addon is discovered automatically. The banner is added to every HTML page
Statamic serves; the control panel is untouched.

Settings live under **Settings › Cookie Consent Kit**. Publishing
`config/cookie-consent.php` is optional and pins whatever it declares: those
settings then show as read-only in the panel.

## Editions

| Edition | What it adds |
|---|---|
| Standard | The banner, the cookie inventory, the video facade |
| Pro | The consent register: server-side proof of what was shown and answered |

Set the edition in `config/statamic/editions.php`:

```php
'addons' => [
    'quebecstudio-mods/statamic-consent-kit' => 'pro',
],
```

## In templates

The banner is injected on every page, so a template needs nothing. With
`autoInject` off, place it yourself — where the tag sits does not matter, only
that it is called once:

```antlers
{{ consent:banner }}
{{ consent:cookie_table }}
{{ consent:cookie_table category="statistics" heading_level="2" }}
{{ consent:video_facade id="dQw4w9WgXcQ" title="A video" }}
```

A Blade site uses `@cookieConsentBanner` and `@withCookieTable` instead. Both go
through the same facade.

## The consent register

With the Pro edition and the register turned on, every decision is recorded as
the browser makes it: the server clock, the site, the categories answered, and
a fingerprint of the wording that was on screen. The cookie's own timestamp
lives on the visitor's device and proves nothing.

Records are read under **Tools › Consentements**, filtered by date and answer,
and exported as CSV or JSON. An export carries the wording of every screen, so
a reader can recompute each fingerprint and check that nothing moved.

The register uses the site's database. Name another connection to keep the
proofs apart:

```php
// config/cookie-consent.php
'register' => ['connection' => 'proofs'],
```

Permissions: **View the consent register**, with **Export** and **Purge**
nested under it. Retention removes outlived records; **Tools › Utilities ›
Consent Purge** deletes them on demand.

## Tailwind

`inventoryFramework => 'tailwind'` styles the cookie inventory with Tailwind
classes. They come from the package, which Tailwind does not scan, so add it to
the sources:

```css
@source "../../vendor/quebecstudio-mods/consent-kit-core/src/Defaults.php";
```

Without it the inventory is styled only where the site happens to use the same
utilities elsewhere.

## Documentation

[DOCUMENTATION.md](DOCUMENTATION.md) covers the settings, the tags, the cookie
inventory, the control panel and the consent register.

## Tests

```bash
composer install
vendor/bin/pest
```

## Licence

Proprietary. See [LICENSE.md](LICENSE.md).
