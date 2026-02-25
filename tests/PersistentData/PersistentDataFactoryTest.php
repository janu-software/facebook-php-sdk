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

use InvalidArgumentException;
use JanuSoftware\Facebook\PersistentData\InMemoryPersistentDataHandler;
use JanuSoftware\Facebook\PersistentData\PersistentDataFactory;
use JanuSoftware\Facebook\PersistentData\PersistentDataInterface;
use JanuSoftware\Facebook\PersistentData\SessionPersistentDataHandler;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PersistentDataFactoryTest extends TestCase
{
	public const COMMON_NAMESPACE = 'JanuSoftware\Facebook\PersistentData\\';
	public const COMMON_INTERFACE = PersistentDataInterface::class;


	#[DataProvider('persistentDataHandlerProviders')]
	public function testCreatePersistentDataHandler(mixed $handler, string $expected): void
	{
		$persistentDataHandler = PersistentDataFactory::createPersistentDataHandler($handler);

		$this->assertInstanceOf(self::COMMON_INTERFACE, $persistentDataHandler);
		$this->assertInstanceOf($expected, $persistentDataHandler);
	}


	public function testInvalidHandlerThrowsException(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('The persistent data handler must be set to "session", "memory", or be an instance of Facebook\PersistentData\PersistentDataInterface');

		PersistentDataFactory::createPersistentDataHandler('invalid_handler');
	}


	public function testFactoryCannotBeInstantiated(): void
	{
		$reflectionClass = new \ReflectionClass(PersistentDataFactory::class);
		$constructor = $reflectionClass->getConstructor();
		$this->assertNotNull($constructor);
		$this->assertTrue($constructor->isPrivate());
	}


	public static function persistentDataHandlerProviders(): array
	{
		$handlers = [
			['memory', self::COMMON_NAMESPACE . 'InMemoryPersistentDataHandler'],
			[new InMemoryPersistentDataHandler, self::COMMON_NAMESPACE . 'InMemoryPersistentDataHandler'],
			[new SessionPersistentDataHandler(false), self::COMMON_NAMESPACE . 'SessionPersistentDataHandler'],
			[null, self::COMMON_INTERFACE],
		];

		if (session_status() === PHP_SESSION_ACTIVE) {
			$handlers[] = ['session', self::COMMON_NAMESPACE . 'SessionPersistentDataHandler'];
		}

		return $handlers;
	}
}
