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

namespace JanuSoftware\Facebook;

use ArrayAccess;
use ArrayIterator;
use IteratorAggregate;

class BatchResponse extends Response implements IteratorAggregate, ArrayAccess
{
	/** @var Response[] */
	protected array $responses = [];


	/**
	 * Creates a new Response entity.
	 */
	public function __construct(
		protected BatchRequest $batchRequest,
		Response $response,
	) {
		$request = $response->getRequest();
		$body = $response->getBody();
		$httpStatusCode = $response->getHttpStatusCode();
		$headers = $response->getHeaders();
		parent::__construct($request, $body, $httpStatusCode, $headers);

		$responses = $response->getDecodedBody();
		$this->setResponses($responses);
	}


	/**
	 * Returns an array of Response entities.
	 *
	 * @return Response[]
	 */
	public function getResponses(): array
	{
		return $this->responses;
	}


	/**
	 * The main batch response will be an array of requests so
	 * we need to iterate over all the responses.
	 */
	public function setResponses(array $responses): void
	{
		$this->responses = [];

		foreach ($responses as $key => $graphResponse) {
			$this->addResponse($key, $graphResponse);
		}
	}


	/**
	 * Add a response to the list.
	 */
	public function addResponse(int $key, ?array $response): void
	{
		$originalRequestName = $this->batchRequest[$key]['name'] ?? $key;
		$originalRequest = $this->batchRequest[$key]['request'] ?? null;

		$httpResponseBody = $response['body'] ?? null;
		$httpResponseCode = $response['code'] ?? null;
		$httpResponseHeaders = isset($response['headers']) ? $this->normalizeBatchHeaders($response['headers']) : [];

		$this->responses[$originalRequestName] = new Response(
			$originalRequest,
			$httpResponseBody,
			$httpResponseCode !== null ? (int) $httpResponseCode : null,
			$httpResponseHeaders,
		);
	}


	public function getIterator(): ArrayIterator
	{
		return new ArrayIterator($this->responses);
	}


	/**
	 * {@inheritdoc}
	 */
	public function offsetSet($offset, $value): void
	{
		$this->addResponse($offset, $value);
	}


	/**
	 * {@inheritdoc}
	 */
	public function offsetExists($offset): bool
	{
		return isset($this->responses[$offset]);
	}


	/**
	 * {@inheritdoc}
	 */
	public function offsetUnset($offset): void
	{
		unset($this->responses[$offset]);
	}


	/**
	 * {@inheritdoc}
	 */
	public function offsetGet($offset): ?Response
	{
		return $this->responses[$offset] ?? null;
	}


	/**
	 * Converts the batch header array into a standard format.
	 *
	 * @TODO replace with array_column() when PHP 5.5 is supported.
	 * @return array<int|string, mixed>
	 */
	private function normalizeBatchHeaders(array $batchHeaders): array
	{
		$headers = [];

		foreach ($batchHeaders as $batchHeader) {
			$headers[$batchHeader['name']] = $batchHeader['value'];
		}

		return $headers;
	}
}
