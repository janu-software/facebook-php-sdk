<?php

declare(strict_types=1);
/**
 * Copyright 2017 Facebook, Inc.
 * You are hereby granted a non-exclusive, worldwide, royalty-free license to
 * use, copy, modify, and distribute this software in source code or binary
 * form for use in connection with the web services and APIs provided by
 * Facebook.
 * As with any software that integrates with the Facebook platform, your use
 * of this software is subject to the Facebook Developer Principles and
 * Policies [http://developers.facebook.com/policy/]. This copyright notice
 * shall be included in all copies or substantial portions of the software.
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
 * DEALINGS IN THE SOFTWARE.
 */

namespace JanuSoftware\Facebook;

use JanuSoftware\Facebook\Authentication\AccessToken;
use Serializable;


class Application implements Serializable
{
	public function __construct(
		protected string $id,
		protected string $secret,
	) {
	}


	/**
	 * Returns the app ID.
	 */
	public function getId(): string
	{
		return $this->id;
	}


	/**
	 * Returns the app secret.
	 */
	public function getSecret(): string
	{
		return $this->secret;
	}


	/**
	 * Returns an app access token.
	 */
	public function getAccessToken(): AccessToken
	{
		return new AccessToken($this->id . '|' . $this->secret);
	}


	/**
	 * Serializes the Application entity as a string.
	 */
	public function serialize(): string
	{
		return implode('|', $this->__serialize());
	}


	/**
	 * Unserializes a string as an Application entity.
	 */
	public function unserialize(string $serialized): void
	{
		[$this->id, $this->secret] = explode('|', $serialized);
	}


	/**
	 * @return string[]
	 */
	public function __serialize(): array
	{
		return [$this->id, $this->secret];
	}


	public function __unserialize(array $data): void
	{
		[$this->id, $this->secret] = $data;
	}
}
