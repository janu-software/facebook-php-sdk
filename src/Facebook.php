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
use InvalidArgumentException;
use JanuSoftware\Facebook\Authentication\AccessToken;
use JanuSoftware\Facebook\Authentication\OAuth2Client;
use JanuSoftware\Facebook\Exception\SDKException;
use JanuSoftware\Facebook\FileUpload\File;
use JanuSoftware\Facebook\FileUpload\ResumableUploader;
use JanuSoftware\Facebook\FileUpload\TransferChunk;
use JanuSoftware\Facebook\FileUpload\Video;
use JanuSoftware\Facebook\GraphNode\GraphEdge;
use JanuSoftware\Facebook\Helper\CanvasHelper;
use JanuSoftware\Facebook\Helper\JavaScriptHelper;
use JanuSoftware\Facebook\Helper\PageTabHelper;
use JanuSoftware\Facebook\Helper\RedirectLoginHelper;
use JanuSoftware\Facebook\PersistentData\PersistentDataFactory;
use JanuSoftware\Facebook\PersistentData\PersistentDataInterface;
use JanuSoftware\Facebook\Url\UrlDetectionHandler;
use JanuSoftware\Facebook\Url\UrlDetectionInterface;
use Safe\Exceptions\FilesystemException;
use TypeError;


class Facebook
{
	/**
	 * @const string Version number of the Facebook PHP SDK.
	 */
	public const VERSION = '0.1';

	/**
	 * @const string The name of the environment variable that contains the app ID.
	 */
	public const APP_ID_ENV_NAME = 'FACEBOOK_APP_ID';

	/**
	 * @const string The name of the environment variable that contains the app secret.
	 */
	public const APP_SECRET_ENV_NAME = 'FACEBOOK_APP_SECRET';

	/**
	 * The Application entity
	 */
	protected Application $app;

	/**
	 * The Facebook client service
	 */
	protected Client $client;

	/**
	 * The OAuth 2.0 client service.
	 */
	protected ?OAuth2Client $oAuth2Client = null;

	/**
	 * The URL detection handler
	 */
	protected null|UrlDetectionInterface $urlDetectionHandler;

	/**
	 * The default access token to use with requests
	 */
	protected ?AccessToken $defaultAccessToken = null;

	/**
	 * The default Graph version we want to use
	 */
	protected ?string $defaultGraphVersion = null;

	/**
	 * The persistent data handler
	 */
	protected ?PersistentDataInterface $persistentDataHandler = null;

	/**
	 * Stores the last request made to Graph
	 */
	protected null|BatchResponse|Response $lastResponse = null;


	/**
	 * Instantiates a new Facebook super-class object.
	 * @throws SDKException
	 */
	public function __construct(array $config = [])
	{
		$config = array_merge([
			'app_id' => getenv(static::APP_ID_ENV_NAME),
			'app_secret' => getenv(static::APP_SECRET_ENV_NAME),
			'default_graph_version' => null,
			'enable_beta_mode' => false,
			'http_client' => null,
			'persistent_data_handler' => null,
			'url_detection_handler' => null,
		], $config);

		if (!$config['app_id']) {
			throw new SDKException('Required "app_id" key not supplied in config and could not find fallback environment variable "' . static::APP_ID_ENV_NAME . '"');
		}
		if (!$config['app_secret']) {
			throw new SDKException('Required "app_secret" key not supplied in config and could not find fallback environment variable "' . static::APP_SECRET_ENV_NAME . '"');
		}
		if ($config['http_client'] !== null && !$config['http_client'] instanceof HttpClient) {
			throw new InvalidArgumentException('Required "http_client" key to be null or an instance of \Http\Client\HttpClient');
		}
		if (!$config['default_graph_version']) {
			throw new InvalidArgumentException('Required "default_graph_version" key not supplied in config');
		}

		$this->app = new Application($config['app_id'], $config['app_secret']);
		$this->client = new Client($config['http_client'], $config['enable_beta_mode']);
		$this->setUrlDetectionHandler($config['url_detection_handler'] ?? new UrlDetectionHandler);
		$this->persistentDataHandler = PersistentDataFactory::createPersistentDataHandler($config['persistent_data_handler']);

		if (isset($config['default_access_token'])) {
			try {
				$this->setDefaultAccessToken($config['default_access_token']);
			}
			/**
			 * @phpstan-ignore-next-line
			 */
			catch (TypeError) {
				throw new InvalidArgumentException('Key "default_access_token" must be string or class AccessToken');
			}
		}

		$this->defaultGraphVersion = $config['default_graph_version'];
	}


	/**
	 * Returns the Application entity.
	 */
	public function getApplication(): Application
	{
		return $this->app;
	}


	/**
	 * Returns the Client service.
	 */
	public function getClient(): Client
	{
		return $this->client;
	}


	/**
	 * Returns the OAuth 2.0 client service.
	 */
	public function getOAuth2Client(): OAuth2Client
	{
		if ($this->oAuth2Client === null) {
			$application = $this->getApplication();
			$client = $this->getClient();
			$this->oAuth2Client = new OAuth2Client($application, $client, $this->defaultGraphVersion);
		}

		return $this->oAuth2Client;
	}


