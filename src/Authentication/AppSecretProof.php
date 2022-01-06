<?php

/**
 * This file is part of the facebook-php-sdk
 * Copyright (c) 2022 Stanislav Janů (https://janu.software)
 */

declare(strict_types=1);

namespace JanuSoftware\FacebookSDK\Authentication;

class AppSecretProof
{
	/**
	 * @see https://developers.facebook.com/docs/graph-api/securing-requests#appsecret_proof
	 */
	public static function create(string $appSecret, string $accessToken): string
	{
		return hash_hmac('sha256', $accessToken, $appSecret);
	}
}
