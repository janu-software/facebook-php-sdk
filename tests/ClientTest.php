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

namespace JanuSoftware\Facebook\Tests;

use JanuSoftware\Facebook\Application;
use JanuSoftware\Facebook\BatchRequest;
use JanuSoftware\Facebook\BatchResponse;
use JanuSoftware\Facebook\Client;
use JanuSoftware\Facebook\Exception\SDKException;
use JanuSoftware\Facebook\FileUpload\File;
use JanuSoftware\Facebook\FileUpload\Video;
use JanuSoftware\Facebook\GraphNode\GraphNode;
use JanuSoftware\Facebook\Request;
use JanuSoftware\Facebook\Response;
use JanuSoftware\Facebook\Tests\Fixtures\MyFooBatchHttpClient;
use JanuSoftware\Facebook\Tests\Fixtures\MyFooHttpClient;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;


class ClientTest extends TestCase
{
	public Application $fbApp;
	public Client $fbClient;
	public static Application $testApp;
	public static Client $testClient;


	protected function setUp(): void
	{
		$this->fbApp = new Application('id', 'shhhh!');
		$this->fbClient = new Client(new MyFooHttpClient);
	}


	public function testACustomHttpClientCanBeInjected(): void
	{
		$handler = new MyFooHttpClient;
		$client = new Client($handler);
		$httpClient = $client->getHttpClient();

		$this->assertInstanceOf(MyFooHttpClient::class, $httpClient);
	}


	public function testTheHttpClientWillFallbackToDefault(): void
	{
		$client = new Client;
		$httpClient = $client->getHttpClient();

		$this->assertInstanceOf(ClientInterface::class, $httpClient);
	}


	public function testBetaModeCanBeDisabledOrEnabledViaConstructor(): void
	{
		$client = new Client(null, false);
		$url = $client->getBaseGraphUrl();
		$this->assertEquals(Client::BaseGraphUrl, $url);

		$client = new Client(null, true);
		$url = $client->getBaseGraphUrl();
		$this->assertEquals(Client::BaseGraphUrlBeta, $url);
	}


	public function testBetaModeCanBeDisabledOrEnabledViaMethod(): void
	{
		$client = new Client;
		$client->enableBetaMode(false);
		$url = $client->getBaseGraphUrl();
		$this->assertEquals(Client::BaseGraphUrl, $url);

		$client->enableBetaMode(true);
		$url = $client->getBaseGraphUrl();
		$this->assertEquals(Client::BaseGraphUrlBeta, $url);
	}


	public function testGraphVideoUrlCanBeSet(): void
	{
		$client = new Client;
		$client->enableBetaMode(false);
		$url = $client->getBaseGraphUrl($postToVideoUrl = true);
		$this->assertEquals(Client::BaseGraphVideoUrl, $url);

		$client->enableBetaMode(true);
		$url = $client->getBaseGraphUrl($postToVideoUrl = true);
		$this->assertEquals(Client::BaseGraphVideoUrlBeta, $url);
	}


	public function testARequestEntityCanBeUsedToSendARequestToGraph(): void
	{
		$fbRequest = new Request($this->fbApp, 'token', 'GET', '/foo');
		$response = $this->fbClient->sendRequest($fbRequest);

		$this->assertInstanceOf(Response::class, $response);
		$this->assertEquals(200, $response->getHttpStatusCode());
		$this->assertEquals('{"data":[{"id":"123","name":"Foo"},{"id":"1337","name":"Bar"}]}', $response->getBody());
	}


	public function testABatchRequestEntityCanBeUsedToSendABatchRequestToGraph(): void
	{
		$fbRequests = [
			new Request($this->fbApp, 'token', 'GET', '/foo'),
			new Request($this->fbApp, 'token', 'POST', '/bar'),
		];
		$fbBatchRequest = new BatchRequest($this->fbApp, $fbRequests);

		$fbBatchClient = new Client(new MyFooBatchHttpClient);
		$response = $fbBatchClient->sendBatchRequest($fbBatchRequest);

		$this->assertInstanceOf(BatchResponse::class, $response);
		$this->assertEquals('GET', $response[0]->getRequest()->getMethod());
		$this->assertEquals('POST', $response[1]->getRequest()->getMethod());
	}