	/**
	 * Returns the last response returned from Graph.
	 * @return BatchResponse|Response|null
	 */
	public function getLastResponse(): ?Response
	{
		return $this->lastResponse;
	}


	/**
	 * Returns the URL detection handler.
	 */
	public function getUrlDetectionHandler(): ?UrlDetectionInterface
	{
		return $this->urlDetectionHandler;
	}


	/**
	 * Changes the URL detection handler.
	 */
	private function setUrlDetectionHandler(UrlDetectionInterface $urlDetection): void
	{
		$this->urlDetectionHandler = $urlDetection;
	}


	/**
	 * Returns the default AccessToken entity.
	 */
	public function getDefaultAccessToken(): ?AccessToken
	{
		return $this->defaultAccessToken;
	}


	/**
	 * Sets the default access token to use with requests.
	 *
	 * @param AccessToken|string $accessToken the access token to save
	 *
	 * @throws InvalidArgumentException
	 */
	public function setDefaultAccessToken(AccessToken|string $accessToken): void
	{
		$this->defaultAccessToken = is_string($accessToken)
			? new AccessToken($accessToken)
			: $accessToken;
	}


	/**
	 * Returns the default Graph version.
	 */
	public function getDefaultGraphVersion(): ?string
	{
		return $this->defaultGraphVersion;
	}


	/**
	 * Returns the redirect login helper.
	 */
	public function getRedirectLoginHelper(): RedirectLoginHelper
	{
		return new RedirectLoginHelper($this->getOAuth2Client(), $this->persistentDataHandler, $this->urlDetectionHandler);
	}


	/**
	 * Returns the JavaScript helper.
	 */
	public function getJavaScriptHelper(): JavaScriptHelper
	{
		return new JavaScriptHelper($this->app, $this->client, $this->defaultGraphVersion);
	}


	/**
	 * Returns the canvas helper.
	 */
	public function getCanvasHelper(): CanvasHelper
	{
		return new CanvasHelper($this->app, $this->client, $this->defaultGraphVersion);
	}


	/**
	 * Returns the page tab helper.
	 */
	public function getPageTabHelper(): PageTabHelper
	{
		return new PageTabHelper($this->app, $this->client, $this->defaultGraphVersion);
	}


	/**
	 * Sends a GET request to Graph and returns the result.
	 *
	 * @throws SDKException
	 */
	public function get(
		string $endpoint,
		AccessToken|string $accessToken = null,
		string $eTag = null,
		string $graphVersion = null,
	): Response
	{
		return $this->sendRequest('GET', $endpoint, [], $accessToken, $eTag, $graphVersion);
	}


	/**
	 * Sends a POST request to Graph and returns the result.
	 *
	 * @throws SDKException
	 */
	public function post(
		string $endpoint,
		array $params = [],
		AccessToken|string $accessToken = null,
		string $eTag = null,
		string $graphVersion = null,
	): Response
	{
		return $this->sendRequest('POST', $endpoint, $params, $accessToken, $eTag, $graphVersion);
	}


	/**
	 * Sends a DELETE request to Graph and returns the result.
	 *
	 * @throws SDKException
	 */
	public function delete(
		string $endpoint,
		array $params = [],
		AccessToken|string $accessToken = null,
		string $eTag = null,
		string $graphVersion = null,
	): Response
	{
		return $this->sendRequest('DELETE', $endpoint, $params, $accessToken, $eTag, $graphVersion);
	}


	/**
	 * Sends a request to Graph for the next page of results.
	 *
	 * @param GraphEdge $graphEdge the GraphEdge to paginate over
	 *
	 * @throws SDKException
	 */
	public function next(GraphEdge $graphEdge): ?GraphEdge
	{
		return $this->getPaginationResults($graphEdge, 'next');
	}


	/**
	 * Sends a request to Graph for the previous page of results.
	 *
	 * @param GraphEdge $graphEdge the GraphEdge to paginate over
	 *
	 * @throws SDKException
	 */
	public function previous(GraphEdge $graphEdge): ?GraphEdge
	{
		return $this->getPaginationResults($graphEdge, 'previous');
	}


	/**
	 * Sends a request to Graph for the next page of results.
	 *
	 * @param GraphEdge $graphEdge the GraphEdge to paginate over
	 * @param string    $direction the direction of the pagination: next|previous
	 *
	 * @throws SDKException
	 */
	public function getPaginationResults(GraphEdge $graphEdge, string $direction): ?GraphEdge
	{
		$paginationRequest = $graphEdge->getPaginationRequest($direction);
		if (!$paginationRequest instanceof Request) {
			return null;
		}

		$this->lastResponse = $this->client->sendRequest($paginationRequest);

		// Keep the same GraphNode subclass
		$subClassName = $graphEdge->getSubClassName();
		$graphEdge = $this->lastResponse->getGraphEdge($subClassName, false);

		return $graphEdge->asArray() !== [] ? $graphEdge : null;
	}


