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

use InvalidArgumentException;
use JanuSoftware\Facebook\Authentication\AccessToken;
use JanuSoftware\Facebook\Client;
use JanuSoftware\Facebook\Exception\ResponseException;
use JanuSoftware\Facebook\Exception\SDKException;
use JanuSoftware\Facebook\Facebook;
use JanuSoftware\Facebook\GraphNode\GraphEdge;
use JanuSoftware\Facebook\GraphNode\GraphNode;
use JanuSoftware\Facebook\Helper\CanvasHelper;
use JanuSoftware\Facebook\Helper\JavaScriptHelper;
use JanuSoftware\Facebook\PersistentData\InMemoryPersistentDataHandler;
use JanuSoftware\Facebook\Request;
use JanuSoftware\Facebook\Response;
use JanuSoftware\Facebook\Tests\Fixtures\FakeGraphApiForResumableUpload;
use JanuSoftware\Facebook\Tests\Fixtures\FooHttpClientInterface;
use JanuSoftware\Facebook\Tests\Fixtures\FooPersistentDataInterface;
use JanuSoftware\Facebook\Tests\Fixtures\FooUrlDetectionInterface;
use JanuSoftware\Facebook\Url\UrlDetectionHandler;
use PHPUnit\Framework\TestCase;
use stdClass;

class FacebookTest extends TestCase
{
	protected $config = [
		'app_id' => '1337',
		'app_secret' => 'foo_secret',
		'default_graph_version' => 'v0.0',
	];


	public function testInstantiatingWithoutAppIdThrows(): void
	{
		$this->expectException(SDKException::class);
		// unset value so there is no fallback to test expected Exception
		putenv(Facebook::AppIdEnvName . '=');
		$config = [
			'app_secret' => 'foo_secret',
			'default_graph_version' => 'v0.0',
		];
		new Facebook($config);
	}


	public function testInstantiatingWithoutAppSecretThrows(): void
	{
		$this->expectException(SDKException::class);
		// unset value so there is no fallback to test expected Exception
		putenv(Facebook::AppSecretEnvName . '=');
		$config = [
			'app_id' => 'foo_id',
			'default_graph_version' => 'v0.0',
		];
		new Facebook($config);
	}


	public function testInstantiatingWithoutDefaultGraphVersionThrows(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$config = [
			'app_id' => 'foo_id',
			'app_secret' => 'foo_secret',
		];
		new Facebook($config);
	}


	public function testSettingAnInvalidHttpClientTypeThrows(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$config = array_merge($this->config, [
			'http_client' => 'foo_client',
		]);
		new Facebook($config);
	}


	public function testSettingAnInvalidHttpClientClassThrows(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$config = array_merge($this->config, [
			'http_client' => new stdClass,
		]);
		new Facebook($config);
	}


	public function testSettingAnInvalidPersistentDataHandlerThrows(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$config = array_merge($this->config, [
			'persistent_data_handler' => 'foo_handler',
		]);
		new Facebook($config);
	}


	public function testPersistentDataHandlerCanBeForced(): void
	{
		$config = array_merge($this->config, [
			'persistent_data_handler' => 'memory',
		]);
		$fb = new Facebook($config);
		$this->assertInstanceOf(
			InMemoryPersistentDataHandler::class,
			$fb->getRedirectLoginHelper()->getPersistentDataHandler(),
		);
	}


	public function testSettingAnInvalidUrlHandlerThrows(): void
	{
		$this->expectException(\TypeError::class);
		$config = array_merge($this->config, [
			'url_detection_handler' => 'foo_handler',
		]);
		new Facebook($config);
	}


	public function testTheUrlHandlerWillDefaultToTheImplementation(): void
	{
		$fb = new Facebook($this->config);
		$this->assertInstanceOf(UrlDetectionHandler::class, $fb->getUrlDetectionHandler());
	}


	public function testAnAccessTokenCanBeSetAsAString(): void
	{
		$fb = new Facebook($this->config);
		$fb->setDefaultAccessToken('foo_token');
		$accessToken = $fb->getDefaultAccessToken();

		$this->assertInstanceOf(AccessToken::class, $accessToken);
		$this->assertEquals('foo_token', (string) $accessToken);
	}


	public function testAnAccessTokenCanBeSetAsAnAccessTokenEntity(): void
	{
		$fb = new Facebook($this->config);
		$fb->setDefaultAccessToken(new AccessToken('bar_token'));
		$accessToken = $fb->getDefaultAccessToken();

		$this->assertInstanceOf(AccessToken::class, $accessToken);
		$this->assertEquals('bar_token', (string) $accessToken);
	}


	public function testSettingAnAccessThatIsNotStringOrAccessTokenThrows(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$config = array_merge($this->config, [
			'default_access_token' => 123,
		]);
		new Facebook($config);
	}


	public function testCreatingANewRequestWillDefaultToTheProperConfig(): void
	{
		$config = array_merge($this->config, [
			'default_access_token' => 'foo_token',
			'enable_beta_mode' => true,
			'default_graph_version' => 'v1337',
		]);
		$fb = new Facebook($config);

		$request = $fb->request('FOO_VERB', '/foo');
		$this->assertEquals('1337', $request->getApplication()->getId());
		$this->assertEquals('foo_secret', $request->getApplication()->getSecret());
		$this->assertEquals('foo_token', (string) $request->getAccessToken());
		$this->assertEquals('v1337', $fb->getDefaultGraphVersion());
		$this->assertEquals('v1337', $request->getGraphVersion());
		$this->assertEquals(
			Client::BaseGraphUrlBeta,
			$fb->getClient()->getBaseGraphUrl(),
		);
	}


