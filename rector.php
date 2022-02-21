<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\ClassMethod\DateTimeToDateTimeInterfaceRector;
use Rector\Core\ValueObject\PhpVersion;
use Rector\Core\Configuration\Option;
use Rector\Set\ValueObject\SetList;
use Rector\Nette\Set\NetteSetList;
use Rector\TypeDeclaration\Rector\FunctionLike\ReturnTypeDeclarationRector;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;


return static function (ContainerConfigurator $containerConfigurator): void {
	// get parameters
	$parameters = $containerConfigurator->parameters();

	$parameters->set(Option::PATHS, [
		__DIR__ . '/src',
	]);

	$parameters->set(Option::AUTO_IMPORT_NAMES, true);
	$parameters->set(Option::CACHE_DIR, __DIR__ . '/temp/rector');

	// Define what rule sets will be applied
	$containerConfigurator->import(SetList::PHP_80);
	$containerConfigurator->import(SetList::CODE_QUALITY);
	$containerConfigurator->import(SetList::NAMING);
	$containerConfigurator->import(SetList::PSR_4);
	$containerConfigurator->import(SetList::TYPE_DECLARATION_STRICT);
	$containerConfigurator->import(NetteSetList::NETTE_CODE_QUALITY);

	$parameters->set(Option::SKIP, [
		DateTimeToDateTimeInterfaceRector::class
	]);
	$parameters->set(Option::PHP_VERSION_FEATURES, PhpVersion::PHP_80);

	$services = $containerConfigurator->services();
	$services->set(ReturnTypeDeclarationRector::class);
};
