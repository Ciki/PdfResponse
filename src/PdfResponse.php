<?php

declare(strict_types=1);

/**
 * PdfResponse
 * -----------
 * Wrapper of mPDF.
 * Generate PDF from Nette Framework in one line.
 *
 * @author     Jan Kuchař
 * @copyright  Copyright (c) 2010 Jan Kuchař (http://mujserver.net)
 * @license    LGPL
 * @link       http://addons.nettephp.com/cs/pdfresponse
 */

namespace PdfResponse;

use DOMDocument;
use Mpdf\Mpdf;
use Nette\Application\UI\Template;
use Nette\Bridges\ApplicationLatte\Template as LatteTemplate;
use Nette\Http\IRequest;
use Nette\Http\IResponse;
use Nette\Utils\Strings;

class PdfResponse implements \Nette\Application\Response
{
	/**
	 * Source HTML string or Nette template
	 */
	private string|Template $source;

	/**
	 * HTML pre-processing options used when `ignoreStylesInHTMLDocument = true`.
	 *
	 * BC NOTE (BREAKING CHANGE from the paquettg-based version): the paquettg-only tokenizer knobs
	 * `whitespaceTextNode`, `strict`, `cleanupInput`, `removeDoubleSpace`,
	 * `preserveLineBreaksAfterClosingTag` are no longer accepted - native DOMDocument has no
	 * equivalent. Passing any of them now throws InvalidArgumentException (was silently passed
	 * through to paquettg's tokenizer). Rationale: fail at composer-update time on the dev box
	 * rather than silently misbehave for the client. Strip them from $domOptions before calling
	 * send() to migrate.
	 *
	 *  - removeStyles (bool, default TRUE)    strip <style> elements (always-stripped net effect
	 *                                          of the prior code path is preserved)
	 *  - enforceEncoding (?string, null)      encoding hint prepended as `<?xml encoding="..." ?>`
	 *                                          before loadHTML(); null = UTF-8
	 *  - preserveLineBreaks (bool, false)     keep \n in serialized output (preserveWhiteSpace)
	 *  - libxml (int, sane defaults)          extra libxml flags OR'd into loadHTML() options
	 *
	 * @var array{removeStyles?:bool,enforceEncoding?:?string,preserveLineBreaks?:bool,libxml?:int}
	 */
	public array $domOptions = [];

	/** @var array{removeStyles:bool,enforceEncoding:?string,preserveLineBreaks:bool,libxml:int} defaults applied when $domOptions is missing a key */
	private const array DOM_OPTION_DEFAULTS = [
		'removeStyles' => true,
		'enforceEncoding' => null,
		'preserveLineBreaks' => false,
		'libxml' => LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING,
	];


	/**
	 * Factory for the underlying Mpdf instance. Replaced if you need custom Mpdf config.
	 * Defaults to {@see self::createMPDF()} (wrapped via Closure::fromCallable in the constructor).
	 */
	public ?\Closure $createMPDF = null;


	/**
	 * Portrait page orientation
	 */
	const string ORIENTATION_PORTRAIT  = 'P';

	/**
	 * Landscape page orientation
	 */
	const string ORIENTATION_LANDSCAPE = 'L';

	/**
	 * Specifies page orientation.
	 *
	 * You can use constants:
	 * <ul>
	 *   <li>PdfResponse::ORIENTATION_PORTRAIT (default)
	 *   <li>PdfResponse::ORIENTATION_LANDSCAPE
	 * </ul>
	 *
	 * <b>In some usages this may not work.</b>
	 * If this setting does not work for you,
	 * you can specify page orientation by setting
	 * page size and orientation in style sheet of
	 * source document.
	 * <pre>
	 * @page { sheet-size: A4-L; }
	 * </pre>
	 */
	public string $pageOrientation = self::ORIENTATION_PORTRAIT;


	/**
	 * Specifies format of the document<br>
	 * <br>
	 * Allowed values: (Values are case-<b>in</b>sensitive)
	 * <ul>
	 *   <li>A0 - A10
	 *   <li>B0 - B10
	 *   <li>C0 - C10
	 *   <li>4A0
	 *   <li>2A0
	 *   <li>RA0 - RA4
	 *   <li>SRA0 - SRA4
	 *   <li>Letter
	 *   <li>Legal
	 *   <li>Executive
	 *   <li>Folio
	 *   <li>Demy
	 *   <li>Royal
	 *   <li>A<i> (Type A paperback 111x178mm)</i>
	 *   <li>B<i> (Type B paperback 128x198mm)</i>
	 * </ul>
	 */
	public string $pageFormat = 'A4';

