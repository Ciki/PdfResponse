<?php declare(strict_types=1);

/**
 * Regression: $pageMargins is a typed assoc array in v1.0.0 (was CSV string in v0.6.4).
 * getMargins() validates side completeness and rejects negative values.
 */

require __DIR__ . '/bootstrap.php';

use Tester\Assert;

// default margins
$response = new \PdfResponse\PdfResponse('x');
$margins = $response->getMargins();
Assert::same(['top', 'right', 'bottom', 'left', 'header', 'footer'], array_keys($margins));
Assert::same(16, $margins['top']);
Assert::same(9, $margins['footer']);

// missing side throws
$response2 = new \PdfResponse\PdfResponse('x');
$response2->pageMargins = ['top' => 10, 'right' => 10, 'bottom' => 10, 'left' => 10];
Assert::exception(
	fn() => $response2->getMargins(),
	\Nette\InvalidStateException::class,
	'~Missing pageMargins side.+header.+footer~',
);

// negative value throws
$response3 = new \PdfResponse\PdfResponse('x');
$response3->pageMargins = ['top' => -1, 'right' => 10, 'bottom' => 10, 'left' => 10, 'header' => 5, 'footer' => 5];
Assert::exception(
	fn() => $response3->getMargins(),
	\Nette\InvalidArgumentException::class,
	"~Margin 'top' must not be negative~",
);
