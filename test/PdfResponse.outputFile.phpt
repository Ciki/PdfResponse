<?php declare(strict_types=1);

/**
 * Regression: outputDestination=OUTPUT_FILE writes the PDF to the path in $outputName
 * instead of streaming it inline. Guards the non-default destinations path in send().
 *
 * Note: OUTPUT_STRING is also defined but currently unreachable through send() because
 * mPDF's Output() return value is discarded - tested only OUTPUT_FILE here.
 */

require __DIR__ . '/bootstrap.php';

use Tester\Assert;

$path = sys_get_temp_dir() . '/pdfresponse-test-' . uniqid() . '.pdf';
$response = new \PdfResponse\PdfResponse('<h1>file output</h1>');
$response->outputDestination = \PdfResponse\PdfResponse::OUTPUT_FILE;
$response->outputName = $path;

try {
	$response->send(fakeHttpRequest(), fakeHttpResponse());
	Assert::true(file_exists($path), 'PDF file created at outputName path');
	assertValidPDF((string) file_get_contents($path));
} finally {
	@unlink($path);
}
