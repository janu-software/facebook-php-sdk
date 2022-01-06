<?php

/**
 * This file is part of the facebook-php-sdk
 * Copyright (c) 2022 Stanislav Janů (https://janu.software)
 */

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use JanuSoftware\FacebookSDK\Client;
use Tester\Assert;

$client = new Client;

Assert::true($client instanceof Client, 'x');
