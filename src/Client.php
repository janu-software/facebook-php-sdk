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

use Http\Client\HttpClient;
use Http\Discovery\HttpClientDiscovery;
use Http\Discovery\MessageFactoryDiscovery;
use JanuSoftware\Facebook\Exception\SDKException;
use function Safe\sprintf;


class Client
{
	/**
	 * @const string Production Graph API URL.
	 */
	public const BASE_GRAPH_URL = 'https://graph.facebook.com';

	/**
	 * @const string Graph API URL for video uploads.
	 */
	public const BASE_GRAPH_VIDEO_URL = 'https://graph-video.facebook.com';

	/**
	 * @const string Beta Graph API URL.
	 */
	public const BASE_GRAPH_URL_BETA = 'https://graph.beta.facebook.com';

	/**
	 * @const string Beta Graph API URL for video uploads.
	 */
	public const BASE_GRAPH_VIDEO_URL_BETA = 'https://graph-video.beta.facebook.com';

	/**
	 * @const int The timeout in seconds for a normal request.
	 */
	public const DEFAULT_REQUEST_TIMEOUT = 60;

	/**
	 * @const int The timeout in seconds for a request that contains file uploads.
	 */
	public const DEFAULT_FILE_UPLOAD_REQUEST_TIMEOUT = 3600;

	/**
	 * @const int The timeout in seconds for a request that contains video uploads.
	 */
	public const DEFAULT_VIDEO_UPLOAD_REQUEST_TIMEOUT = 7200;

	public static int $requestCount = 0;

	protected HttpClient $httpClient;


	/**
	 * Instantiates a new Client object.
	 */
	public function __construct(
		HttpClient $httpClient = null,
		protected bool $enableBetaMode = false,
	) {
		$this->httpClient = $httpClient ?? HttpClientDiscovery::find();
	}


	/**
	 * Sets the HTTP client handler.
	 */
	public function setHttpClient(HttpClient $httpClient): void
	{
		$this->httpClient = $httpClient;
	}


	/**
	 * Returns the HTTP client handler.
	 */
	public function getHttpClient(): HttpClient
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
				? static::BASE_GRAPH_VIDEO_URL_BETA
				: static::BASE_GRAPH_VIDEO_URL;
		}

		return $this->enableBetaMode ? static::BASE_GRAPH_URL_BETA : static::BASE_GRAPH_URL;
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

		/**
		 * @phpstan-ignore-next-line
		 */
		$psr7Response = $this->httpClient->sendRequest(MessageFactoryDiscovery::find()
			->createRequest($method, $url, $headers, $body));

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
