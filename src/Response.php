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

use JanuSoftware\Facebook\Exception\ResponseException;
use JanuSoftware\Facebook\Exception\SDKException;
use JanuSoftware\Facebook\GraphNode\GraphEdge;
use JanuSoftware\Facebook\GraphNode\GraphNode;
use JanuSoftware\Facebook\GraphNode\GraphNodeFactory;
use Safe\Exceptions\JsonException;
use function Safe\json_decode;


class Response
{
	protected array $decodedBody = [];
	protected ?SDKException $thrownException = null;


	/**
	 * Creates a new Response entity.
	 */
	public function __construct(
		protected Request $request,
		protected ?string $body = null,
		protected ?int $httpStatusCode = null,
		protected array $headers = [],
	) {
		$this->decodeBody();
	}


	/**
	 * Return the original request that returned this response.
	 */
	public function getRequest(): Request
	{
		return $this->request;
	}


	/**
	 * Return the Application entity used for this response.
	 */
	public function getApplication(): ?Application
	{
		return $this->request->getApplication();
	}


	/**
	 * Return the access token that was used for this response.
	 */
	public function getAccessToken(): ?string
	{
		return $this->request->getAccessToken();
	}


	/**
	 * Return the HTTP status code for this response.
	 */
	public function getHttpStatusCode(): ?int
	{
		return $this->httpStatusCode;
	}


	/**
	 * Return the HTTP headers for this response.
	 */
	public function getHeaders(): array
	{
		return $this->headers;
	}


	/**
	 * Return the raw body response.
	 */
	public function getBody(): ?string
	{
		return $this->body;
	}


	/**
	 * Return the decoded body response.
	 */
	public function getDecodedBody(): array
	{
		return $this->decodedBody;
	}


	/**
	 * Get the app secret proof that was used for this response.
	 */
	public function getAppSecretProof(): ?string
	{
		return $this->request->getAppSecretProof();
	}


	/**
	 * Get the ETag associated with the response.
	 */
	public function getETag(): ?string
	{
		return $this->headers['ETag'] ?? null;
	}


	/**
	 * Get the version of Graph that returned this response.
	 */
	public function getGraphVersion(): ?string
	{
		return $this->headers['Facebook-API-Version'] ?? null;
	}


	/**
	 * Returns true if Graph returned an error message.
	 */
	public function isError(): bool
	{
		return isset($this->decodedBody['error']);
	}


	/**
	 * Throws the exception.
	 * @throws SDKException
	 */
	public function throwException(): void
	{
		throw $this->thrownException;
	}


	/**
	 * Instantiates an exception to be thrown later.
	 */
	public function makeException(): void
	{
		$this->thrownException = ResponseException::create($this);
	}


	/**
	 * Returns the exception that was thrown for this request.
	 */
	public function getThrownException(): ?SDKException
	{
		return $this->thrownException;
	}


	/**
	 * Convert the raw response into an array if possible.
	 * Graph will return 2 types of responses:
	 * - JSON(P)
	 *    Most responses from Graph are JSON(P)
	 * - application/x-www-form-urlencoded key/value pairs
	 *    Happens on the `/oauth/access_token` endpoint when exchanging
	 *    a short-lived access token for a long-lived access token
	 * - And sometimes nothing :/ but that'd be a bug.
	 */
	public function decodeBody(): void
	{
		if ($this->body === null) {
			$this->decodedBody = [];
		} else {
			try {
				$decodedBody = json_decode($this->body, true);

				$this->decodedBody = is_bool($decodedBody)
					? ['success' => $decodedBody]
					: $decodedBody;

			} catch (JsonException) {
				$this->decodedBody = [];
				parse_str($this->body, $this->decodedBody);

				if (is_numeric($this->body)) {
					$this->decodedBody = ['id' => $this->decodedBody];
				}
			}
		}

		if ($this->isError()) {
			$this->makeException();
		}
	}


	/**
	 * Instantiate a new GraphNode from response.
	 *
	 * @param string|null $subclassName the GraphNode subclass to cast to
	 *
	 * @throws SDKException
	 */
	public function getGraphNode(string $subclassName = null): GraphNode
	{
		$graphNodeFactory = new GraphNodeFactory($this);

		return $graphNodeFactory->makeGraphNode($subclassName);
	}


	/**
	 * Instantiate a new GraphEdge from response.
	 *
	 * @param string|null $subclassName the GraphNode subclass to cast list items to
	 * @param bool        $auto_prefix  toggle to auto-prefix the subclass name
	 *
	 * @throws SDKException
	 */
	public function getGraphEdge(string $subclassName = null, bool $auto_prefix = true): GraphEdge
	{
		$graphNodeFactory = new GraphNodeFactory($this);

		return $graphNodeFactory->makeGraphEdge($subclassName, $auto_prefix);
	}
}
