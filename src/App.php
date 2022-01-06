<?php

/**
 * This file is part of the facebook-php-sdk
 * Copyright (c) 2022 Stanislav Janů (https://janu.software)
 */

declare(strict_types=1);

namespace JanuSoftware\FacebookSDK;

use JanuSoftware\FacebookSDK\Authentication\AccessToken;
use Serializable;


class App implements Serializable
{
	public function __construct(
		protected string $id,
		protected string $secret,
	) {
	}


	public function getId(): string
	{
		return $this->id;
	}


	public function getSecret(): string
	{
		return $this->secret;
	}


	public function getAccessToken(): AccessToken
	{
		return new AccessToken($this->id . '|' . $this->secret);
	}


	public function serialize(): string
	{
		return implode('|', [$this->id, $this->secret]);
	}


	public function unserialize(string $data): void
	{
		[$this->id, $this->secret] = explode('|', $data);
	}
}
