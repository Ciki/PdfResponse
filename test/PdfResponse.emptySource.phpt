<?php declare(strict_types=1);

/**
 * Regression: new PdfResponse('') must not throw "Source is not defined". The v0.6.4 check
 * `if (!$this->source)` treated empty string as missing; v1.0.0 narrows $source to
 * string|Template, so the constructor enforces presence and getSource() returns the value
 * as-is. The send() safety net replaces empty HTML with <html><body></body></html>.
 */

require __DIR__ . '/bootstrap.php';

use Tester\Assert;

$response = new \PdfResponse\PdfResponse('');

ob_start();
$response->send(fakeHttpRequest(), fakeHttpResponse());
$pdfData = (string) ob_get_clean();

assertValidPDF($pdfData);
Assert::same('', $response->getRawSource(), 'getRawSource returns the original empty string');
