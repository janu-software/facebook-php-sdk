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

namespace JanuSoftware\Facebook\Tests\Authentication;

use JanuSoftware\Facebook\Application;
use JanuSoftware\Facebook\Authentication\AccessToken;
use JanuSoftware\Facebook\Authentication\AccessTokenMetadata;
use JanuSoftware\Facebook\Authentication\OAuth2Client;
use JanuSoftware\Facebook\Facebook;
use PHPUnit\Framework\TestCase;

class OAuth2ClientTest extends TestCase
{
	/**
	 * @const The foo Graph version
	 */
	public const TESTING_GRAPH_VERSION = 'v1337';

	protected FooClientForOAuth2Test $client;
	protected OAuth2Client $oauth;


	protected function setUp(): void
	{
		$app = new Application('123', 'foo_secret');
		$this->client = new FooClientForOAuth2Test;
		$this->oauth = new OAuth2Client($app, $this->client, static::TESTING_GRAPH_VERSION);
	}


	public function testCanGetMetadataFromAnAccessToken(): void
	{
		$this->client->setMetadataResponse();

		$metadata = $this->oauth->debugToken('baz_token');

		$this->assertInstanceOf(AccessTokenMetadata::class, $metadata);
		$this->assertEquals('444', $metadata->getUserId());

		$expectedParams = [
			'input_token' => 'baz_token',
			'access_token' => '123|foo_secret',
			'appsecret_proof' => 'de753c58fd58b03afca2340bbaeb4ecf987b5de4c09e39a63c944dd25efbc234',
		];

		$request = $this->oauth->getLastRequest();
		$this->assertEquals('GET', $request->getMethod());
		$this->assertEquals('/debug_token', $request->getEndpoint());
		$this->assertEquals($expectedParams, $request->getParams());
		$this->assertEquals(static::TESTING_GRAPH_VERSION, $request->getGraphVersion());
	}


	public function testCanBuildAuthorizationUrl(): void
	{
		$scope = ['email', 'base_foo'];
		$authUrl = $this->oauth->getAuthorizationUrl('https://foo.bar', 'foo_state', $scope, ['foo' => 'bar'], '*');

		$this->assertStringContainsString('*', $authUrl);

		$expectedUrl = 'https://www.facebook.com/' . static::TESTING_GRAPH_VERSION . '/dialog/oauth?';
		$this->assertStringStartsWith($expectedUrl, $authUrl, 'Unexpected base authorization URL returned from getAuthorizationUrl().');

		$params = [
			'client_id' => '123',
			'redirect_uri' => 'https://foo.bar',
			'state' => 'foo_state',
			'sdk' => 'php-sdk-' . Facebook::VERSION,
			'scope' => implode(',', $scope),
			'foo' => 'bar',
		];
		foreach ($params as $key => $value) {
			$this->assertStringContainsString($key . '=' . urlencode($value), $authUrl);
		}
	}


	public function testCanGetAccessTokenFromCode(): void
	{
		$this->client->setAccessTokenResponse();

		$accessToken = $this->oauth->getAccessTokenFromCode('bar_code', 'foo_uri');

		$this->assertInstanceOf(AccessToken::class, $accessToken);
		$this->assertEquals('my_access_token', $accessToken->getValue());

		$expectedParams = [
			'code' => 'bar_code',
			'redirect_uri' => 'foo_uri',
			'client_id' => '123',
			'client_secret' => 'foo_secret',
			'access_token' => '123|foo_secret',
			'appsecret_proof' => 'de753c58fd58b03afca2340bbaeb4ecf987b5de4c09e39a63c944dd25efbc234',
		];

		$request = $this->oauth->getLastRequest();
		$this->assertEquals('GET', $request->getMethod());
		$this->assertEquals('/oauth/access_token', $request->getEndpoint());
		$this->assertEquals($expectedParams, $request->getParams());
		$this->assertEquals(static::TESTING_GRAPH_VERSION, $request->getGraphVersion());
	}


	public function testCanGetLongLivedAccessToken(): void
	{
		$this->client->setAccessTokenResponse();

		$accessToken = $this->oauth->getLongLivedAccessToken('short_token');

		$this->assertEquals('my_access_token', $accessToken->getValue());

		$expectedParams = [
			'grant_type' => 'fb_exchange_token',
			'fb_exchange_token' => 'short_token',
			'client_id' => '123',
			'client_secret' => 'foo_secret',
			'access_token' => '123|foo_secret',
			'appsecret_proof' => 'de753c58fd58b03afca2340bbaeb4ecf987b5de4c09e39a63c944dd25efbc234',
		];

		$request = $this->oauth->getLastRequest();
		$this->assertEquals($expectedParams, $request->getParams());
	}


	public function testCanGetCodeFromLongLivedAccessToken(): void
	{
		$this->client->setCodeResponse();

		$code = $this->oauth->getCodeFromLongLivedAccessToken('long_token', 'foo_uri');

		$this->assertEquals('my_neat_code', $code);

		$expectedParams = [
			'access_token' => 'long_token',
			'redirect_uri' => 'foo_uri',
			'client_id' => '123',
			'client_secret' => 'foo_secret',
			'appsecret_proof' => '7e91300ea91be4166282611d4fc700b473466f3ea2981dafbf492fc096995bf1',
		];

		$request = $this->oauth->getLastRequest();
		$this->assertEquals($expectedParams, $request->getParams());
		$this->assertEquals('/oauth/client_code', $request->getEndpoint());
	}
}
