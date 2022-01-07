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

namespace JanuSoftware\Facebook\GraphNode;

use DateTime;


class GraphSessionInfo extends GraphNode
{
	/**
	 * Returns the application id the token was issued for.
	 */
	public function getAppId(): ?string
	{
		return $this->getField('app_id');
	}


	/**
	 * Returns the application name the token was issued for.
	 */
	public function getApplication(): ?string
	{
		return $this->getField('application');
	}


	/**
	 * Returns the date & time that the token expires.
	 */
	public function getExpiresAt(): ?DateTime
	{
		return $this->getField('expires_at');
	}


	/**
	 * Returns whether the token is valid.
	 */
	public function getIsValid(): bool
	{
		return $this->getField('is_valid');
	}


	/**
	 * Returns the date & time the token was issued at.
	 */
	public function getIssuedAt(): ?DateTime
	{
		return $this->getField('issued_at');
	}


	/**
	 * Returns the scope permissions associated with the token.
	 */
	public function getScopes(): array
	{
		return $this->getField('scopes');
	}


	/**
	 * Returns the login id of the user associated with the token.
	 */
	public function getUserId(): ?string
	{
		return $this->getField('user_id');
	}
}
