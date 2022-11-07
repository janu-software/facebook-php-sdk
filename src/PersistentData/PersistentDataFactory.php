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

namespace JanuSoftware\Facebook\PersistentData;

use InvalidArgumentException;


class PersistentDataFactory
{
	private function __construct()
	{
		// a factory constructor should never be invoked
	}


	/**
	 * PersistentData generation.
	 *
	 * @throws InvalidArgumentException if the persistent data handler isn't "session", "memory", or an instance of Facebook\PersistentData\PersistentDataInterface
	 */
	public static function createPersistentDataHandler(
		null|PersistentDataInterface|string $handler,
	): InMemoryPersistentDataHandler|PersistentDataInterface|SessionPersistentDataHandler
	{
		if ($handler === null) {
			return session_status() === PHP_SESSION_ACTIVE
				? new SessionPersistentDataHandler
				: new InMemoryPersistentDataHandler;
		}

		if ($handler instanceof PersistentDataInterface) {
			return $handler;
		}

		if ($handler === 'session') {
			return new SessionPersistentDataHandler;
		}
		if ($handler === 'memory') {
			return new InMemoryPersistentDataHandler;
		}

		throw new InvalidArgumentException('The persistent data handler must be set to "session", "memory", or be an instance of Facebook\PersistentData\PersistentDataInterface');
	}
}
