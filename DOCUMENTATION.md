# Cookie Consent Kit for Statamic

Cookie consent banner for Statamic 6, built for Quebec's Law 25 and usable
under the GDPR.

The addon is one layer over three packages: `consent-kit-core` renders the
markup and resolves the settings, `laravel-consent-kit` injects the banner and
holds the configuration, `consent-kit-register` keeps the consent register.
Statamic being a Laravel application, that middleware runs on Statamic's `web`
group unchanged. This addon adds the Antlers tags, the control panel and the
register's screens.

## Installation

Requires PHP 8.2 and Statamic 6.

```bash
composer require quebecstudio-mods/statamic-consent-kit
php please vendor:publish --tag=cookie-consent-config
```

The addon is discovered automatically. `consent.css` and `consent.js` are
published to `public/vendor/cookie-consent/` and republished on every
`composer update`.

## What gets added to a page

Every HTML response Statamic serves through the `web` group receives:

| Position | Markup |
|---|---|
| first in `<head>` | the inline bootstrap: reads the consent cookie, primes Matomo and Google Consent Mode in a refused state |
| end of `<head>` | `consent.css` |
| end of `<body>` | the `<qsm-consent-kit>` banner (when `autoInject` is on) and `consent.js` |

JSON, redirects, file and streamed responses are left untouched, and so is the
control panel: its routes are not in the `web` group.

The markup is identical for every visitor, so pages stay cacheable. Statamic's
`half` static caching keeps the banner.

## Tags

### `{{ consent:banner }}`

The `<qsm-consent-kit>` element. Only needed with `autoInject` off.

The element renders where the tag sits, but its panel is `position: fixed`, so
nothing appears at that spot in the flow. **Where the tag sits in a template
does not matter** — only that it is called once. A second call in the same
request outputs an empty string.

### `{{ consent:cookie_table }}`

The declared cookies, one table per category.

| Parameter | Default | What it does |
|---|---|---|
| `category` | all | Handles to show, comma separated. An unknown handle outputs an empty string |
| `heading` | `true` | Whether each category shows its name and description |
| `heading_level` | `3` | Heading level, 2 to 6 |
| `class_<element>` | from the settings | Classes for one element, added to the shipped ones |

The stylable elements are `wrapper`, `section`, `heading`, `description`,
`table`, `thead`, `tbody`, `tr`, `th`, `td`:

```antlers
{{ consent:cookie_table
    category="necessary"
    heading_level="4"
    class_table="w-full text-left text-sm"
    class_th="px-4 py-2" }}
```

There is one parameter per element rather than a single `classes` one: an
Antlers parameter is a string, and the settings hold classes keyed by element.

### `{{ consent:video_facade }}`

A YouTube video that loads on click, so YouTube sets no cookie before consent.

| Parameter | Default | What it does |
|---|---|---|
| `id` | — | The YouTube id; `youtube_id` is accepted too. Without it, nothing is output |
| `title` | — | The title shown on the facade and given to the iframe |
| `poster` | the YouTube thumbnail | A poster image URL of your own |

With `videoFacade` off in the settings, the video is embedded directly.

The thumbnail is served by the site, from the route
`/cookie-consent/thumbnail?v=<id>`, outside the `web` group so an image request
sets no session cookie, and cached in `storage/app/cookie-consent-thumbnails/`.
## Editions

`standard` collects and honours consent. `pro` adds the consent register.

The edition is what the site declares in `config/statamic/editions.php`:

```php
return [
    'quebecstudio-mods/statamic-consent-kit' => 'pro',
];
```

The gate falls on collecting, never on reading: a register already kept stays
readable whatever the licence says.

## Control panel

### Settings

**Addons ▸ Cookie Consent Kit ▸ Settings**, the native addon settings screen.
It writes `resources/addons/statamic-consent-kit.yaml`.

Three sources, each overriding the one before: the package defaults, what the
screen saved, and the keys `config/cookie-consent.php` fixes. A key fixed in
the file is shown on the screen but cannot be changed there — a site that keeps
its configuration in version control stays in charge.

### Consent register

**Consent** in the navigation, on the Pro edition. One row per answer: when,
which site, which user, what was pressed, where from, and the outcome.

Filter by period, outcome and site; sort on any of them; choose which columns
show. Opening a row shows the exact wording the banner was displaying, replayed
from its fingerprint, so a record proves what was consented to and not merely
that something was.

