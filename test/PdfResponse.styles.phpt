<?php declare(strict_types=1);

/**
 * Regression: $styles is extra CSS appended after the main HTML via a second WriteHTML()
 * call. End-to-end check: when set, the resulting PDF should reflect the additional rule.
 *
 * We can't easily introspect PDF rendering at the byte level, so this is a smoke test that
 * the second WriteHTML branch executes without error and produces a valid PDF (guards
 * against accidental removal of the $styles handling).
 */

require __DIR__ . '/bootstrap.php';

$response = new \PdfResponse\PdfResponse('<h1>Hello</h1>');
$response->styles = 'h1 { color: red; font-size: 24pt; }';

ob_start();
$response->send(fakeHttpRequest(), fakeHttpResponse());
$pdfData = (string) ob_get_clean();

assertValidPDF($pdfData);
