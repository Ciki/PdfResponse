<?php declare(strict_types=1);

/**
 * Regression: $source typed as Nette\Application\UI\Template (the interface) must accept
 * any concrete Template impl, not just Nette\Bridges\ApplicationLatte\Template. For
 * non-bridge impls there's no automatic parameter injection (the interface doesn't promise
 * any param-passing API) - we just call render() and capture the output.
 */

require __DIR__ . '/bootstrap.php';

use Tester\Assert;

$customTemplate = new class implements \Nette\Application\UI\Template {
	public function render(): void
	{
		echo '<!DOCTYPE html><html><body><h1>From a custom Template impl</h1></body></html>';
	}

	public function setFile(string $file): static
	{
		return $this;
	}

	public function getFile(): ?string
	{
		return null;
	}
};

$response = new \PdfResponse\PdfResponse($customTemplate);

Assert::contains('From a custom Template impl', $response->getSource());

// E2E PDF generation through the custom Template path
ob_start();
$response->send(fakeHttpRequest(), fakeHttpResponse());
$pdf = (string) ob_get_clean();
assertValidPDF($pdf);
