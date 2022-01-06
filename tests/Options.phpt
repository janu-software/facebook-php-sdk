<?php

/**
 * This file is part of the facebook-php-sdk
 * Copyright (c) 2022 Stanislav Janů (https://janu.software)
 */

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use GuzzleHttp\Client;
use JanuSoftware\FacebookSDK\Config\Options;
use JanuSoftware\FacebookSDK\InvalidArgumentException;
use Tester\Assert;

$client = new Client;
$options = new Options(
	123,
	'def',
	httpClient: $client,
);

Assert::same(123, $options->getAppId(), 'AppId parameter is wrong.');
Assert::same('def', $options->getAppSecret(), 'AppSecret parameter is wrong.');
Assert::same('v12.0', $options->getGraphApiVersion(), 'GraphApiVersion parameter is wrong.');
Assert::same($client, $options->getHttpClient(), 'HttpClient parameter is wrong.');

Assert::exception(function () {
	new Options(
		123,
		'def',
		'12.0',
	);
}, InvalidArgumentException::class, 'The "graphApiVersion" must start with letter "v" followed by version number, ie: "v12.0".');
