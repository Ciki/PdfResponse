# Changelog

All notable changes to this project will be documented in this file. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and this project adheres to
[Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-05-20

This is the first stable release. It modernizes the codebase for PHP 8.3+, drops the
`paquettg/php-html-parser` dependency, and tightens the public API.

### Removed (BC)

- Dependency `paquettg/php-html-parser` (replaced with native `DOMDocument`).
  Removing the dep also unpins `guzzlehttp/psr7` to `^2.7+` for downstream consumers.
- `$domOptions` keys `whitespaceTextNode`, `strict`, `cleanupInput`, `removeDoubleSpace`,
  `preserveLineBreaksAfterClosingTag` (paquettg-only; now throw `InvalidArgumentException`).
- `Nette\SmartObject` trait + the `@property-read Mpdf $mPDF` magic accessor.
  Use `$response->getMPDF()` instead.
- `PdfResponse\Utils::tryCall()` and the entire `PdfResponse\Utils` class (internal helper,
  no longer needed - `?->__invoke()` does the job natively).

### Changed (BC)

- **Minimum PHP version is now 8.3** (was 7.1 in v0.6.4).
- `$source` constructor argument is typed `string | Nette\Bridges\ApplicationLatte\Template`.
  Anything else fails at construction time with a `TypeError`.
  Template parameters `$pdfResponse` and `$mPDF` are now passed via `renderToString()`
  instead of dynamic property assignment - templates that referenced `{$pdfResponse}` and
  `{$mPDF}` continue to work unchanged.
- `$pageMargins` is now an assoc array keyed by side, not a comma-separated string:
  ```php
  // before (v0.6.4)
  $response->pageMargins = '16,15,16,15,9,9';
  // now (v1.0.0)
  $response->pageMargins = ['top' => 16, 'right' => 15, 'bottom' => 16,
      'left' => 15, 'header' => 9, 'footer' => 9];
  ```
- Callback properties (`$createMPDF`, `$onBeforeWrite`, `$onBeforeComplete`) are typed
  `?\Closure`. To assign a method callable, wrap it:
  `\Closure::fromCallable([$obj, 'method'])`.
- `getRawSource()` return type is now `string | Template` (was `mixed`).
- `getMargins()` is now `public` (was implicitly public via no-modifier, but is part of
  the documented contract).

### Added

- Native `DOMDocument`-based HTML cleanup path with documented `$domOptions` keys:
  - `removeStyles` (bool, default `true`) - strip `<style>` elements
  - `enforceEncoding` (?string, default `null` ⇒ UTF-8)
  - `preserveLineBreaks` (bool, default `false`)
  - `libxml` (int, sane defaults)
- PHPStan analysis at level 8.
- GitHub Actions CI matrix for PHP 8.3 / 8.4 / 8.5.
- Regression tests for `<style>`/`<script>` cleanup behavior, unknown `$domOptions` key
  rejection, empty source acceptance, and `$pageMargins` validation.

### Fixed

- `new PdfResponse('')` no longer throws "Source is not defined" - an empty source is
  valid and renders an empty PDF.

## [0.6.4] - 2023-03-24

Last release before the v1.0.0 modernization. See git log `v0.6.4` for details.