	public function testCreatingANewBatchRequestWillDefaultToTheProperConfig(): void
	{
		$config = array_merge($this->config, [
			'default_access_token' => 'foo_token',
			'enable_beta_mode' => true,
			'default_graph_version' => 'v1337',
		]);
		$fb = new Facebook($config);

		$batchRequest = $fb->newBatchRequest();
		$this->assertEquals('1337', $batchRequest->getApplication()->getId());
		$this->assertEquals('foo_secret', $batchRequest->getApplication()->getSecret());
		$this->assertEquals('foo_token', (string) $batchRequest->getAccessToken());
		$this->assertEquals('v1337', $batchRequest->getGraphVersion());
		$this->assertEquals(
			Client::BaseGraphUrlBeta,
			$fb->getClient()->getBaseGraphUrl(),
		);
		$this->assertInstanceOf('JanuSoftware\Facebook\BatchRequest', $batchRequest);
		$this->assertCount(0, $batchRequest->getRequests());
	}


	public function testCanInjectCustomHandlers(): void
	{
		$config = array_merge($this->config, [
			'http_client' => new FooHttpClientInterface,
			'persistent_data_handler' => new FooPersistentDataInterface,
			'url_detection_handler' => new FooUrlDetectionInterface,
		]);
		$fb = new Facebook($config);

		$this->assertInstanceOf(
			FooHttpClientInterface::class,
			$fb->getClient()->getHttpClient(),
		);
		$this->assertInstanceOf(
			FooPersistentDataInterface::class,
			$fb->getRedirectLoginHelper()->getPersistentDataHandler(),
		);
		$this->assertInstanceOf(
			FooUrlDetectionInterface::class,
			$fb->getRedirectLoginHelper()->getUrlDetectionHandler(),
		);
	}


	public function testPaginationReturnsProperResponse(): void
	{
		$config = array_merge($this->config, [
			'http_client' => new FooHttpClientInterface,
		]);
		$fb = new Facebook($config);

		$request = new Request($fb->getApplication(), 'foo_token', 'GET');
		$graphEdge = new GraphEdge(
			$request,
			[],
			[
				'paging' => [
					'cursors' => [
						'after' => 'bar_after_cursor',
						'before' => 'bar_before_cursor',
					],
					'previous' => 'previous_url',
					'next' => 'next_url',
				],
			],
			'/1337/photos',
			GraphNode::class,
		);

		$nextPage = $fb->next($graphEdge);
		$this->assertInstanceOf(GraphEdge::class, $nextPage);
		$this->assertInstanceOf(GraphNode::class, $nextPage[0]);
		$this->assertEquals('Foo', $nextPage[0]->getField('name'));

		$prevPage = $fb->previous($graphEdge);
		$this->assertInstanceOf(GraphEdge::class, $prevPage);
		$this->assertInstanceOf(GraphNode::class, $prevPage[0]);
		$this->assertEquals('Foo', $prevPage[0]->getField('name'));

		$lastResponse = $fb->getLastResponse();
		$this->assertInstanceOf(Response::class, $lastResponse);
		$this->assertEquals(321, $lastResponse->getHttpStatusCode());
	}


	public function testCanGetSuccessfulTransferWithMaxTries(): void
	{
		$config = array_merge($this->config, [
			'http_client' => new FakeGraphApiForResumableUpload,
		]);
		$fb = new Facebook($config);
		$response = $fb->uploadVideo('me', __DIR__ . '/foo.txt', [], 'foo-token', 3);
		$this->assertEquals([
			'video_id' => '1337',
			'success' => true,
		], $response);
	}


	public function testMaxingOutRetriesWillThrow(): void
	{
		$this->expectException(ResponseException::class);
		$client = new FakeGraphApiForResumableUpload;
		$client->failOnTransfer();

		$config = array_merge($this->config, [
			'http_client' => $client,
		]);
		$fb = new Facebook($config);
		$fb->uploadVideo('4', __DIR__ . '/foo.txt', [], 'foo-token', 3);
	}


	public function testFileToUpload(): void
	{
		$fb = new Facebook($this->config);
		$file = $fb->fileToUpload(__DIR__ . '/foo.txt');
		$this->assertEquals(__DIR__ . '/foo.txt', $file->getFilePath());
		$this->assertEquals('foo.txt', $file->getFileName());
		$this->assertEquals('This is a text file used for testing. Let\'s dance.', $file->getContents());
		$this->assertEquals('text/plain', $file->getMimetype());
		$this->assertEquals(50, $file->getSize());
	}


	public function testOthers(): void
	{
		$fb = new Facebook($this->config);
		$this->assertInstanceOf(JavaScriptHelper::class, $fb->getJavaScriptHelper());
		$this->assertInstanceOf(CanvasHelper::class, $fb->getCanvasHelper());
	}
}