	/**
	 * Margins in millimeters, keyed by side: top, right, bottom, left, header, footer.
	 * Values must be >= 0; zero values may cause issues in some PDF viewers.
	 *
	 * @var array{top:int,right:int,bottom:int,left:int,header:int,footer:int}
	 */
	public array $pageMargins = [
		'top' => 16,
		'right' => 15,
		'bottom' => 16,
		'left' => 15,
		'header' => 9,
		'footer' => 9,
	];

	/**
	 * Author of the document
	 */
	public string $documentAuthor = 'Nette Framework - Pdf response';

	/**
	 * Title of the document
	 */
	public string $documentTitle = 'Unnamed document';

	/**
	 * This parameter specifies the magnification (zoom) of the display when the document is opened.<br>
	 * Values (case-<b>sensitive</b>)
	 * <ul>
	 *   <li><b>fullpage</b>: Fit a whole page in the screen
	 *   <li><b>fullwidth</b>: Fit the width of the page in the screen
	 *   <li><b>real</b>: Display at real size
	 *   <li><b>default</b>: User's default setting in Adobe Reader
	 *   <li><i>integer</i>: Display at a percentage zoom (e.g. 90 will display at 90% zoom)
	 * </ul>
	 */
	public string|int $displayZoom = 'default';

	/**
	 * Specify the page layout to be used when the document is opened.<br>
	 * Values (case-<b>sensitive</b>)
	 * <ul>
	 *   <li><b>single</b>: Display one page at a time
	 *   <li><b>continuous</b>: Display the pages in one column
	 *   <li><b>two</b>: Display the pages in two columns
	 *   <li><b>default</b>: User's default setting in Adobe Reader
	 * </ul>
	 */
	public string $displayLayout = 'continuous';

	/**
	 * This parameter specifie the directory to be used as a temp dir when generating PDF content.<br/>
	 * If it is empty, mPDF default value is used.
	 */
	public ?string $tempDir = null;

	/**
	 * Hook fired right before mPDF Output() is called.
	 */
	public ?\Closure $onBeforeComplete = null;

	/**
	 * Hook fired right before mPDF WriteHTML() is called.
	 */
	public ?\Closure $onBeforeWrite = null;

	/**
	 * Multi-language document?
	 */
	public bool $multiLanguage = false;

	/**
	 * Additional stylesheet as a <b>string</b>
	 */
	public string $styles = '';

	/**
	 * <b>Ignore</b> styles in HTML document
	 * When using this feature, you MUST also install SimpleHTMLDom to your application!
	 */
	public bool $ignoreStylesInHTMLDocument = false;

	/**
	 * mPDF instance
	 */
	private ?Mpdf $mPDF = null;

	/**
	 * Document name on output
	 */
	public ?string $outputName = null;

	/**
	 * send the file inline to the browser. The plug-in is used if available. The name given by filename is used when one selects the "Save as" option on the link generating the PDF.
	 */
	const string OUTPUT_INLINE = 'I';

	/**
	 * send to the browser and force a file download with the name given by filename.
	 */
	const string OUTPUT_DOWNLOAD = 'D';

	/**
	 * save to a local file with the name given by filename (may include a path).
	 */
	const string OUTPUT_FILE = 'F';

	/**
	 * return the document as a string. filename is ignored.
	 */
	const string OUTPUT_STRING = 'S';

	/**
	 * Output destination
	 */
	public string $outputDestination = self::OUTPUT_INLINE;

	/**
	 * Returns validated margins keyed by side. Throws if any side is missing or negative.
	 *
	 * @return array{top:int,right:int,bottom:int,left:int,header:int,footer:int}
	 */
	public function getMargins(): array
	{
		$expected = ['top', 'right', 'bottom', 'left', 'header', 'footer'];
		$missing = array_diff($expected, array_keys($this->pageMargins));
		if ($missing !== []) {
			throw new \Nette\InvalidStateException('Missing pageMargins side(s): ' . implode(', ', $missing));
		}

		foreach ($this->pageMargins as $side => $value) {
			if ($value < 0) {
				throw new \Nette\InvalidArgumentException("Margin '{$side}' must not be negative.");
			}
		}

		return $this->pageMargins;
	}

