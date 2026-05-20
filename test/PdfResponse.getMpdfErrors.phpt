<?php declare(strict_types=1);

/**
 * Regression: getMPDF() defensive error paths.
 *  - $createMPDF = null  -> InvalidStateException "createMPDF closure is not set"
 *  - $createMPDF returns non-Mpdf -> InvalidStateException "must return an Mpdf instance"
 *
 * Both are programmer-error guards; this test exists so the messages don't silently
 * change/disappear during future refactors.
 */

require __DIR__ . '/bootstrap.php';

use Tester\Assert;

// case 1: factory unset
$r1 = new \PdfResponse\PdfResponse('x');
$r1->createMPDF = null;
Assert::exception(
	fn() => $r1->getMPDF(),
	\Nette\InvalidStateException::class,
	'~createMPDF closure is not set~',
);

// case 2: factory returns something that's not an Mpdf
$r2 = new \PdfResponse\PdfResponse('x');
$r2->createMPDF = fn(): \stdClass => new \stdClass();
Assert::exception(
	fn() => $r2->getMPDF(),
	\Nette\InvalidStateException::class,
	'~createMPDF closure must return an Mpdf instance~',
);
