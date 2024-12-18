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

use JanuSoftware\Facebook\Authentication\AccessToken;
use JanuSoftware\Facebook\Exception\SDKException;
use JanuSoftware\Facebook\FileUpload\File;
use JanuSoftware\Facebook\FileUpload\Video;
use JanuSoftware\Facebook\Http\RequestBodyMultipart;
use JanuSoftware\Facebook\Http\RequestBodyUrlEncoded;
use JanuSoftware\Facebook\Url\UrlManipulator;
use JetBrains\PhpStorm\ArrayShape;


/**
 * Class Request.
 */
class Request
{
	/** The Facebook app entity */
	protected ?Application $app;

	/** The access token to use for this request */
	protected ?string $accessToken;

	/** The HTTP method for this request */
	protected ?string $method = null;

	/** The Graph endpoint for this request */
	protected ?string $endpoint;

	/** The headers to send with this request */
	protected array $headers = [];

	/** The parameters to send with this request */
	protected array $params = [];

	/** The files to send with this request */
	protected array $files = [];

	/** ETag to send with this request */
	protected ?string $eTag;


	/**
	 * Creates a new Request entity.
	 *
	 * @throws SDKException
	 */
	public function __construct(
		?Application $application = null,
		AccessToken|string|null $accessToken = null,
		?string $method = null,
		?string $endpoint = null,
		array $params = [],
		?string $eTag = null,
		protected ?string $graphVersion = null,
	) {
		$this->setApp($application);
		$this->setAccessToken($accessToken);
		$this->setMethod($method);
		$this->setEndpoint($endpoint);
		$this->setParams($params);
		$this->setETag($eTag);
	}


	/**
	 * Set the access token for this request.
	 */
	public function setAccessToken(AccessToken|string|null $accessToken): self
	{
		$this->accessToken = $accessToken instanceof AccessToken
			? $accessToken->getValue()
			: $accessToken;

		return $this;
	}


	/**
	 * Sets the access token with one harvested from a URL or POST params.
	 */
	public function setAccessTokenFromParams(string $accessToken): self
	{
		$existingAccessToken = $this->getAccessToken();
		if ($existingAccessToken === null) {
			$this->setAccessToken($accessToken);
		} elseif ($accessToken !== $existingAccessToken) {
			throw new SDKException('Access token mismatch. The access token provided in the Request and the one provided in the URL or POST params do not match.');
		}

		return $this;
	}


	/**
	 * Return the access token for this request.
	 */
	public function getAccessToken(): ?string
	{
		return $this->accessToken;
	}


	/**
	 * Return the access token for this request as an AccessToken entity.
	 */
	public function getAccessTokenEntity(): ?AccessToken
	{
		return $this->accessToken !== null ? new AccessToken($this->accessToken) : null;
	}


	/**
	 * Set the Application entity used for this request.
	 */
	public function setApp(?Application $application = null): void
	{
		$this->app = $application;
	}


	/**
	 * Return the Application entity used for this request.
	 */
	public function getApplication(): ?Application
	{
		return $this->app;
	}


	/**
	 * Generate an app secret proof to sign this request.
	 */
	public function getAppSecretProof(): ?string
	{
		$accessTokenEntity = $this->getAccessTokenEntity();
		if (!$accessTokenEntity instanceof AccessToken) {
			return null;
		}

		return $accessTokenEntity->getAppSecretProof((string) $this->app?->getSecret());
	}


	/**
	 * Validate that an access token exists for this request.
	 * @throws SDKException
	 */
	public function validateAccessToken(): void
	{
		$accessToken = $this->getAccessToken();
		if ($accessToken === null) {
			throw new SDKException('You must provide an access token.');
		}
	}


	/**
	 * Set the HTTP method for this request.
	 */
	public function setMethod(?string $method): void
	{
		if ($method !== null) {
			$this->method = strtoupper($method);
		}
	}


	/**
	 * Return the HTTP method for this request.
	 */
	public function getMethod(): ?string
	{
		return $this->method;
	}


	/**
	 * Validate that the HTTP method is set.
	 * @throws SDKException
	 */
	public function validateMethod(): void
	{
		if ($this->method === null || $this->method === '' || $this->method === '0') {
			throw new SDKException('HTTP method not specified.');
		}

		if (!in_array($this->method, ['GET', 'POST', 'DELETE'], true)) {
			throw new SDKException('Invalid HTTP method specified.');
		}
	}


	/**
	 * Set the endpoint for this request.
	 * @throws SDKException
	 */
	public function setEndpoint(?string $endpoint): self
	{
		if ($endpoint === null) {
			return $this;
		}

		// Harvest the access token from the endpoint to keep things in sync
		$params = UrlManipulator::getParamsAsArray($endpoint);
		if (isset($params['access_token'])) {
			$this->setAccessTokenFromParams($params['access_token']);
		}

		// Clean the token & app secret proof from the endpoint.
		$filterParams = ['access_token', 'appsecret_proof'];
		$this->endpoint = UrlManipulator::removeParamsFromUrl($endpoint, $filterParams);

		return $this;
	}


