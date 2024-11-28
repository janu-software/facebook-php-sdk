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

use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use JanuSoftware\Facebook\Exception\SDKException;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\StreamInterface;


class Client
{
	/**
	 * @const string Production Graph API URL.
	 */
	final public const BaseGraphUrl = 'https://graph.facebook.com';

	/**
	 * @const string Graph API URL for video uploads.
	 */
	final public const BaseGraphVideoUrl = 'https://graph-video.facebook.com';

	/**
	 * @const string Beta Graph API URL.
	 */
	final public const BaseGraphUrlBeta = 'https://graph.beta.facebook.com';

	/**
	 * @const string Beta Graph API URL for video uploads.
	 */
	final public const BaseGraphVideoUrlBeta = 'https://graph-video.beta.facebook.com';

	public static int $requestCount = 0;

	protected ClientInterface $httpClient;


	/**
	 * Instantiates a new Client object.
	 */
	public function __construct(
		?ClientInterface $httpClient = null,
		protected bool $enableBetaMode = false,
	) {
		$this->httpClient = $httpClient ?? Psr18ClientDiscovery::find();
	}


	/**
	 * Sets the HTTP client handler.
	 */
	public function setHttpClient(ClientInterface $httpClient): void
	{
		$this->httpClient = $httpClient;
	}


	/**
	 * Returns the HTTP client handler.
	 */
	public function getHttpClient(): ClientInterface
	{
		return $this->httpClient;
	}


	/**
	 * Toggle beta mode.
	 */
	public function enableBetaMode(bool $betaMode = true): void
	{
		$this->enableBetaMode = $betaMode;
	}


	/**
	 * Returns the base Graph URL.
	 *
	 * @param bool $postToVideoUrl post to the video API if videos are being uploaded
	 */
	public function getBaseGraphUrl(bool $postToVideoUrl = false): string
	{
		if ($postToVideoUrl) {
			return $this->enableBetaMode
				? static::BaseGraphVideoUrlBeta
				: static::BaseGraphVideoUrl;
		}

		return $this->enableBetaMode ? static::BaseGraphUrlBeta : static::BaseGraphUrl;
	}


	/**
	 * Prepares the request for sending to the client handler.
	 */
	public function prepareRequestMessage(Request|BatchRequest $request): array
	{
		$postToVideoUrl = $request->containsVideoUploads();
		$url = $this->getBaseGraphUrl($postToVideoUrl) . $request->getUrl();

		// If we're sending files they should be sent as multipart/form-data
		if ($request->containsFileUploads()) {
			$requestBody = $request->getMultipartBody();
			$request->setHeaders([
				'Content-Type' => 'multipart/form-data; boundary=' . $requestBody->getBoundary(),
			]);
		} else {
			$requestBody = $request->getUrlEncodedBody();
			$request->setHeaders([
				'Content-Type' => 'application/x-www-form-urlencoded',
			]);
		}

		return [
			$url,
			$request->getMethod(),
			$request->getHeaders(),
			$requestBody->getBody(),
		];
	}


	/**
	 * Makes the request to Graph and returns the result.
	 * @throws SDKException
	 */
	public function sendRequest(Request|BatchRequest $request): Response
	{
		if ($request::class === Request::class) {
			$request->validateAccessToken();
		}

		[$url, $method, $headers, $body] = $this->prepareRequestMessage($request);

		$requestFactory = Psr17FactoryDiscovery::findRequestFactory()->createRequest($method, $url);
		if ($body instanceof StreamInterface) {
			$requestFactory = $requestFactory->withBody($body);
		}
		foreach ($headers as $name => $value) {
			$requestFactory = $requestFactory->withHeader($name, $value);
		}

		$psr7Response = $this->httpClient->sendRequest($requestFactory);

		static::$requestCount++;

		// Prepare headers from associative array to a single string for each header.
		$responseHeaders = [];
		foreach ($psr7Response->getHeaders() as $name => $values) {
			$responseHeaders[] = sprintf('%s: %s', $name, implode(', ', $values));
		}

		$response = new Response($request, $psr7Response->getBody()->getContents(), $psr7Response->getStatusCode(), $responseHeaders);

		if ($response->isError()) {
			throw $response->getThrownException();
		}

		return $response;
	}


	/**
	 * Makes a batched request to Graph and returns the result.
	 * @throws SDKException
	 */
	public function sendBatchRequest(BatchRequest $batchRequest): BatchResponse
	{
		$batchRequest->prepareRequestsForBatch();
		$response = $this->sendRequest($batchRequest);

		return new BatchResponse($batchRequest, $response);
	}
}
