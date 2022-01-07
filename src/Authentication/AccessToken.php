<?php

declare(strict_types=1);
/**
 * Copyright 2017 Facebook, Inc.
 *
 * You are hereby granted a non-exclusive, worldwide, royalty-free license to
 * use, copy, modify, and distribute this software in source code or binary
 * form for use in connection with the web services and APIs provided by
 * Facebook.
 *
 * As with any software that integrates with the Facebook platform, your use
 * of this software is subject to the Facebook Developer Principles and
 * Policies [http://developers.facebook.com/policy/]. This copyright notice
 * shall be included in all copies or substantial portions of the software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
 * DEALINGS IN THE SOFTWARE.
 */

namespace JanuSoftware\Facebook\Authentication;

use DateTimeInterface;
use Safe\DateTime;
use Stringable;

class AccessToken implements Stringable
{
	protected ?DateTimeInterface $expiresAt = null;


	public function __construct(
		protected string $value,
		int $expiresAt = 0,
	) {
		if ($expiresAt !== 0) {
			$this->setExpiresAtFromTimeStamp($expiresAt);
		}
	}


	/**
	 * Generate an app secret proof to sign a request to Graph.
	 */
	public function getAppSecretProof(string $appSecret): string
	{
		return hash_hmac('sha256', $this->value, $appSecret);
	}


	/**
	 * Getter for expiresAt.
	 */
	public function getExpiresAt(): ?DateTimeInterface
	{
		return $this->expiresAt;
	}


	/**
	 * Determines whether or not this is an app access token.
	 */
	public function isAppAccessToken(): bool
	{
		return str_contains($this->value, '|');
	}


	/**
	 * Determines whether or not this is a long-lived token.
	 */
	public function isLongLived(): bool
	{
		if ($this->expiresAt !== null) {
			return $this->expiresAt->getTimestamp() > time() + (60 * 60 * 2);
		}

		return $this->isAppAccessToken();
	}


	/**
	 * Checks the expiration of the access token.
	 */
	public function isExpired(): ?bool
	{
		if ($this->getExpiresAt() instanceof DateTimeInterface) {
			return $this->getExpiresAt()->getTimestamp() < time();
		}

		if ($this->isAppAccessToken()) {
			return false;
		}

		return null;
	}


	/**
	 * Returns the access token as a string.
	 */
	public function getValue(): string
	{
		return $this->value;
	}


	/**
	 * Returns the access token as a string.
	 */
	public function __toString(): string
	{
		return $this->getValue();
	}


	/**
	 * Setter for expires_at.
	 */
	protected function setExpiresAtFromTimeStamp(int $timeStamp): void
	{
		$dateTime = new DateTime;
		$dateTime->setTimestamp($timeStamp);
		$this->expiresAt = $dateTime;
	}
}
