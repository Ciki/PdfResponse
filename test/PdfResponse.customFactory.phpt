<?php declare(strict_types=1);

/**
 * Regression: $mpdfFactory is a replaceable factory closure - assigning a custom one must
 * cause getMPDF() to use it, and the closure must receive enough context to build a working
 * Mpdf. Also covers $tempDir being threaded through the default factory.
 */

require __DIR__ . '/bootstrap.php';

use Tester\Assert;

// custom factory
$response = new \PdfResponse\PdfResponse('<p>x</p>');
$customMpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'Letter']);
$response->mpdfFactory = fn(): \Mpdf\Mpdf => $customMpdf;

Assert::same($customMpdf, $response->getMPDF(), 'custom factory result is returned by getMPDF()');

// default factory threads $tempDir into the Mpdf config
$tempDir = sys_get_temp_dir() . '/pdfresponse-test-' . uniqid();
mkdir($tempDir);
try {
	$response2 = new \PdfResponse\PdfResponse('<p>x</p>');
	$response2->tempDir = $tempDir;
	$mpdf = $response2->getMPDF();
	Assert::same($tempDir, $mpdf->tempDir, 'tempDir property is forwarded to Mpdf');
} finally {
	// recursive cleanup - Mpdf creates mpdf/ subdir with cached fonts during init
	$rmrf = function (string $dir) use (&$rmrf): void {
		foreach (glob($dir . '/*') ?: [] as $f) {
			is_dir($f) ? $rmrf($f) : @unlink($f);
		}
		@rmdir($dir);
	};
	$rmrf($tempDir);
}
