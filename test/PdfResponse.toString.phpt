<?php declare(strict_types=1);

/**
 * Regression: toString() returns the rendered PDF as a binary string, bypassing
 * $outputDestination. Lets callers email/persist the PDF without round-tripping
 * through ob_start()/ob_get_clean().
 */

require __DIR__ . '/bootstrap.php';

$response = new \PdfResponse\PdfResponse('<h1>toString() test</h1>');

$pdfData = $response->toString();

assertValidPDF($pdfData);
