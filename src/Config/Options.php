<?php

/**
 * This file is part of the facebook-php-sdk
 * Copyright (c) 2022 Stanislav Janů (https://janu.software)
 */

declare(strict_types=1);

namespace JanuSoftware\FacebookSDK\Config;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use JanuSoftware\FacebookSDK\InvalidArgumentException;
use Safe\Exceptions\PcreException;
use function Safe\preg_match;


class Options
{
	protected const ApiVersionRegex = '~^v\d+\.\d+$~';
	protected string $graphApiVersion;
	protected ClientInterface $httpClient;

	public function __construct(
		protected int $appId,
		protected string $appSecret,
		string $graphApiVersion = 'v12.0',
		?ClientInterface $httpClient = null,
	) {
		try {
			if (!preg_match(self::ApiVersionRegex, $graphApiVersion)) {
				throw new InvalidArgumentException('The "graphApiVersion" must start with letter "v" followed by version number, ie: "v12.0".');
			}
			$this->graphApiVersion = $graphApiVersion;
		} catch (PcreException $exception) {
			throw new InvalidArgumentException($exception->getMessage(), $exception->getCode(), $exception);
		}

		$this->httpClient = $httpClient ?? new Client;
	}


	public function getAppId(): int
	{
		return $this->appId;
	}


	public function getAppSecret(): string
	{
		return $this->appSecret;
	}


	public function getGraphApiVersion(): string
	{
		return $this->graphApiVersion;
	}


	public function getHttpClient(): ClientInterface
	{
		return $this->httpClient;
	}
}
