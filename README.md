# PdfResponse

[![CI](https://github.com/Ciki/PdfResponse/actions/workflows/ci.yml/badge.svg)](https://github.com/Ciki/PdfResponse/actions/workflows/ci.yml)
[![Latest Stable Version](https://poser.pugx.org/jkuchar/pdfresponse/v/stable)](https://packagist.org/packages/jkuchar/pdfresponse)
[![PHP Version Require](https://poser.pugx.org/jkuchar/pdfresponse/require/php)](https://packagist.org/packages/jkuchar/pdfresponse)
[![License](https://poser.pugx.org/jkuchar/pdfresponse/license)](https://packagist.org/packages/jkuchar/pdfresponse)

Generate a PDF from an HTML string or Nette template, ready to ship as a
`Nette\Application\Response`. Wraps [`mPDF`](https://github.com/mpdf/mpdf).

## Requirements

- PHP 8.3 - 8.5
- `ext-dom`, `ext-libxml`
- `nette/application ^3.1`, `nette/http ^3.2`, `nette/utils ^4.0`
- `mpdf/mpdf ^8.0`

## Installation

```bash
composer require jkuchar/pdfresponse:^1.0
```

## Usage

### From an HTML string

```php
$html = '<h1>Invoice</h1><p>Total: 42 EUR</p>';
$response = new \PdfResponse\PdfResponse($html);
$response->documentTitle = 'invoice-2026-001';
return $response;
```

### From a Nette template

```php
$template = $this->createTemplate();
$template->setFile(__DIR__ . '/templates/invoice.latte');
$template->order = $this->order;

$response = new \PdfResponse\PdfResponse($template);
$response->documentTitle = 'invoice-' . $this->order->getNumber();
return $response;
```

Inside the Latte template you can use `{$pdfResponse}` (the response instance) and
`{$mPDF}` (the underlying mPDF instance) - both are injected automatically when the
template is a `Nette\Bridges\ApplicationLatte\Template` (the bridge class returned by
`$presenter->createTemplate()`). Custom `Template` impls are also accepted, but no
parameters are auto-injected.

### As a string (email attachments, storage, ...)

```php
$pdfData = (new \PdfResponse\PdfResponse($html))->toString();
file_put_contents('/path/to/invoice.pdf', $pdfData);
```

## Configuration

All settings are public properties on the `PdfResponse` instance.

| Property                     | Type                | Default                              | Description                                                                  |
| ---------------------------- | ------------------- | ------------------------------------ | ---------------------------------------------------------------------------- |
| `pageOrientation`            | `string`            | `'P'` (portrait)                     | Use the `ORIENTATION_PORTRAIT` / `ORIENTATION_LANDSCAPE` constants.          |
| `pageFormat`                 | `string`            | `'A4'`                               | Any mPDF page size: `A0`-`A10`, `Letter`, `Legal`, etc.                      |
| `pageMargins`                | `array`             | `['top'=>16,'right'=>15,...]`        | Margins in mm. All six sides required: top, right, bottom, left, header, footer. |
| `documentTitle`              | `string`            | `'Unnamed document'`                 | PDF document title; also used as the download filename (webalized).          |
| `documentAuthor`             | `string`            | `'Nette Framework - Pdf response'`   | PDF metadata.                                                                |
| `displayZoom`                | `string \| int`     | `'default'`                          | `'fullpage'`, `'fullwidth'`, `'real'`, `'default'`, or zoom percentage.      |
| `displayLayout`              | `string`            | `'continuous'`                       | `'single'`, `'continuous'`, `'two'`, or `'default'`.                         |
| `outputDestination`          | `string`            | `OUTPUT_INLINE` (`'I'`)              | Where the PDF goes: inline, download, file, string.                          |
| `outputName`                 | `?string`           | derived from `documentTitle`         | Filename for `OUTPUT_DOWNLOAD` / `OUTPUT_FILE`.                              |
| `multiLanguage`              | `bool`              | `false`                              | Sets `Mpdf::$biDirectional`.                                                 |
| `styles`                     | `string`            | `''`                                 | Extra CSS appended via a second `WriteHTML()` call.                          |
| `ignoreStylesInHTMLDocument` | `bool`              | `false`                              | If `true`, the input HTML is cleaned via DOMDocument before mPDF gets it.    |
| `domOptions`                 | `array`             | `[]`                                 | Cleanup knobs (see below); only consulted when `ignoreStylesInHTMLDocument`. |
| `tempDir`                    | `?string`           | `null` (mPDF default)                | mPDF working directory.                                                      |
| `mpdfFactory`                | `?\Closure`         | bound to `createMPDF()` default      | Replace to construct mPDF yourself.                                          |
| `onBeforeWrite`              | `?\Closure`         | `null`                               | Hook fired right before `Mpdf::WriteHTML()`.                                 |
| `onBeforeComplete`           | `?\Closure`         | `null`                               | Hook fired right before `Mpdf::Output()`.                                    |

### `domOptions` keys

Recognized when `ignoreStylesInHTMLDocument = true`. Unknown keys throw
`InvalidArgumentException`.

| Key                  | Type      | Default            | Effect                                                            |
| -------------------- | --------- | ------------------ | ----------------------------------------------------------------- |
| `removeStyles`       | `bool`    | `true`             | Strip `<style>` elements before handing the HTML to mPDF.         |
| `enforceEncoding`    | `?string` | `null` (UTF-8)     | Encoding hint injected via `<?xml encoding="..." ?>`.             |
| `preserveLineBreaks` | `bool`    | `false`            | Toggles `DOMDocument::$preserveWhiteSpace`.                       |
| `libxml`             | `int`     | sane HTML defaults | Extra libxml flags passed to `loadHTML()`.                        |

## Upgrading

See [CHANGELOG.md](CHANGELOG.md) for the v1.0.0 BC breaks (typed `$source`, `$pageMargins`
shape, dropped `SmartObject` magic, etc.).

## License

LGPL-3.0-or-later. See [LICENSE](LICENSE).
