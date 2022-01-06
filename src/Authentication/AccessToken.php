<?php

/**
 * This file is part of the facebook-php-sdk
 * Copyright (c) 2022 Stanislav Janů (https://janu.software)
 */

declare(strict_types=1);

namespace JanuSoftware\FacebookSDK\Authentication;


use DateTime;


class AccessToken
{
	protected ?DateTime $expiresAt = null;


	public function __construct(protected string $value, int|DateTime $expiresAt = null)
	{
		if ($expiresAt !== null) {
			$this->expiresAt = $expiresAt instanceof DateTime ? $expiresAt : (new DateTime)->setTimestamp($expiresAt);
		}
	}


	public function getValue(): string
	{
		return $this->value;
	}


	public function getExpiresAt(): ?DateTime
	{
		return $this->expiresAt;
	}


	public function getAppSecretProof(string $appSecret): string
	{
		return AppSecretProof::create($appSecret, $this->value);
	}


	public function isAppAccessToken(): bool
	{
		return str_contains($this->value, '|');
	}


	public function isLongLived(): bool
	{
		if ($this->expiresAt !== null) {
			return $this->expiresAt->getTimestamp() > time() + (60 * 60 * 2);
		}

		if ($this->isAppAccessToken()) {
			return true;
		}

		return false;
	}


	public function isExpired(): ?bool
	{
		if ($this->expiresAt !== null) {
			return $this->expiresAt->getTimestamp() < time();
		}

		if ($this->isAppAccessToken()) {
			return false;
		}

		return null;
	}


	public function __toString(): string
	{
		return $this->getValue();
	}
}
