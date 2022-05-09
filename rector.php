<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\ClassMethod\DateTimeToDateTimeInterfaceRector;
use Rector\Config\RectorConfig;
use Rector\Core\ValueObject\PhpVersion;
use Rector\Core\Configuration\Option;
use Rector\Set\ValueObject\SetList;
use Rector\Nette\Set\NetteSetList;
use Rector\TypeDeclaration\Rector\FunctionLike\ReturnTypeDeclarationRector;


return static function (RectorConfig $rectorConfig): void {
	// get parameters
	$parameters = $rectorConfig->parameters();

	$rectorConfig->paths([
		__DIR__ . '/src',
	]);

	$rectorConfig->importNames();
	$parameters->set(Option::CACHE_DIR, __DIR__ . '/temp/rector');

	// Define what rule sets will be applied
	$rectorConfig->import(SetList::PHP_80);
	$rectorConfig->import(SetList::CODE_QUALITY);
	$rectorConfig->import(SetList::NAMING);
	$rectorConfig->import(SetList::PSR_4);
	$rectorConfig->import(SetList::TYPE_DECLARATION_STRICT);
	$rectorConfig->import(NetteSetList::NETTE_CODE_QUALITY);

	$rectorConfig->skip([
		DateTimeToDateTimeInterfaceRector::class
	]);
	$rectorConfig->phpVersion(PhpVersion::PHP_80);

	$services = $rectorConfig->services();
	$services->set(ReturnTypeDeclarationRector::class);
};
