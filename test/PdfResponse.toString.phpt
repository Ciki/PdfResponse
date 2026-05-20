<?php declare(strict_types=1);

/**
 * Regression: toString() returns the rendered PDF as a binary string, bypassing
 * $outputDestination. Lets callers email/persist the PDF without round-tripping
 * through ob_start()/ob_get_clean().
 *
 * Also covers the defensive guard for when Mpdf::Output() in 'S' mode does NOT return
 * a string (shouldn't happen in practice with real Mpdf, but guards against future API
 * regressions).
 */

require __DIR__ . '/bootstrap.php';

use Tester\Assert;

// happy path
$response = new \PdfResponse\PdfResponse('<h1>toString() test</h1>');
assertValidPDF($response->toString());

// defensive guard: a broken Mpdf that doesn't return string in 'S' mode must throw
$response2 = new \PdfResponse\PdfResponse('<p>x</p>');
$response2->createMPDF = fn(): \Mpdf\Mpdf => new class(['mode' => 'utf-8']) extends \Mpdf\Mpdf {
	public function Output($name = '', $dest = ''): int
	{
		return 42; // intentionally wrong type
	}
};
Assert::exception(
	fn() => $response2->toString(),
	\Nette\InvalidStateException::class,
	'~did not return a string~',
);
