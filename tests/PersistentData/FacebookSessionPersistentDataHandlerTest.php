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

namespace JanuSoftware\Facebook\Tests\PersistentData;

use JanuSoftware\Facebook\Exception\SDKException;
use JanuSoftware\Facebook\PersistentData\SessionPersistentDataHandler;
use PHPUnit\Framework\TestCase;

class FacebookSessionPersistentDataHandlerTest extends TestCase
{
	public function testInactiveSessionsWillThrow(): void
	{
		$this->expectException(SDKException::class);
		new SessionPersistentDataHandler;
	}


	public function testCanSetAValue(): void
	{
		$handler = new SessionPersistentDataHandler($enableSessionCheck = false);
		$handler->set('foo', 'bar');

		$this->assertEquals('bar', $_SESSION['FBRLH_foo']);
	}


	public function testCanGetAValue(): void
	{
		$_SESSION['FBRLH_faz'] = 'baz';
		$handler = new SessionPersistentDataHandler($enableSessionCheck = false);
		$value = $handler->get('faz');

		$this->assertEquals('baz', $value);
	}


	public function testGettingAValueThatDoesntExistWillReturnNull(): void
	{
		$handler = new SessionPersistentDataHandler($enableSessionCheck = false);
		$value = $handler->get('does_not_exist');

		$this->assertNull($value);
	}
}
