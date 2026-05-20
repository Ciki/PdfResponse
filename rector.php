<?php

declare(strict_types=1);

use Rector\Caching\ValueObject\Storage\FileCacheStorage;
use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\StaticCall\RemoveParentCallWithoutParentRector;
use Rector\Php81\Rector\FuncCall\NullToStrictStringFuncCallArgRector;
use Rector\Php82\Rector\Class_\ReadOnlyClassRector;
use Rector\Php83\Rector\ClassMethod\AddOverrideAttributeToOverriddenMethodsRector;
use Rector\Privatization\Rector\MethodCall\PrivatizeLocalGetterToPropertyRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\ValueObject\PhpVersion;

return static function (RectorConfig $rectorConfig): void {
	$rectorConfig->paths([
		__DIR__ . '/src',
		__DIR__ . '/test',
	]);

	$rectorConfig->phpVersion(PhpVersion::PHP_83);
	$rectorConfig->importNames();
	$rectorConfig->indent("\t", 1);

	$rectorConfig->cacheClass(FileCacheStorage::class);
	$rectorConfig->cacheDirectory(__DIR__ . '/temp/.rector_cache');

	$rectorConfig->rule(InlineConstructorDefaultToPropertyRector::class);

	$rectorConfig->skip([
		// keep parent::__construct() for future-proof change
		RemoveParentCallWithoutParentRector::class,

		// PHPStan checks types - no need for runtime casts
		NullToStrictStringFuncCallArgRector::class,

		// requires PHP 8.2 (we target 8.3 min, so available - but readonly classes are intrusive)
		ReadOnlyClassRector::class,

		// noisy on framework-overridden methods
		AddOverrideAttributeToOverriddenMethodsRector::class,

		// we prefer local getters
		PrivatizeLocalGetterToPropertyRector::class,
	]);

	$rectorConfig->sets([
		SetList::PRIVATIZATION,
		SetList::TYPE_DECLARATION,
		LevelSetList::UP_TO_PHP_83,
	]);
};