	public function __construct(string|Template $source)
	{
		$this->createMPDF = \Closure::fromCallable([$this, 'createMPDF']);
		$this->source = $source;
	}


	public function openPrintDialog(): void
	{
		$this->getMPDF()->SetJS('print()');
	}

	/**
	 * Returns rendered HTML for the configured source.
	 *
	 * For {@see LatteTemplate} (the Nette+Latte bridge class) we use the documented
	 * `renderToString(null, [...])` API to inject `$pdfResponse` and `$mPDF` as template
	 * parameters. For other {@see Template} implementations we just call `render()` and
	 * capture the output - no automatic parameter injection (the interface doesn't promise
	 * any parameter-passing API).
	 */
	public function getSource(): string
	{
		if (is_string($this->source)) {
			return $this->source;
		}

		if ($this->source instanceof LatteTemplate) {
			return $this->source->renderToString(null, [
				'pdfResponse' => $this,
				'mPDF' => $this->getMPDF(),
			]);
		}

		ob_start();
		$this->source->render();
		return (string) ob_get_clean();
	}

	public function getRawSource(): string|Template
	{
		return $this->source;
	}



	/**
	 * Sends the response to the current output (per `$outputDestination`).
	 * Called automatically by Nette when this is returned from a presenter.
	 */
	public function send(IRequest $httpRequest, IResponse $httpResponse): void
	{
		$mpdf = $this->buildMpdfDocument();
		if ($this->outputName === null || $this->outputName === '') {
			$this->outputName = Strings::webalize($this->documentTitle) . '.pdf';
		}
		$mpdf->Output($this->outputName, $this->outputDestination);
	}

	/**
	 * Returns the rendered PDF as a binary string, bypassing `$outputDestination`.
	 * Useful when you want to email the PDF, persist it to storage, etc.
	 */
	public function toString(): string
	{
		$result = $this->buildMpdfDocument()->Output('', self::OUTPUT_STRING);
		if (!is_string($result)) {
			throw new \Nette\InvalidStateException('Mpdf::Output() did not return a string in OUTPUT_STRING mode.');
		}
		return $result;
	}

	/**
	 * Resolves the source, configures mPDF metadata + display, runs the optional
	 * pre-write/pre-complete hooks, writes HTML and optional extra styles.
	 * Returns the fully-prepared Mpdf instance ready for Output().
	 */
	private function buildMpdfDocument(): Mpdf
	{
		// Throws exception if sources can not be processed
		$html = $this->getSource();

		// Fix: $html can't be empty (mPDF generates Fatal error)
		if ($html === '') {
			$html = '<html><body></body></html>';
		}

		$mpdf = $this->getMPDF();
		$mpdf->biDirectional = $this->multiLanguage;
		$mpdf->SetAuthor($this->documentAuthor);
		$mpdf->SetTitle($this->documentTitle);
		$mpdf->SetDisplayMode($this->displayZoom, $this->displayLayout);

		// @see: http://mpdf1.com/manual/index.php?tid=121&searchstring=writeHTML
		if ($this->ignoreStylesInHTMLDocument) {

			// copied from mPDF -> removes comments
			$html = Strings::replace($html, '/<!--mpdf/i', '');
			$html = Strings::replace($html, '/mpdf-->/i', '');
			$html = Strings::replace($html, '/<\!\-\-.*?\-\->/s', '');

			// deletes all <style> tags (and other configured cleanups) via native DOMDocument
			$html = $this->cleanupHtmlForMpdf($html);

			$mode = 2; // If <body> tags are found, all html outside these tags are discarded, and the rest is parsed as content for the document. If no <body> tags are found, all html is parsed as content. Prior to mPDF 4.2 the default CSS was not parsed when using mode #2
		} else {
			$mode = 0; // Parse all: HTML + CSS
		}

		($this->onBeforeWrite)?->__invoke();

		// Add content
		$mpdf->WriteHTML($html, $mode);

		// Add styles
		if ($this->styles !== '') {
			$mpdf->WriteHTML($this->styles, 1);
		}

		($this->onBeforeComplete)?->__invoke();

		return $mpdf;
	}


