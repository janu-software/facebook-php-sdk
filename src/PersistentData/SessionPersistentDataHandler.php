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

namespace JanuSoftware\Facebook\PersistentData;

use JanuSoftware\Facebook\Exception\SDKException;

class SessionPersistentDataHandler implements PersistentDataInterface
{
	/** Prefix to use for session variables */
	protected string $sessionPrefix = 'FBRLH_';


	/**
	 * Init the session handler.
	 *
	 * @throws SDKException
	 */
	public function __construct(bool $enableSessionCheck = true)
	{
		if ($enableSessionCheck && session_status() !== PHP_SESSION_ACTIVE) {
			throw new SDKException(
				'Sessions are not active. Please make sure session_start() is at the top of your script.',
				720,
			);
		}
	}


	public function get(string $key): mixed
	{
		if (isset($_SESSION[$this->sessionPrefix . $key])) {
			return $_SESSION[$this->sessionPrefix . $key];
		}

		return null;
	}


	/**
	 * {@inheritdoc}
	 */
	public function set($key, $value): void
	{
		$_SESSION[$this->sessionPrefix . $key] = $value;
	}
}