	/**
	 * Return the endpoint for this request.
	 */
	public function getEndpoint(): ?string
	{
		// For batch requests, this will be empty
		return $this->endpoint;
	}


	/**
	 * Generate and return the headers for this request.
	 */
	public function getHeaders(): array
	{
		$headers = static::getDefaultHeaders();

		if ($this->eTag !== null && $this->eTag !== '' && $this->eTag !== '0') {
			$headers['If-None-Match'] = $this->eTag;
		}

		return array_merge($this->headers, $headers);
	}


	/**
	 * Set the headers for this request.
	 */
	public function setHeaders(array $headers): void
	{
		$this->headers = array_merge($this->headers, $headers);
	}


	/**
	 * Sets the eTag value.
	 */
	public function setETag(?string $eTag): void
	{
		$this->eTag = $eTag;
	}


	/**
	 * Set the params for this request.
	 * @throws SDKException
	 */
	public function setParams(array $params = []): self
	{
		if (isset($params['access_token'])) {
			$this->setAccessTokenFromParams($params['access_token']);
		}

		// Don't let these buggers slip in.
		unset($params['access_token'], $params['appsecret_proof']);

		$params = $this->sanitizeFileParams($params);
		$this->dangerouslySetParams($params);

		return $this;
	}


	/**
	 * Set the params for this request without filtering them first.
	 */
	public function dangerouslySetParams(array $params = []): self
	{
		$this->params = array_merge($this->params, $params);

		return $this;
	}


	/**
	 * Iterate over the params and pull out the file uploads.
	 */
	public function sanitizeFileParams(array $params): array
	{
		foreach ($params as $key => $value) {
			if ($value instanceof File) {
				$this->addFile($key, $value);
				unset($params[$key]);
			}
		}

		return $params;
	}


	/**
	 * Add a file to be uploaded.
	 */
	public function addFile(string $key, File $file): void
	{
		$this->files[$key] = $file;
	}


	/**
	 * Removes all the files from the upload queue.
	 */
	public function resetFiles(): void
	{
		$this->files = [];
	}


	/**
	 * Get the list of files to be uploaded.
	 */
	public function getFiles(): array
	{
		return $this->files;
	}


	/**
	 * Let's us know if there is a file upload with this request.
	 */
	public function containsFileUploads(): bool
	{
		return $this->files !== [];
	}


	/**
	 * Let's us know if there is a video upload with this request.
	 */
	public function containsVideoUploads(): bool
	{
		foreach ($this->files as $file) {
			if ($file instanceof Video) {
				return true;
			}
		}

		return false;
	}


	/**
	 * Returns the body of the request as multipart/form-data.
	 */
	public function getMultipartBody(): RequestBodyMultipart
	{
		$params = $this->getPostParams();

		return new RequestBodyMultipart($params, $this->files);
	}


	/**
	 * Returns the body of the request as URL-encoded.
	 */
	public function getUrlEncodedBody(): RequestBodyUrlEncoded
	{
		$params = $this->getPostParams();

		return new RequestBodyUrlEncoded($params);
	}


	/**
	 * Generate and return the params for this request.
	 */
	public function getParams(): array
	{
		$params = $this->params;

		$accessToken = $this->getAccessToken();
		if ($accessToken !== null) {
			$params['access_token'] = $accessToken;
			$params['appsecret_proof'] = $this->getAppSecretProof();
		}

		return $params;
	}


	/**
	 * Only return params on POST requests.
	 */
	public function getPostParams(): array
	{
		if ($this->getMethod() === 'POST') {
			return $this->getParams();
		}

		return [];
	}


	/**
	 * The graph version used for this request.
	 */
	public function getGraphVersion(): ?string
	{
		return $this->graphVersion;
	}


	/**
	 * Generate and return the URL for this request.
	 */
	public function getUrl(): string
	{
		$this->validateMethod();

		$graphVersion = UrlManipulator::forceSlashPrefix($this->graphVersion);
		$endpoint = UrlManipulator::forceSlashPrefix($this->getEndpoint());

		$url = $graphVersion . $endpoint;

		if ($this->getMethod() !== 'POST') {
			$params = $this->getParams();
			$url = UrlManipulator::appendParamsToUrl($url, $params);
		}

		return $url;
	}


	/**
	 * Return the default headers that every request should use.
	 */
	#[ArrayShape(['User-Agent' => 'string', 'Accept-Encoding' => 'string'])]
	public static function getDefaultHeaders(): array
	{
		return [
			'User-Agent' => 'fb-php-' . Facebook::Version,
			'Accept-Encoding' => '*',
		];
	}
}
