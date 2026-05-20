<?php declare(strict_types=1);

/**
 * Regression: when ignoreStylesInHTMLDocument=true, the native DOMDocument cleanup must
 * strip <style> elements (matching the historical paquettg net-effect) and must leave
 * <script> elements intact (paquettg never removed them; we don't either by default).
 *
 * History: the initial paquettg-removal commits silently enabled removeScripts=true and a
 * destructive Smarty regex - both behavioral surprises. v1.0.0 reverts to the v0.6.4 net effect.
 */

require __DIR__ . '/bootstrap.php';

use Tester\Assert;

$html = '<!DOCTYPE html><html><head><style>body{color:red}</style></head>'
	. '<body><script>alert(1)</script><p>hello</p></body></html>';

$response = new \PdfResponse\PdfResponse($html);
$response->ignoreStylesInHTMLDocument = true;

// Use Reflection to test the cleanup in isolation (no PDF rendering needed).
$ref = new \ReflectionMethod($response, 'cleanupHtmlForMpdf');
$out = $ref->invoke($response, $html);

Assert::false(str_contains($out, '<style>'), '<style> must be stripped');
Assert::true(str_contains($out, '<script>'), '<script> must be preserved (paquettg parity)');
Assert::true(str_contains($out, '<p>hello</p>'), 'body content must survive');

// removeStyles=false opt-out
$response2 = new \PdfResponse\PdfResponse($html);
$response2->domOptions = ['removeStyles' => false];
$out2 = $ref->invoke($response2, $html);
Assert::true(str_contains($out2, '<style>'), 'removeStyles=false preserves <style>');
