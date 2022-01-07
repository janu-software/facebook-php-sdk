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

use JanuSoftware\Facebook\Authentication\AccessToken;
use JanuSoftware\Facebook\Authentication\OAuth2Client;
use JanuSoftware\Facebook\Exception\SDKException;
use JanuSoftware\Facebook\PersistentData\PersistentDataInterface;
use JanuSoftware\Facebook\PersistentData\SessionPersistentDataHandler;
use JanuSoftware\Facebook\Url\UrlDetectionHandler;
use JanuSoftware\Facebook\Url\UrlDetectionInterface;
use JanuSoftware\Facebook\Url\UrlManipulator;


class RedirectLoginHelper
{
	/**
	 * @const int The length of CSRF string to validate the login link.
	 */
	public const CSRF_LENGTH = 32;

	protected UrlDetectionInterface $urlDetectionHandler;
	protected PersistentDataInterface $persistentDataHandler;


	/**
	 * @param OAuth2Client                 $oAuth2Client          The OAuth 2.0 client service.
	 * @param PersistentDataInterface|null $persistentData the persistent data handler
	 * @param UrlDetectionInterface|null   $urlDetection the URL detection handler
	 */
	public function __construct(
		protected OAuth2Client $oAuth2Client,
		PersistentDataInterface $persistentData = null,
		UrlDetectionInterface $urlDetection = null,
	) {
		$this->persistentDataHandler = $persistentData ?? new SessionPersistentDataHandler;
		$this->urlDetectionHandler = $urlDetection ?? new UrlDetectionHandler;
	}


	/**
	 * Returns the persistent data handler.
	 */
	public function getPersistentDataHandler(): PersistentDataInterface
	{
		return $this->persistentDataHandler;
	}


	/**
	 * Returns the URL detection handler.
	 */
	public function getUrlDetectionHandler(): UrlDetectionInterface
	{
		return $this->urlDetectionHandler;
	}


	/**
	 * Stores CSRF state and returns a URL to which the user should be sent to in order to continue the login process with Facebook.
	 *
	 * @param string $redirectUrl the URL Facebook should redirect users to after login
	 * @param array  $scope       list of permissions to request during login
	 * @param array  $params      an array of parameters to generate URL
	 * @param string $separator   the separator to use in http_build_query()
	 */
	private function makeUrl(string $redirectUrl, array $scope, array $params = [], string $separator = '&'): string
	{
		$state = $this->persistentDataHandler->get('state') ?? $this->getPseudoRandomString();
		$this->persistentDataHandler->set('state', $state);

		return $this->oAuth2Client->getAuthorizationUrl($redirectUrl, $state, $scope, $params, $separator);
	}


	private function getPseudoRandomString(): string
	{
		return bin2hex(random_bytes(static::CSRF_LENGTH));
	}


	/**
	 * Returns the URL to send the user in order to login to Facebook.
	 *
	 * @param string $redirectUrl the URL Facebook should redirect users to after login
	 * @param array  $scope       list of permissions to request during login
	 * @param string $separator   the separator to use in http_build_query()
	 */
	public function getLoginUrl(string $redirectUrl, array $scope = [], string $separator = '&'): string
	{
		return $this->makeUrl($redirectUrl, $scope, [], $separator);
	}


	/**
	 * Returns the URL to send the user in order to log out of Facebook.
	 *
	 * @param AccessToken|string $accessToken the access token that will be logged out
	 * @param string             $next        the url Facebook should redirect the user to after a successful logout
	 * @param string             $separator   the separator to use in http_build_query()
	 *
	 * @throws SDKException
	 */
	public function getLogoutUrl(AccessToken|string $accessToken, string $next, string $separator = '&'): string
	{
		if (!$accessToken instanceof AccessToken) {
			$accessToken = new AccessToken($accessToken);
		}

		if ($accessToken->isAppAccessToken()) {
			throw new SDKException('Cannot generate a logout URL with an app access token.', 722);
		}

		$params = [
			'next' => $next,
			'access_token' => $accessToken->getValue(),
		];

		return 'https://www.facebook.com/logout.php?' . http_build_query($params, '', $separator);
	}


