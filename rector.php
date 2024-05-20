<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Identical\FlipTypeControlToUseExclusiveTypeRector;
use Rector\Config\RectorConfig;
use Rector\Php73\Rector\FuncCall\JsonThrowOnErrorRector;
use Rector\Php80\Rector\FunctionLike\MixedTypeRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;


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
	$rectorConfig->import(SetList::TYPE_DECLARATION);

	$rectorConfig->skip([
		JsonThrowOnErrorRector::class,
		FlipTypeControlToUseExclusiveTypeRector::class,
		MixedTypeRector::class,
	]);
};
