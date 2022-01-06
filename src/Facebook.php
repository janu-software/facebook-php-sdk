<?php

/**
 * This file is part of the facebook-php-sdk
 * Copyright (c) 2022 Stanislav Janů (https://janu.software)
 */

declare(strict_types=1);

namespace JanuSoftware\FacebookSDK;


use JanuSoftware\FacebookSDK\Config\Options;


class Facebook
{
	protected App $app;
	protected Client $client;


	public function __construct(
		private Options $options,
	) {
		$this->app = new App($this->options->getAppId(), $this->options->getAppSecret());
		$this->client = new Client($this->options->getHttpClient());
	}
}