	/**
	 * Returns the URL to send the user in order to login to Facebook with permission(s) to be re-asked.
	 *
	 * @param string $redirectUrl the URL Facebook should redirect users to after login
	 * @param array  $scope       list of permissions to request during login
	 * @param string $separator   the separator to use in http_build_query()
	 */
	public function getReRequestUrl(string $redirectUrl, array $scope = [], string $separator = '&'): string
	{
		$params = ['auth_type' => 'rerequest'];

		return $this->makeUrl($redirectUrl, $scope, $params, $separator);
	}


	/**
	 * Returns the URL to send the user in order to login to Facebook with user to be re-authenticated.
	 *
	 * @param string $redirectUrl the URL Facebook should redirect users to after login
	 * @param array  $scope       list of permissions to request during login
	 * @param string $separator   the separator to use in http_build_query()
	 */
	public function getReAuthenticationUrl(string $redirectUrl, array $scope = [], string $separator = '&'): string
	{
		$params = ['auth_type' => 'reauthenticate'];

		return $this->makeUrl($redirectUrl, $scope, $params, $separator);
	}


	/**
	 * Takes a valid code from a login redirect, and returns an AccessToken entity.
	 *
	 * @param string|null $redirectUrl the redirect URL
	 *
	 * @throws SDKException
	 */
	public function getAccessToken(string $redirectUrl = null): ?AccessToken
	{
		$code = $this->getCode();
		if ($code === null) {
			return null;
		}

		$this->validateCsrf();
		$this->resetCsrf();

		$redirectUrl ??= $this->urlDetectionHandler->getCurrentUrl();
		// At minimum we need to remove the state param
		$redirectUrl = UrlManipulator::removeParamsFromUrl($redirectUrl, ['state']);

		return $this->oAuth2Client->getAccessTokenFromCode($code, $redirectUrl);
	}


	/**
	 * Validate the request against a cross-site request forgery.
	 * @throws SDKException
	 */
	protected function validateCsrf(): void
	{
		$state = $this->getState();
		if ($state === null) {
			throw new SDKException('Cross-site request forgery validation failed. Required GET param "state" missing.');
		}
		$savedState = $this->persistentDataHandler->get('state');
		if ($savedState === null) {
			throw new SDKException('Cross-site request forgery validation failed. Required param "state" missing from persistent data.');
		}

		if (\hash_equals($savedState, $state)) {
			return;
		}

		throw new SDKException('Cross-site request forgery validation failed. The "state" param from the URL and session do not match.');
	}


	/**
	 * Resets the CSRF so that it doesn't get reused.
	 */
	private function resetCsrf(): void
	{
		$this->persistentDataHandler->set('state', null);
	}


	/**
	 * Return the code.
	 */
	protected function getCode(): ?string
	{
		return $this->getInput('code');
	}


	/**
	 * Return the state.
	 */
	protected function getState(): ?string
	{
		return $this->getInput('state');
	}


	/**
	 * Return the error code.
	 */
	public function getErrorCode(): ?string
	{
		return $this->getInput('error_code');
	}


	/**
	 * Returns the error.
	 */
	public function getError(): ?string
	{
		return $this->getInput('error');
	}


	/**
	 * Returns the error reason.
	 */
	public function getErrorReason(): ?string
	{
		return $this->getInput('error_reason');
	}


	/**
	 * Returns the error description.
	 */
	public function getErrorDescription(): ?string
	{
		return $this->getInput('error_description');
	}


	/**
	 * Returns a value from a GET param.
	 */
	private function getInput(string $key): ?string
	{
		return $_GET[$key] ?? null;
	}
}
