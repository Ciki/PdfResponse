<?php declare(strict_types=1);

/**
 * Regression: getMPDF() defensive error paths.
 *  - $mpdfFactory = null  -> InvalidStateException "mpdfFactory closure is not set"
 *  - $mpdfFactory returns non-Mpdf -> InvalidStateException "must return an Mpdf instance"
 *
 * Both are programmer-error guards; this test exists so the messages don't silently
 * change/disappear during future refactors.
 */

require __DIR__ . '/bootstrap.php';

use Tester\Assert;

// case 1: factory unset
$r1 = new \PdfResponse\PdfResponse('x');
$r1->mpdfFactory = null;
Assert::exception(
	fn() => $r1->getMPDF(),
	\Nette\InvalidStateException::class,
	'~mpdfFactory closure is not set~',
);

// case 2: factory returns something that's not an Mpdf
$r2 = new \PdfResponse\PdfResponse('x');
$r2->mpdfFactory = fn(): \stdClass => new \stdClass();
Assert::exception(
	fn() => $r2->getMPDF(),
	\Nette\InvalidStateException::class,
	'~mpdfFactory closure must return an Mpdf instance~',
);
