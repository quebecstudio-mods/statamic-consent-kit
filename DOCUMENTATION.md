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
php artisan migrate
```

The addon is discovered automatically, and everything is set from the control
panel. `consent.css` and `consent.js` are published to
`public/vendor/cookie-consent/` and republished on every `composer update`.

`php artisan migrate` creates the consent register's tables. A site that keeps
no register can skip it.

**Publishing `config/cookie-consent.php` is optional**, and it is not a first
step: the file pins every key it declares, and a pinned setting shows on the
settings screen as read-only. Publish it when the site wants its configuration
in version control, and then trim it to the keys it means to fix.

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
'addons' => [
    'quebecstudio-mods/statamic-consent-kit' => 'pro',
],
```

The gate falls on collecting, never on reading: a register already kept stays
readable whatever the licence says.

## Control panel

### Settings

**Settings ▸ Cookie Consent Kit**, the native addon settings screen.
It writes `resources/addons/statamic-consent-kit.yaml`.

Three sources, each overriding the one before: the package defaults, what the
screen saved, and the keys `config/cookie-consent.php` fixes. A key fixed in
the file is shown on the screen but cannot be changed there — a site that keeps
its configuration in version control stays in charge.

### Consent register

**Tools ▸ Consent** in the navigation, on the Pro edition. One row per answer: when,
which site, which user, what was pressed, where from, and the outcome.

Filter by period, outcome and site; sort on any of them; choose which columns
show. Opening a row shows the exact wording the banner was displaying, replayed
from its fingerprint, so a record proves what was consented to and not merely
that something was.

Export the filtered listing as CSV or JSON.

### Consent Purge

**Tools ▸ Utilities ▸ Consent Purge**, for deleting records older than a chosen
age,
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

Without it, nothing expires on its own — purge from **Tools ▸ Utilities ▸ Consent
Purge**, or run the command by hand.

**Retention answers to the records, not to the switch.** Turning the register
off stops new decisions being written; it does not strand the ones already
kept, which can carry an address and a user agent. They go on expiring, and the
purge utility goes on reaching them.

### Which database

Resolved in order: `cookie-consent.register.connection` when the site names
one, then the site's own default connection, then a SQLite file in
`storage/cookie-consent/register.sqlite` that the addon declares and migrates
itself.

On the site's own connection, `php artisan migrate` creates the two tables.

## Configuration

Everything is set from the control panel. `config/cookie-consent.php` exists for
the keys a site would rather keep in version control: what it fixes there is
shown on the settings screen but cannot be changed from it.

```bash
php please vendor:publish --tag=cookie-consent-config
```

| Key | Default | What it does |
|---|---|---|
| `cookieName` | `cookie_consent` | Cookie holding the visitor's decision |
| `cookieMaxAge` | `15552000` | Its lifetime in seconds — six months |
| `version` | `1` | Raise it to ask every visitor again |
| `policyUrl` | `/privacy-policy` | Link shown in the banner; empty shows none |
| `defaultLanguage` | `en` | Wording used when the site locale has no file |
| `autoInject` | `true` | Appends the banner to every HTML page |
| `colorScheme` | `auto` | `auto`, `light`, `dark` |
| `backdropStyle` | `blur` | Behind the manage panel: `blur`, `dim`, `none` |
| `displayMode` | `full` | `full`, `floating`, `corner-left`, `corner-right` |
| `reopenButton` | `true` | Shows the tab that reopens the banner |
| `reopenPosition` | `auto` | `auto` follows `displayMode`; or `left`, `right` |
| `gpcHidesBanner` | `true` | A Global Privacy Control refusal answers for the visitor |
| `analyticsCategory` | `statistics` | Category granting Matomo and Google Consent Mode analytics |
| `marketingCategory` | `marketing` | Category granting their advertising signals |
| `videoFacade` | `true` | YouTube videos load on click |
| `videoThumbnails` | `true` | The site serves the thumbnail itself |
| `videoConsentCategory` | `''` | Category that lifts the facade; empty asks each time |
| `inventoryFramework` | `''` | Classes for the cookie table: `bootstrap`, `bulma`, `tailwind`, `custom` |
| `inventoryClasses` | `[]` | Class overrides, keyed by element |
| `categories` | `null` | The cookie inventory; null uses the shipped categories |
| `registry` | `false` | Whether decisions are recorded |
| `registryUser` | `true` | Records the signed-in user's id |
| `registryRequestContext` | `false` | Records the IP address and user agent |
| `registryGrace` | `12` | Months kept past the consent cookie's own life |

`cookieName` and `policyUrl` read `COOKIE_CONSENT_NAME` and
`COOKIE_CONSENT_POLICY_URL` from the environment when they are set.

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

### `window.qsmConsentKit`

```js
window.qsmConsentKit.get()                    // {v, ts, cat:{…}} | null
window.qsmConsentKit.granted('statistics')    // bool
window.qsmConsentKit.on('statistics', fn)     // runs now if already granted, otherwise on the next change
window.qsmConsentKit.set({ statistics: true })
window.qsmConsentKit.acceptAll()
window.qsmConsentKit.refuseAll()
window.qsmConsentKit.open()
window.qsmConsentKit.close()
window.qsmConsentKit.ready                    // true once the script has run

document.addEventListener('qsm-consent-kit:change', e => e.detail);
```

`open()` reopens the banner, for a link of the site's own when `reopenButton`
is off.

`get()` returns `null` until a decision is made. Under Global Privacy Control it
returns a refusal carrying `gpc: true`, with no category granted — a state the
browser signals and the addon never writes to a cookie.

The `<head>` bootstrap leaves `window.qsmConsentKitBootstrap = { state }`
behind, which is what lets a video facade lift before the main script loads.

### Conditional tags

A tag marked with a category is activated once that category is granted:

```html
<script type="text/plain" data-consent="marketing" data-consent-src="https://…"></script>
<script type="text/plain" data-consent="statistics">/* inline code */</script>
<iframe data-consent="marketing" data-consent-src="https://…"></iframe>
```

- A script is recreated with a runnable type; an iframe gets its `src`.
- Tags are activated one category at a time, in inventory order, and in
  document order within a category.
- Activated elements carry `data-consent-done`.

A vendor's `<noscript>` fallback is fetched by the browser whatever the visitor
answered. Leave it out.

### Google Consent Mode and Matomo

Both are primed in a refused state by the `<head>` bootstrap, before any tag
runs, and updated when the visitor answers. `analyticsCategory` and
`marketingCategory` decide which category grants what; an empty value grants
nothing.

To drive another vendor, wait on the category:

```js
window.qsmConsentKit.on('statistics', function () {
    // load the vendor here
});
```

### The consent cookie

```json
{ "v": 1, "ts": 1787598237, "cat": { "statistics": false, "marketing": false } }
```

| Property | Value |
|---|---|
| Encoding | URI-encoded JSON |
| Scope | host-only, `path=/` |
| Attributes | `SameSite=Lax`; `Secure` over HTTPS |
| `v` | the `version` setting when the decision was made |
| `ts` | Unix time of the decision |
| `cat` | one boolean per optional category |

A cookie whose `v` differs from `version`, or that cannot be read, is treated
as absent: the banner is shown again.

## Licence

Proprietary.
