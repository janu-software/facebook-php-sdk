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

namespace JanuSoftware\Facebook\Tests\Helper;

use JanuSoftware\Facebook\Application;
use JanuSoftware\Facebook\Client;
use JanuSoftware\Facebook\Facebook;
use JanuSoftware\Facebook\Helper\RedirectLoginHelper;
use JanuSoftware\Facebook\PersistentData\InMemoryPersistentDataHandler;
use JanuSoftware\Facebook\Tests\Fixtures\FooRedirectLoginOAuth2Client;
use PHPUnit\Framework\TestCase;

class RedirectLoginHelperTest extends TestCase
{
	public const REDIRECT_URL = 'http://invalid.zzz';
	protected InMemoryPersistentDataHandler $persistentDataHandler;
	protected RedirectLoginHelper $redirectLoginHelper;


	protected function setUp(): void
	{
		$this->persistentDataHandler = new InMemoryPersistentDataHandler;

		$app = new Application('123', 'foo_app_secret');
		$oAuth2Client = new FooRedirectLoginOAuth2Client($app, new Client, 'v1337');
		$this->redirectLoginHelper = new RedirectLoginHelper($oAuth2Client, $this->persistentDataHandler);
	}


	public function testLoginURL(): void
	{
		$scope = ['foo', 'bar'];
		$loginUrl = $this->redirectLoginHelper->getLoginUrl(self::REDIRECT_URL, $scope);

		$expectedUrl = 'https://www.facebook.com/v1337/dialog/oauth?';
		$this->assertStringStartsWith($expectedUrl, $loginUrl, 'Unexpected base login URL returned from getLoginUrl().');

		$params = [
			'client_id' => '123',
			'redirect_uri' => self::REDIRECT_URL,
			'state' => $this->persistentDataHandler->get('state'),
			'sdk' => 'php-sdk-' . Facebook::VERSION,
			'scope' => implode(',', $scope),
		];
		foreach ($params as $key => $value) {
			$this->assertStringContainsString($key . '=' . urlencode($value), $loginUrl);
		}
	}


	public function testLogoutURL(): void
	{
		$logoutUrl = $this->redirectLoginHelper->getLogoutUrl('foo_token', self::REDIRECT_URL);
		$expectedUrl = 'https://www.facebook.com/logout.php?';
		$this->assertStringStartsWith($expectedUrl, $logoutUrl, 'Unexpected base logout URL returned from getLogoutUrl().');

		$params = [
			'next' => self::REDIRECT_URL,
			'access_token' => 'foo_token',
		];
		foreach ($params as $key => $value) {
			$this->assertStringContainsString($key . '=' . urlencode($value), $logoutUrl);
		}
	}


	public function testAnAccessTokenCanBeObtainedFromRedirect(): void
	{
		$this->persistentDataHandler->set('state', 'foo_state');
		$_GET['state'] = 'foo_state';
		$_GET['code'] = 'foo_code';

		$accessToken = $this->redirectLoginHelper->getAccessToken(self::REDIRECT_URL);

		$this->assertEquals('foo_token_from_code|foo_code|' . self::REDIRECT_URL, (string) $accessToken);
	}
}