Export the filtered listing as CSV or JSON.

### Consent purge

**Utilities ▸ Consent purge**, for deleting records older than a chosen age,
beside the retention that removes outlived ones on its own.

### Permissions

| Permission | What it opens |
|---|---|
| `view consent register` | The register and a record |
| `export consent register` | The CSV and JSON exports |
| `purge consent register` | The purge utility |

Exporting and purging are children of viewing: neither is granted on its own.

## The consent register

Off by default: keeping a register is something a site announces in its privacy
policy, not something an update starts doing. Turn it on under **Register** in
the settings.

Each answer is recorded with the fingerprint of the screen it was given on — a
SHA-256 over the banner's canonical wording, computed by the server, never
taken from the request. Screens are stored once and shared by every decision
made on them.

| Setting | Default | What it does |
|---|---|---|
| `registry` | `false` | Whether answers are recorded |
| `registryUser` | `true` | Records the signed-in user's id |
| `registryRequestContext` | `false` | Records the IP address and user agent |
| `registryGrace` | `12` | Months kept past the consent cookie's life; `0` keeps everything until a purge by hand |

### Retention

`registryGrace` months past the consent cookie's own life, after which a record
has outlived what it attests. `cookie-consent:purge-registry` deletes those
records and the screens nothing cites any more; the addon schedules it daily.

It therefore needs the site's scheduler to be running, which Statamic already
asks for:

```
* * * * * cd /path/to/site && php artisan schedule:run >> /dev/null 2>&1
```

Without it, nothing expires on its own — purge from **Utilities ▸ Consent
purge**, or run the command by hand.

### Which database

Resolved in order: `cookie-consent.register.connection` when the site names
one, then the site's own default connection, then a SQLite file in
`storage/cookie-consent/register.sqlite` that the addon declares and migrates
itself.

On the site's own connection, `php artisan migrate` creates the two tables.

## Configuration

`config/cookie-consent.php` is shared with the Laravel package, which documents
every key: cookie name and lifetime, consent version, policy URL, default
language, auto-injection, colour scheme, backdrop, display mode, reopen tab,
Global Privacy Control, analytics and marketing categories, video facade, and
the cookie inventory.

### Wording

The banner speaks the site locale: the exact locale, then its base language,
then `defaultLanguage`. English and French are shipped.

`lang/vendor/cookie-consent/<language>.php` replaces strings or adds a language,
and holds only the keys it changes:

```php
<?php

return [
    'texts' => ['accept' => 'J’accepte'],
];
```

```bash
php please vendor:publish --tag=cookie-consent-lang
```

copies the shipped files as a starting point. A published file keeps every key,
so shipped wording updates no longer reach it; keep only the keys you change.

### Cookie inventory

`categories` in the configuration declares the categories and their cookies. A
category with no declared cookie is not shown. Wording is a string, or keyed by
locale:

```php
'categories' => [
    'necessary' => [
        'required' => true,
        'label' => ['en' => 'Necessary', 'fr' => 'Nécessaires'],
        'cookies' => [
            'session' => [
                'name' => 'statamic-session',
                'purpose' => ['en' => 'Keeps your browsing session.'],
                'duration' => ['en' => 'Session'],
            ],
        ],
    ],
],
```

## Styling

The banner carries its own CSS and needs no framework.

The cookie inventory carries none: it sits inside a policy page and inherits
from the site. `inventoryFramework` offers `bootstrap`, `bulma`, `tailwind` or
`custom` presets.

With Tailwind, the preset classes come from the package, which Tailwind does not
scan. Add it to the sources, or the inventory is styled only where the site
happens to use the same utilities elsewhere:

```css
@source "../../vendor/quebecstudio-mods/consent-kit-core/src/Defaults.php";
```

## Overriding templates

`resources/views/vendor/cookie-consent/<name>` replaces a shipped template:
`banner`, `cookie-table`, `video-facade`, `video-embed`.

Write the override in either engine — `banner.antlers.html` and
`banner.blade.php` both work, so a site keeps the one it uses.

```bash
php please vendor:publish --tag=cookie-consent-views
```

The shipped templates are Blade, which Statamic renders natively. Keep the
`data-qsm-ck-*` attributes: the script finds every control through them.

## Front end

The JavaScript API, conditional tags and integration recipes are the same as for
the Craft CMS plugin; see its documentation.

## Licence

Proprietary.