	/**
	 * Sends a request to Graph and returns the result.
	 *
	 * @throws SDKException
	 */
	public function sendRequest(
		string $method,
		string $endpoint,
		array $params = [],
		AccessToken|string $accessToken = null,
		string $eTag = null,
		string $graphVersion = null,
	): Response
	{
		$accessToken ??= $this->defaultAccessToken;
		$graphVersion ??= $this->defaultGraphVersion;
		$request = $this->request($method, $endpoint, $params, $accessToken, $eTag, $graphVersion);

		return $this->lastResponse = $this->client->sendRequest($request);
	}


	/**
	 * Sends a batched request to Graph and returns the result.
	 *
	 * @param AccessToken|string|null $accessToken
	 * @param string|null $graphVersion
	 *
	 * @throws SDKException
	 */
	public function sendBatchRequest(
		array $requests,
		AccessToken|string $accessToken = null,
		string $graphVersion = null,
	): BatchResponse
	{
		$accessToken ??= $this->defaultAccessToken;
		$graphVersion ??= $this->defaultGraphVersion;
		$batchRequest = new BatchRequest($this->app, $requests, $accessToken, $graphVersion);

		return $this->lastResponse = $this->client->sendBatchRequest($batchRequest);
	}


	/**
	 * Instantiates an empty BatchRequest entity.
	 *
	 * @param AccessToken|string|null $accessToken The top-level access token. Requests with no access token
	 * will fallback to this.
	 * @param string|null $graphVersion the Graph API version to use
	 */
	public function newBatchRequest(AccessToken|string $accessToken = null, string $graphVersion = null): BatchRequest
	{
		$accessToken ??= $this->defaultAccessToken;
		$graphVersion ??= $this->defaultGraphVersion;

		return new BatchRequest($this->app, [], $accessToken, $graphVersion);
	}


	/**
	 * Instantiates a new Request entity.
	 *
	 * @param AccessToken|string|null $accessToken
	 * @param string|null $eTag
	 * @param string|null $graphVersion
	 *
	 * @throws SDKException
	 */
	public function request(
		string $method,
		string $endpoint,
		array $params = [],
		AccessToken|string $accessToken = null,
		string $eTag = null,
		string $graphVersion = null,
	): Request
	{
		$accessToken ??= $this->defaultAccessToken;
		$graphVersion ??= $this->defaultGraphVersion;

		return new Request($this->app, $accessToken, $method, $endpoint, $params, $eTag, $graphVersion);
	}


	/**
	 * Factory to create File's.
	 *
	 * @throws SDKException
	 */
	public function fileToUpload(string $pathToFile): File
	{
		return new File($pathToFile);
	}


	/**
	 * Factory to create Video's.
	 *
	 * @throws SDKException
	 */
	public function videoToUpload(string $pathToFile): Video
	{
		return new Video($pathToFile);
	}


	/**
	 * Upload a video in chunks.
	 *
	 * @param int|string                $target             the id of the target node before the /videos edge
	 * @param string                    $pathToFile         the full path to the file
	 * @param array                     $metadata           the metadata associated with the video file
	 * @param string|AccessToken|null   $accessToken        the access token
	 * @param int                       $maxTransferTries   the max times to retry a failed upload chunk
	 * @param string|null               $graphVersion       the Graph API version to use
	 *
	 * @return array{video_id: int, success: bool}
	 * @throws SDKException|FilesystemException
	 */
	public function uploadVideo(
		int|string $target,
		string $pathToFile,
		array $metadata = [],
		string|AccessToken $accessToken = null,
		int $maxTransferTries = 5,
		string $graphVersion = null,
	): array
	{
		$accessToken ??= $this->defaultAccessToken;
		$graphVersion ??= $this->defaultGraphVersion;

		$resumableUploader = new ResumableUploader($this->app, $this->client, $accessToken, $graphVersion);
		$endpoint = '/' . $target . '/videos';
		$video = $this->videoToUpload($pathToFile);
		$chunk = $resumableUploader->start($endpoint, $video);

		do {
			$chunk = $this->maxTriesTransfer($resumableUploader, $endpoint, $chunk, $maxTransferTries);
		} while (!$chunk->isLastChunk());

		return [
			'video_id' => $chunk->getVideoId(),
			'success' => $resumableUploader->finish($endpoint, $chunk->getUploadSessionId(), $metadata),
		];
	}


	/**
	 * Attempts to upload a chunk of a file in $retryCountdown tries.
	 *
	 * @throws SDKException
	 */
	private function maxTriesTransfer(
		ResumableUploader $resumableUploader,
		string $endpoint,
		TransferChunk $transferChunk,
		int $retryCountdown,
	): TransferChunk
	{
		$newChunk = $resumableUploader->transfer($endpoint, $transferChunk, $retryCountdown < 1);

		if ($newChunk !== $transferChunk) {
			return $newChunk;
		}

		$retryCountdown--;

		// If transfer() returned the same chunk entity, the transfer failed but is resumable.
		return $this->maxTriesTransfer($resumableUploader, $endpoint, $transferChunk, $retryCountdown);
	}
}
