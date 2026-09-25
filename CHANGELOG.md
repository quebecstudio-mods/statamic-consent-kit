# Changelog

## 6.0.8 - 2026-09-25

### Fixed

- **The settings screen says when the register is dormant.** A site whose
  configuration asks for a register without the Pro edition was told what Pro
  adds, but not that nothing is being written. The register’s own screen said
  it; the screen holding the switch did not.

### Changed

- The settings screen names the free edition by what it does rather than by
  its handle.

## 6.0.7 - 2026-09-24

### Fixed

- The links to the documentation and the licence are absolute. The Marketplace
  shows this file as the product description, outside the repository, where a
  relative link resolves against the wrong host and leads nowhere.

## 6.0.6 - 2026-09-24

### Added

- A check that every string the addon asks to translate resolves, run with the
  rest. Nothing here renders a screen, so nothing else would notice a key the
  wording no longer carries.
- The settings screen warns, when the cookie table is styled with Tailwind,
  that Tailwind scans no package and the classes have to be pointed at.

### Changed

- The README opens on what the addon does and what the Pro edition adds,
  rather than on how it is put together. The Marketplace shows it as the
  product description.

## 6.0.5 - 2026-09-24

### Fixed

- **Retention answers to the records, not to the switch.** Turning the register
  off stopped expiry, so decisions already kept — which can carry an address
  and a user agent — stayed indefinitely. They go on expiring now. Turning the
  register off still stops new decisions being written.
- The documentation said to publish `config/cookie-consent.php` while
  installing. That file pins every key it declares, so following it left almost
  every setting read-only. It is optional, and says so.
- The editions example left out the `addons` key, so Pro never turned on when
  it was copied as written.
- The settings screen, the register and the purge utility are named and placed
  as the panel shows them.

## 6.0.4 - 2026-09-24

### Changed

- The settings screen renders the catalogue the core describes instead of
  declaring the same fields again. The screen is unchanged; what it is built
  from is not.

## 6.0.3 - 2026-09-24

### Changed

- The documentation stands on its own: the configuration keys and the front-end
  API are described here rather than pointed at from elsewhere.

## 6.0.2 - 2026-09-24

### Added

- The package declares where to get help: the homepage, an address, the issue
  tracker, the source and the documentation.

## 6.0.1 - 2026-09-24

### Changed

- The English control panel follows one casing rule, the one Statamic titles
  its own interface with: title case for labels, titles, headings and column
  names, sentence case for permissions, buttons, links and options. The
  permissions, the export and filter buttons and the listing options were
  titled as if they were labels.

## 6.0.0

Initial release.
