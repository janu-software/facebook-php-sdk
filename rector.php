<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Core\ValueObject\PhpVersion;
use Rector\Set\ValueObject\SetList;
use Rector\TypeDeclaration\Rector\FunctionLike\ReturnTypeDeclarationRector;


return static function (RectorConfig $rectorConfig): void {
	$rectorConfig->paths([
		__DIR__ . '/src',
		__DIR__ . '/tests',
	]);

	$rectorConfig->importNames();
	$rectorConfig->cacheDirectory(__DIR__ . '/temp/rector');

	// Define what rule sets will be applied
	$rectorConfig->import(SetList::PHP_80);
	$rectorConfig->import(SetList::CODE_QUALITY);
	$rectorConfig->import(SetList::NAMING);
	$rectorConfig->import(SetList::PSR_4);
	$rectorConfig->import(SetList::TYPE_DECLARATION_STRICT);

	$rectorConfig->phpVersion(PhpVersion::PHP_80);

	$services = $rectorConfig->services();
	$services->set(ReturnTypeDeclarationRector::class);
};
