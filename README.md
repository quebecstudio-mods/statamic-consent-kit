# Cookie Consent Kit for Statamic

Cookie consent banner for Statamic 6, built for Quebec's Law 25 and usable
under the GDPR.

**No third-party cookie is set before the visitor agrees**, and the markup is
the same for every visitor, so pages stay cacheable — static caching included.

## What it does

- **A banner with the categories the site declares**, answered by accepting
  everything, refusing everything, or choosing one category at a time.
- **A cookie inventory** the site fills in the control panel, rendered as a
  table in the privacy policy page, styled by the site's own CSS framework.
- **YouTube videos load on click.** Nothing reaches Google before that, and the
  thumbnail is served by the site, not fetched from YouTube.
- **Any script or iframe waits for its category** — mark it and the addon
  activates it when consent arrives.
- **Google Consent Mode and Matomo** are primed refused before any tag runs,
  and updated the moment the visitor answers.
- **Global Privacy Control is honoured**, and the banner can be skipped for
  visitors who send it.
- **Settings in the control panel**, on Statamic's own settings screen. A site
  that keeps its configuration in version control can pin any of them.
- **English and French included.** A site adds a language with one file, or
  overrides a single sentence.
- **Templates are yours.** Override the banner, the table or the video facade
  in Antlers or Blade.

### With the Pro edition

- **A consent register**: every decision recorded server-side with the server's
  clock, the site, the categories granted, and a fingerprint of the exact
  wording that was on screen. The cookie's own timestamp lives on the visitor's
  device and proves nothing.
- **Read it in the control panel**, filtered by date, answer and site, with the
  screen each decision was made on shown as it was worded then.
- **Export to CSV or JSON.** The export carries the wording of every screen, so
  a third party can recompute each fingerprint and check that nothing moved.
- **Retention and a purge utility**, with three permissions — viewing,
  exporting and purging — so producing a proof is not the same trust as
  destroying one.

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

Records are read under **Tools › Consent**, filtered by date and answer,
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

## How it is built

Statamic is a Laravel application, so the banner is injected by
[`quebecstudio-mods/laravel-consent-kit`](https://github.com/quebecstudio-mods/laravel-consent-kit)
and rendered by `quebecstudio-mods/consent-kit-core`; the register is kept by
`quebecstudio-mods/consent-kit-register`. This addon adds the Antlers tags, the
control panel screens and the register's own.

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
