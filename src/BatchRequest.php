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

use ArrayAccess;
use ArrayIterator;
use InvalidArgumentException;
use IteratorAggregate;
use JanuSoftware\Facebook\Authentication\AccessToken;
use JanuSoftware\Facebook\Exception\SDKException;
use JanuSoftware\Facebook\FileUpload\File;
use Safe\Exceptions\JsonException;
use function Safe\json_encode;


class BatchRequest extends Request implements IteratorAggregate, ArrayAccess
{
	protected array $requests = [];

	/** @var File[] */
	protected array $attachedFiles = [];


	/**
	 * Creates a new Request entity.
	 *
	 * @param Request[] $requests
	 */
	public function __construct(
		?Application $application = null,
		array $requests = [],
		AccessToken|string|null $accessToken = null,
		?string $graphVersion = null,
	) {
		parent::__construct($application, $accessToken, 'POST', '', [], null, $graphVersion);

		$this->add($requests);
	}


	/**
	 * Adds a new request to the array.
	 *
	 * @param Request[]|Request $request
	 * @param array|string|null $options Array of batch request options e.g. 'name', 'omit_response_on_success'.
	 * If a string is given, it is the value of the 'name' option.
	 *
	 * @throws InvalidArgumentException|SDKException
	 */
	public function add(array|Request $request, null|array|string $options = null): static
	{
		if (is_array($request)) {
			foreach ($request as $key => $req) {
				$this->add($req, is_int($key) ? (string) $key : $key);
			}

			return $this;
		}

		if ($options === null) {
			$options = [];
		} elseif (!is_array($options)) {
			$options = ['name' => $options];
		}

		$this->addFallbackDefaults($request);

		// File uploads
		$attachedFiles = $this->extractFileAttachments($request);

		$name = $options['name'] ?? null;

		unset($options['name']);

		$requestToAdd = [
			'name' => $name,
			'request' => $request,
			'options' => $options,
			'attached_files' => $attachedFiles,
		];

		$this->requests[] = $requestToAdd;

		return $this;
	}


	/**
	 * Ensures that the Application and access token fall back when missing.
	 * @throws SDKException
	 */
	public function addFallbackDefaults(Request $request): void
	{
		if ($request->getApplication() === null) {
			$application = $this->getApplication();
			if (!$application instanceof Application) {
				throw new SDKException('Missing Application on Request and no fallback detected on BatchRequest.');
			}
			$request->setApp($application);
		}

		if ($request->getAccessToken() === null) {
			$accessToken = $this->getAccessToken();
			if ($accessToken === null) {
				throw new SDKException('Missing access token on Request and no fallback detected on BatchRequest.');
			}
			$request->setAccessToken($accessToken);
		}
	}


	/**
	 * Extracts the files from a request.
	 * @throws SDKException
	 */
	public function extractFileAttachments(Request $request): ?string
	{
		if (!$request->containsFileUploads()) {
			return null;
		}

		$files = $request->getFiles();
		$fileNames = [];
		foreach ($files as $file) {
			$fileName = uniqid();
			$this->addFile($fileName, $file);
			$fileNames[] = $fileName;
		}

		$request->resetFiles();

		// @TODO Does Graph support multiple uploads on one endpoint?
		return implode(',', $fileNames);
	}


	/**
	 * Return the Request entities.
	 * @return mixed[]
	 */
	public function getRequests(): array
	{
		return $this->requests;
	}


	/**
	 * Prepares the requests to be sent as a batch request.
	 */
	public function prepareRequestsForBatch(): void
	{
		$this->validateBatchRequestCount();

		$params = [
			'batch' => $this->convertRequestsToJson(),
			'include_headers' => true,
		];
		$this->setParams($params);
	}


	/**
	 * Converts the requests into a JSON(P) string.
	 * @throws JsonException
	 */
	public function convertRequestsToJson(): string
	{
		$requests = [];
		foreach ($this->requests as $request) {
			$options = [];

			if ($request['name'] !== null) {
				$options['name'] = $request['name'];
			}

			$options += $request['options'];

			$requests[] = $this->requestEntityToBatchArray($request['request'], $options, $request['attached_files']);
		}

		return json_encode($requests);
	}


	/**
	 * Validate the request count before sending them as a batch.
	 * @throws SDKException
	 */
	public function validateBatchRequestCount(): void
	{
		$batchCount = count($this->requests);
		if ($batchCount === 0) {
			throw new SDKException('There are no batch requests to send.');
		} elseif ($batchCount > 50) {
			// Per: https://developers.facebook.com/docs/graph-api/making-multiple-requests#limits
			throw new SDKException('You cannot send more than 50 batch requests at a time.');
		}
	}


	/**
	 * Converts a Request entity into an array that is batch-friendly.
	 *
	 * @param Request             $request       the request entity to convert
	 * @param string|mixed[]|null $options       Array of batch request options e.g. 'name', 'omit_response_on_success'. If a string is given, it is the value of the 'name' option.
	 * @param string|null         $attachedFiles names of files associated with the request
	 *
	 * @return mixed[]
	 */
	public function requestEntityToBatchArray(
		Request $request,
		array|string|null $options = null,
		?string $attachedFiles = null,
	): array
	{
		if ($options === null) {
			$options = [];
		} elseif (!is_array($options)) {
			$options = ['name' => $options];
		}

		$compiledHeaders = [];
		$headers = $request->getHeaders();
		foreach ($headers as $name => $value) {
			$compiledHeaders[] = $name . ': ' . $value;
		}

		$batch = [
			'headers' => $compiledHeaders,
			'method' => $request->getMethod(),
			'relative_url' => $request->getUrl(),
		];

		// Since file uploads are moved to the root request of a batch request,
		// the child requests will always be URL-encoded.
		$stream = $request->getUrlEncodedBody()
			->getBody();
		if ($stream !== null) {
			$batch['body'] = $stream->getContents();
		}

		$batch += $options;

		if ($attachedFiles !== null) {
			$batch['attached_files'] = $attachedFiles;
		}

		return $batch;
	}


	/**
	 * Get an iterator for the items.
	 */
	public function getIterator(): ArrayIterator
	{
		return new ArrayIterator($this->requests);
	}


	/**
	 * {@inheritdoc}
	 */
	public function offsetSet($offset, $value): void
	{
		$this->add($value, $offset);
	}


	/**
	 * {@inheritdoc}
	 */
	public function offsetExists($offset): bool
	{
		return isset($this->requests[$offset]);
	}


	/**
	 * {@inheritdoc}
	 */
	public function offsetUnset($offset): void
	{
		unset($this->requests[$offset]);
	}


	/**
	 * {@inheritdoc}
	 */
	public function offsetGet($offset): mixed
	{
		return $this->requests[$offset] ?? null;
	}
}
