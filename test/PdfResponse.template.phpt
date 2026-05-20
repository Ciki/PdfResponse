<?php declare(strict_types=1);

/**
 * Regression: when $source is a Nette template, getSource() must call renderToString()
 * with $pdfResponse and $mPDF injected as parameters. Guards against breakage of the
 * Template injection contract (templates in the wild reference these as {$pdfResponse}
 * and {$mPDF}).
 *
 * History: the PHPStan-level-8 fix migrated this from dynamic property assignment to
 * renderToString(); this test ensures both injected variables are reachable from the
 * template body.
 */

require __DIR__ . '/bootstrap.php';

use Tester\Assert;

$latte = new \Latte\Engine();
$latte->setLoader(new \Latte\Loaders\StringLoader([
	'invoice.latte' => 'PdfResponseClass={get_class($pdfResponse)}; mPDFClass={get_class($mPDF)}; title={$pdfResponse->documentTitle}',
]));

// #[AllowDynamicProperties] mirrors what Nette's DefaultTemplate does - renderToString()
// uses Arrays::toObject() to set our params as dynamic properties on $this.
$template = new #[\AllowDynamicProperties] class($latte) extends \Nette\Bridges\ApplicationLatte\Template {};
$template->setFile('invoice.latte');

$response = new \PdfResponse\PdfResponse($template);
$response->documentTitle = 'My Invoice';

$html = $response->getSource();

Assert::contains('PdfResponseClass=PdfResponse\PdfResponse', $html);
Assert::contains('mPDFClass=Mpdf\Mpdf', $html);
Assert::contains('title=My Invoice', $html);

// also verify end-to-end PDF generation works through the template path
ob_start();
$response->send(fakeHttpRequest(), fakeHttpResponse());
$pdf = (string) ob_get_clean();
assertValidPDF($pdf);