	public function testABatchRequestWillProperlyBatchFiles(): void
	{
		$fbRequests = [
			new Request($this->fbApp, 'token', 'POST', '/photo', [
				'message' => 'foobar',
				'source' => new File(__DIR__ . '/foo.txt'),
			]),
			new Request($this->fbApp, 'token', 'POST', '/video', [
				'message' => 'foobar',
				'source' => new Video(__DIR__ . '/foo.txt'),
			]),
		];
		$fbBatchRequest = new BatchRequest($this->fbApp, $fbRequests);
		$fbBatchRequest->prepareRequestsForBatch();

		[$url, $method, $headers, $body] = $this->fbClient->prepareRequestMessage($fbBatchRequest);

		$body = $body?->getContents() ?? '';

		$this->assertEquals(Client::BaseGraphVideoUrl, $url);
		$this->assertEquals('POST', $method);
		$this->assertStringContainsString('multipart/form-data; boundary=', $headers['Content-Type']);
		$this->assertStringContainsString('Content-Disposition: form-data; name="batch"', $body);
		$this->assertStringContainsString('Content-Disposition: form-data; name="include_headers"', $body);
		$this->assertStringContainsString('"attached_files":', $body);
		$this->assertStringContainsString('"; filename="foo.txt"', $body);
	}


	public function testARequestOfParamsWillBeUrlEncoded(): void
	{
		$fbRequest = new Request($this->fbApp, 'token', 'POST', '/foo', ['foo' => 'bar']);
		$response = $this->fbClient->sendRequest($fbRequest);

		$headersSent = $response->getRequest()->getHeaders();

		$this->assertEquals('application/x-www-form-urlencoded', $headersSent['Content-Type']);
	}


	public function testARequestWithFilesWillBeMultipart(): void
	{
		$myFile = new File(__DIR__ . '/foo.txt');
		$fbRequest = new Request($this->fbApp, 'token', 'POST', '/foo', ['file' => $myFile]);
		$response = $this->fbClient->sendRequest($fbRequest);

		$headersSent = $response->getRequest()->getHeaders();

		$this->assertStringContainsString('multipart/form-data; boundary=', $headersSent['Content-Type']);
	}


	public function testARequestValidatesTheAccessTokenWhenOneIsNotProvided(): void
	{
		$this->expectException(SDKException::class);
		$fbRequest = new Request($this->fbApp, null, 'GET', '/foo');
		$this->fbClient->sendRequest($fbRequest);
	}


	/**
	 * @group integration
	 */
	public function testCanCreateATestUserAndGetTheProfileAndThenDeleteTheTestUser(): void
	{
		$this->initializeTestApp();

		// Create a test user
		$testUserPath = '/' . TestCredentials::$appId . '/accounts/test-users';
		$params = [
			'installed' => true,
			'name' => 'Foo Phpunit User',
			'locale' => 'en_US',
			'permissions' => implode(',', ['read_stream', 'user_photos']),
		];

		$request = new Request(
			static::$testApp,
			static::$testApp->getAccessToken(),
			'POST',
			$testUserPath,
			$params,
		);
		$response = static::$testClient->sendRequest($request)->getGraphNode();

		$testUserId = $response->getField('id');
		$testUserAccessToken = $response->getField('access_token');

		// Get the test user's profile
		$request = new Request(
			static::$testApp,
			$testUserAccessToken,
			'GET',
			'/me',
		);
		$graphNode = static::$testClient->sendRequest($request)->getGraphNode();

		$this->assertInstanceOf(GraphNode::class, $graphNode);
		$this->assertNotNull($graphNode->getField('id'));
		$this->assertEquals('Foo Phpunit User', $graphNode->getField('name'));

		// Delete test user
		$request = new Request(
			static::$testApp,
			static::$testApp->getAccessToken(),
			'DELETE',
			'/' . $testUserId,
		);
		$graphNode = static::$testClient->sendRequest($request)->getGraphNode();

		$this->assertTrue($graphNode->getField('success'));
	}


	public function initializeTestApp(): void
	{
		if (!file_exists(__DIR__ . '/TestCredentials.php')) {
			throw new SDKException(
				'You must create a TestCredentials.php file from TestCredentials.php.dist',
			);
		}

		if (!strlen(TestCredentials::$appId) ||
			!strlen(TestCredentials::$appSecret)
		) {
			throw new SDKException(
				'You must fill out TestCredentials.php',
			);
		}
		static::$testApp = new Application(
			TestCredentials::$appId,
			TestCredentials::$appSecret,
		);

		static::$testClient = new Client;
	}
}
