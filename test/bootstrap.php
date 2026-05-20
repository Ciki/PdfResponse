<?php

declare(strict_types=1);

use Nette\Http\Request;
use Nette\Http\Response;
use Nette\Http\UrlScript;
use Tester\Assert;
use Tester\Environment;

require __DIR__ . '/../vendor/autoload.php';

Environment::setup();

function fakeHttpRequest(): Request
{
	return new Request(
		new UrlScript(),
	);
}

function fakeHttpResponse(): Response
{
	return new Response();
}


function assertValidPDF(string $data): void
{
	Assert::true(
		str_starts_with($data, '%PDF'),
		'Have not found valid PDF file in generated output.',
	);
}

function savePDF(string $data, string $name): void
{
	file_put_contents($name . '.output.pdf', $data);
}
