<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\Basic\BracesFixer;
use PhpCsFixer\Fixer\Basic\SingleLineEmptyBodyFixer;
use PhpCsFixer\Fixer\ClassNotation\ClassAttributesSeparationFixer;
use PhpCsFixer\Fixer\ClassNotation\OrderedClassElementsFixer;
use PhpCsFixer\Fixer\ControlStructure\TrailingCommaInMultilineFixer;
use PhpCsFixer\Fixer\FunctionNotation\FunctionDeclarationFixer;
use PhpCsFixer\Fixer\Operator\NotOperatorWithSuccessorSpaceFixer;
use PhpCsFixer\Fixer\Phpdoc\GeneralPhpdocAnnotationRemoveFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocLineSpanFixer;
use PhpCsFixer\Fixer\Whitespace\NoExtraBlankLinesFixer;
use Symplify\CodingStandard\Fixer\LineLength\LineLengthFixer;
use Symplify\CodingStandard\Fixer\Spacing\MethodChainingNewlineFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;
use Symplify\EasyCodingStandard\ValueObject\Set\SetList;

return static function (ECSConfig $ecsConfig): void {
	$ecsConfig->parallel();

	$ecsConfig->paths([
		__DIR__ . '/src',
		__DIR__ . '/test',
	]);

	$ecsConfig->cacheDirectory(__DIR__ . '/temp/.ecs_cache');
	$ecsConfig->indentation('tab');

	$ecsConfig->skip([
		// keep single-line doc-comments intact -> do not convert to block phpDocs
		PhpdocLineSpanFixer::class,

		// do not use till https://github.com/PHP-CS-Fixer/PHP-CS-Fixer/issues/6981 is solved
		LineLengthFixer::class,

		// breaks layout more often than helps
		MethodChainingNewlineFixer::class,

		// disable from preset
		OrderedClassElementsFixer::class,
		NotOperatorWithSuccessorSpaceFixer::class,
	]);

	$ecsConfig->sets([
		SetList::ARRAY,
		SetList::CLEAN_CODE,
		SetList::COMMENTS,
		SetList::CONTROL_STRUCTURES,
		SetList::DOCBLOCK,
		SetList::NAMESPACES,
		SetList::PSR_12,
		SetList::SPACES,
		SetList::SYMPLIFY,
	]);

	$ecsConfig->ruleWithConfiguration(FunctionDeclarationFixer::class, [
		'closure_fn_spacing' => 'none', // fn ($x) -> fn($x)
	]);
	$ecsConfig->rule(SingleLineEmptyBodyFixer::class);
	$ecsConfig->ruleWithConfiguration(TrailingCommaInMultilineFixer::class, [
		'elements' => ['arguments', 'arrays', 'match', 'parameters'],
	]);

	// keep blank lines between code blocks readable
	$ecsConfig->ruleWithConfiguration(NoExtraBlankLinesFixer::class, [
		'tokens' => ['continue', 'default', 'return', 'switch', 'use', 'use_trait'],
	]);
	$ecsConfig->ruleWithConfiguration(ClassAttributesSeparationFixer::class, [
		'elements' => [
			'const' => 'only_if_meta',
			'trait_import' => 'only_if_meta',
			'property' => 'one',
			'method' => 'one',
		],
	]);
	$ecsConfig->ruleWithConfiguration(GeneralPhpdocAnnotationRemoveFixer::class, [
		'annotations' => ['author', 'package', 'group', 'covers', 'category'],
	]);
};