	public function getMPDF(): Mpdf
	{
		if (!$this->mPDF instanceof Mpdf) {
			if ($this->createMPDF === null) {
				throw new \Nette\InvalidStateException('createMPDF closure is not set!');
			}
			$mpdf = ($this->createMPDF)();
			if (!$mpdf instanceof Mpdf) {
				throw new \Nette\InvalidStateException('createMPDF closure must return an Mpdf instance!');
			}
			$this->mPDF = $mpdf;
		}
		return $this->mPDF;
	}


	/**
	 * Creates and returns mPDF object
	 */
	public function createMPDF(): Mpdf
	{
		$margins = $this->getMargins();
		$config = [
			'mode' => 'utf-8',
			'format' => $this->pageFormat,
			'default_font_size' => '',
			'default_font' => '',
			'margin_left' => $margins['left'],
			'margin_right' => $margins['right'],
			'margin_top' => $margins['top'],
			'margin_bottom' => $margins['bottom'],
			'margin_header' => $margins['header'],
			'margin_footer' => $margins['footer'],
			'orientation' => $this->pageOrientation,
		];
		if ($this->tempDir !== null) {
			$config['tempDir'] = $this->tempDir;
		}

		return new Mpdf($config);
	}


	/**
	 * Apply $domOptions and strip <style> elements from an HTML fragment via native PHP DOMDocument.
	 *
	 * Migrated from paquettg/php-html-parser - the previous code path parsed via
	 * `(new Dom())->setOptions($domOptions)->loadStr($html)` and then ran a `find('style')` loop
	 * to remove <style> elements. This method preserves the same net effect plus a documented
	 * subset of options - see $domOptions docblock for the recognized keys.
	 *
	 * @throws \InvalidArgumentException when $domOptions contains an unrecognized key
	 */
	private function cleanupHtmlForMpdf(string $html): string
	{
		$opts = $this->resolveDomOptions();

		$encoding = $opts['enforceEncoding'] ?? 'UTF-8';
		$dom = new DOMDocument();
		$dom->preserveWhiteSpace = $opts['preserveLineBreaks'];
		// XML declaration with explicit encoding is the documented workaround for libxml's
		// Latin-1 default since PHP 8.2 deprecated `mb_convert_encoding(..., 'HTML-ENTITIES', ...)`.
		$dom->loadHTML('<?xml encoding="' . htmlspecialchars($encoding, ENT_QUOTES) . '" ?>' . $html, $opts['libxml']);

		if ($opts['removeStyles']) {
			// iterator_to_array snapshot because removeChild mutates the live NodeList during iteration
			foreach (iterator_to_array($dom->getElementsByTagName('style')) as $el) {
				$el->parentNode?->removeChild($el);
			}
		}

		// strip the XML processing instruction we injected for UTF-8 hinting (saveHTML emits it back -
		// sometimes with the closing token, sometimes without depending on libxml version, hence the
		// lenient regex covers both forms)
		$out = (string) $dom->saveHTML();
		return preg_replace('~<\?xml\b[^>]*\?>\s*|<\?xml\b[^>]*>\s*~', '', $out, 1) ?? $out;
	}


	/**
	 * Merge user-supplied $domOptions onto DOM_OPTION_DEFAULTS and reject unrecognized keys.
	 *
	 * @return array{removeStyles:bool,enforceEncoding:?string,preserveLineBreaks:bool,libxml:int}
	 * @throws \InvalidArgumentException when an unrecognized option key is present
	 */
	private function resolveDomOptions(): array
	{
		$unknown = array_diff(array_keys($this->domOptions), array_keys(self::DOM_OPTION_DEFAULTS));
		if ($unknown !== []) {
			throw new \InvalidArgumentException(
				'Unknown PdfResponse domOptions key(s): ' . implode(', ', $unknown)
				. '. Supported keys: ' . implode(', ', array_keys(self::DOM_OPTION_DEFAULTS)),
			);
		}
		return array_replace(self::DOM_OPTION_DEFAULTS, $this->domOptions);
	}
}
