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

namespace JanuSoftware\Facebook\Exception;

use JanuSoftware\Facebook\Response;


class ResponseException extends SDKException
{
	protected array $responseData;


	public function __construct(
		protected Response $response,
		?SDKException $sdkException = null,
	) {
		$this->responseData = $response->getDecodedBody();

		$errorMessage = $this->get('message', 'Unknown error from Graph.');
		$errorCode = $this->get('code', -1);

		parent::__construct($errorMessage, $errorCode, $sdkException);
	}


	/**
	 * A factory for creating the appropriate exception based on the response from Graph.
	 */
	public static function create(Response $response): self
	{
		$data = $response->getDecodedBody();

		if (!isset($data['error']['code']) && isset($data['code'])) {
			$data = ['error' => $data];
		}

		$code = $data['error']['code'] ?? null;
		$message = $data['error']['message'] ?? 'Unknown error from Graph.';

		if (isset($data['error']['error_subcode'])) {
			$match = match ($data['error']['error_subcode']) {
				458, 459, 460, 463, 464, 467 => new self($response, new AuthenticationException($message, $code)),
				1_363_030, 1_363_019, 1_363_037, 1_363_033, 1_363_021, 1_363_041 => new self($response, new ResumableUploadException($message, $code)),
				default => null,
			};
			if ($match !== null) {
				return $match;
			}
		}

		$match = match ($code) {
			100, 102, 190 => new self($response, new AuthenticationException($message, $code)),
			1, 2 => new self($response, new ServerException($message, $code)),
			4, 17, 32, 341, 613 => new self($response, new ThrottleException($message, $code)),
			506 => new self($response, new ClientException($message, $code)),
			default => null,
		};
		if ($match !== null) {
			return $match;
		}

		// Missing Permissions
		if ($code === 10 || ($code >= 200 && $code <= 299)) {
			return new self($response, new AuthorizationException($message, $code));
		}

		// OAuth authentication error
		if (isset($data['error']['type']) && $data['error']['type'] === 'OAuthException') {
			return new self($response, new AuthenticationException($message, $code));
		}

		// All others
		return new self($response, new OtherException($message, $code));
	}


	private function get(string $key, mixed $default = null): mixed
	{
		return $this->responseData['error'][$key] ?? $default;
	}


	/**
	 * Returns the HTTP status code.
	 */
	public function getHttpStatusCode(): ?int
	{
		return $this->response->getHttpStatusCode();
	}


	/**
	 * Returns the sub-error code.
	 */
	public function getSubErrorCode(): int
	{
		return $this->get('error_subcode', -1);
	}


	/**
	 * Returns the error type.
	 */
	public function getErrorType(): string
	{
		return $this->get('type', '');
	}


	/**
	 * Returns the raw response used to create the exception.
	 */
	public function getRawResponse(): ?string
	{
		return $this->response->getBody();
	}


	/**
	 * Returns the decoded response used to create the exception.
	 * @return mixed[]
	 */
	public function getResponseData(): array
	{
		return $this->responseData;
	}


	/**
	 * Returns the response entity used to create the exception.
	 */
	public function getResponse(): Response
	{
		return $this->response;
	}
}
