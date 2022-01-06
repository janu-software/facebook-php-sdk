<?php

/**
 * This file is part of the facebook-php-sdk
 * Copyright (c) 2022 Stanislav Janů (https://janu.software)
 */

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use JanuSoftware\FacebookSDK\Authentication\AccessToken;
use Tester\Assert;

$at = new AccessToken(
	'abc',
);

Assert::same('abc', $at->getValue(), 'Value parameter is wrong.');
Assert::same(null, $at->getExpiresAt(), 'ExpiresAt parameter is wrong.');
Assert::same(null, $at->isExpired(), 'IsExpired method is wrong.');
Assert::false($at->isLongLived(), 'IsLongLived method is wrong.');

$time = new DateTime('now');
$at = new AccessToken(
	'def',
	$time,
);

Assert::same('def', $at->getValue(), 'Value parameter is wrong.');
Assert::same($time, $at->getExpiresAt(), 'ExpiresAt parameter is wrong.');
Assert::false($at->isExpired(), 'IsExpired method is wrong.');
Assert::false($at->isLongLived(), 'IsLongLived method is wrong.');

$time = (new DateTime('now'))->getTimestamp();
$at = new AccessToken(
	'xyz',
	$time,
);

Assert::same('xyz', $at->getValue(), 'Value parameter is wrong.');
Assert::same($time, $at->getExpiresAt()->getTimestamp(), 'ExpiresAt parameter is wrong.');
Assert::false($at->isExpired(), 'IsExpired method is wrong.');
Assert::false($at->isLongLived(), 'IsLongLived method is wrong.');

$at = new AccessToken('', time() + (60 * 60 * 3));
Assert::false($at->isExpired(), 'IsExpired method is wrong.');
Assert::true($at->isLongLived(), 'IsLongLived method is wrong.');

$at = new AccessToken('', time() - 1);
Assert::true($at->isExpired(), 'IsExpired method is wrong.');
Assert::false($at->isLongLived(), 'IsLongLived method is wrong.');
