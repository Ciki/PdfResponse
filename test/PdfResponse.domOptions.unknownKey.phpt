<?php declare(strict_types=1);

/**
 * Regression: unknown $domOptions keys must throw InvalidArgumentException with the offending
 * key name and the list of supported keys. Was silently accepted in v0.6.4 (paquettg path)
 * and is the documented v1.0.0 BC break.
 */

require __DIR__ . '/bootstrap.php';

use Tester\Assert;

$response = new \PdfResponse\PdfResponse('<p>x</p>');
$response->ignoreStylesInHTMLDocument = true;
$response->domOptions = ['removeStyles' => true, 'whitespaceTextNode' => true];

Assert::exception(
	fn() => $response->send(fakeHttpRequest(), fakeHttpResponse()),
	\InvalidArgumentException::class,
	'~Unknown PdfResponse domOptions key.+whitespaceTextNode.+Supported keys.+removeStyles~',
);
