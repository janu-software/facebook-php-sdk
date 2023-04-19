<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Identical\FlipTypeControlToUseExclusiveTypeRector;
use Rector\Config\RectorConfig;
use Rector\Core\ValueObject\PhpVersion;
use Rector\Php73\Rector\FuncCall\JsonThrowOnErrorRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\TypeDeclaration\Rector\ClassMethod\ParamAnnotationIncorrectNullableRector;


return static function (RectorConfig $rectorConfig): void {
	$rectorConfig->paths([
		__DIR__ . '/src',
		__DIR__ . '/tests',
	]);

	$rectorConfig->importNames();
	$rectorConfig->parallel();
	$rectorConfig->cacheDirectory(__DIR__ . '/temp/rector');

	// Define what rule sets will be applied
	$rectorConfig->import(LevelSetList::UP_TO_PHP_81);
	$rectorConfig->import(SetList::CODE_QUALITY);
	$rectorConfig->import(SetList::NAMING);
	$rectorConfig->import(SetList::PSR_4);
	$rectorConfig->import(SetList::TYPE_DECLARATION);

	$rectorConfig->skip([
		JsonThrowOnErrorRector::class,
		ParamAnnotationIncorrectNullableRector::class,
		FlipTypeControlToUseExclusiveTypeRector::class,
	]);

	$rectorConfig->phpVersion(PhpVersion::PHP_81);
};
