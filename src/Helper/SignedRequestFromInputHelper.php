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

namespace JanuSoftware\Facebook\Helper;

use JanuSoftware\Facebook\Application;
use JanuSoftware\Facebook\Authentication\AccessToken;
use JanuSoftware\Facebook\Authentication\OAuth2Client;
use JanuSoftware\Facebook\Client;
use JanuSoftware\Facebook\Exception\SDKException;
use JanuSoftware\Facebook\SignedRequest;


abstract class SignedRequestFromInputHelper
{
	protected ?SignedRequest $signedRequest = null;
	protected OAuth2Client $oAuth2Client;


	/**
	 * Initialize the helper and process available signed request data.
	 *
	 * @param Application $application the Application entity
	 * @param Client      $client       the client to make HTTP requests
	 * @param string      $graphVersion the version of Graph to use
	 */
	public function __construct(
		protected Application $application,
		Client $client,
		string $graphVersion,
	) {
		$this->oAuth2Client = new OAuth2Client($this->application, $client, $graphVersion);

		$this->instantiateSignedRequest();
	}


	/**
	 * Instantiates a new SignedRequest entity.
	 */
	public function instantiateSignedRequest(?string $rawSignedRequest = null): void
	{
		$rawSignedRequest ??= $this->getRawSignedRequest();

		if ($rawSignedRequest === null) {
			return;
		}

		$this->signedRequest = new SignedRequest($this->application, $rawSignedRequest);
	}


	/**
	 * Returns an AccessToken entity from the signed request.
	 * @throws SDKException
	 */
	public function getAccessToken(): ?AccessToken
	{
		if ($this->signedRequest !== null && $this->signedRequest->hasOAuthData()) {
			$code = $this->signedRequest->get('code');
			$accessToken = $this->signedRequest->get('oauth_token');

			if ($code !== null && $accessToken === null) {
				return $this->oAuth2Client->getAccessTokenFromCode($code);
			}

			$expiresAt = $this->signedRequest->get('expires', 0);

			return new AccessToken($accessToken, $expiresAt);
		}

		return null;
	}


	/**
	 * Returns the SignedRequest entity.
	 */
	public function getSignedRequest(): ?SignedRequest
	{
		return $this->signedRequest;
	}


	/**
	 * Returns the user_id if available.
	 */
	public function getUserId(): ?int
	{
		return $this->signedRequest?->getUserId();
	}


	/**
	 * Get raw signed request from input.
	 */
	abstract public function getRawSignedRequest(): ?string;


	/**
	 * Get raw signed request from POST input.
	 */
	public function getRawSignedRequestFromPost(): ?string
	{
		return $_POST['signed_request'] ?? null;
	}


	/**
	 * Get raw signed request from cookie set from the Javascript SDK.
	 */
	public function getRawSignedRequestFromCookie(): ?string
	{
		return $_COOKIE['fbsr_' . $this->application->getId()] ?? null;
	}
}
