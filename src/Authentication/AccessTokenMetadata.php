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

namespace JanuSoftware\Facebook\Authentication;

use JanuSoftware\Facebook\Exception\SDKException;
use Safe\DateTime;

/**
 * Represents metadata from an access token.
 *
 * @see     https://developers.facebook.com/docs/graph-api/reference/debug_token
 */
class AccessTokenMetadata
{
	/**
	 * The access token metadata.
	 */
	protected array $metadata = [];

	/**
	 * Properties that should be cast as DateTime objects.
	 */
	protected static array $dateProperties = ['expires_at', 'issued_at'];


	/**
	 * @throws SDKException
	 */
	public function __construct(array $metadata)
	{
		if (!isset($metadata['data'])) {
			throw new SDKException('Unexpected debug token response data.', 401);
		}

		$this->metadata = $metadata['data'];

		$this->castTimestampsToDateTime();
	}


	/**
	 * Returns a value from the metadata.
	 *
	 * @param string $field   the property to retrieve
	 * @param mixed $default the default to return if the property doesn't exist
	 */
	public function getField(string $field, mixed $default = null): mixed
	{
		return $this->metadata[$field] ?? $default;
	}


	/**
	 * Returns a value from a child property in the metadata.
	 *
	 * @param string $parentField the parent property
	 * @param string $field       the property to retrieve
	 * @param mixed $default the default to return if the property doesn't exist
	 */
	public function getChildProperty(string $parentField, string $field, mixed $default = null): mixed
	{
		if (!isset($this->metadata[$parentField])) {
			return $default;
		}

		if (!isset($this->metadata[$parentField][$field])) {
			return $default;
		}

		return $this->metadata[$parentField][$field];
	}


	/**
	 * Returns a value from the error metadata.
	 *
	 * @param string $field   the property to retrieve
	 * @param mixed $default the default to return if the property doesn't exist
	 */
	public function getErrorProperty(string $field, mixed $default = null): mixed
	{
		return $this->getChildProperty('error', $field, $default);
	}


	/**
	 * Returns a value from the "metadata" metadata. *Brain explodes*.
	 *
	 * @param string $field   the property to retrieve
	 * @param mixed $default the default to return if the property doesn't exist
	 */
	public function getMetadataProperty(string $field, mixed $default = null): mixed
	{
		return $this->getChildProperty('metadata', $field, $default);
	}


	/**
	 * The ID of the application this access token is for.
	 */
	public function getAppId(): ?string
	{
		return $this->getField('app_id');
	}


	/**
	 * Name of the application this access token is for.
	 */
	public function getApplication(): ?string
	{
		return $this->getField('application');
	}


	/**
	 * Any error that a request to the graph api
	 * would return due to the access token.
	 */
	public function isError(): bool
	{
		return $this->getField('error') !== null;
	}


	/**
	 * The error code for the error.
	 */
	public function getErrorCode(): ?int
	{
		return $this->getErrorProperty('code');
	}


	/**
	 * The error message for the error.
	 */
	public function getErrorMessage(): ?string
	{
		return $this->getErrorProperty('message');
	}


	/**
	 * The error subcode for the error.
	 */
	public function getErrorSubcode(): ?int
	{
		return $this->getErrorProperty('subcode');
	}


	/**
	 * DateTime when this access token expires.
	 */
	public function getExpiresAt(): ?DateTime
	{
		$value = $this->getField('expires_at');
		if ($value === 0) {
			return null;
		}
		return $value;
	}


	/**
	 * Whether the access token is still valid or not.
	 */
	public function getIsValid(): ?bool
	{
		return $this->getField('is_valid');
	}


	/**
	 * DateTime when this access token was issued.
	 *
	 * Note that the issued_at field is not returned
	 * for short-lived access tokens.
	 *
	 * @see https://developers.facebook.com/docs/facebook-login/access-tokens#debug
	 */
	public function getIssuedAt(): ?DateTime
	{
		return $this->getField('issued_at');
	}


	/**
	 * General metadata associated with the access token.
	 * Can contain data like 'sso', 'auth_type', 'auth_nonce'.
	 */
	public function getMetadata(): ?array
	{
		return $this->getField('metadata');
	}


	/**
	 * The 'sso' child property from the 'metadata' parent property.
	 */
	public function getSso(): ?string
	{
		return $this->getMetadataProperty('sso');
	}


	/**
	 * The 'auth_type' child property from the 'metadata' parent property.
	 */
	public function getAuthType(): ?string
	{
		return $this->getMetadataProperty('auth_type');
	}


	/**
	 * The 'auth_nonce' child property from the 'metadata' parent property.
	 */
	public function getAuthNonce(): ?string
	{
		return $this->getMetadataProperty('auth_nonce');
	}


	/**
	 * For impersonated access tokens, the ID of
	 * the page this token contains.
	 */
	public function getProfileId(): ?string
	{
		return $this->getField('profile_id');
	}


	/**
	 * List of permissions that the user has granted for
	 * the app in this access token.
	 *
	 * @return mixed[]
	 */
	public function getScopes(): array
	{
		return $this->getField('scopes');
	}


	/**
	 * The ID of the user this access token is for.
	 */
	public function getUserId(): ?string
	{
		return $this->getField('user_id');
	}


	/**
	 * Ensures the app ID from the access token
	 * metadata is what we expect.
	 *
	 * @throws SDKException
	 */
	public function validateAppId(string $appId): void
	{
		if ($this->getAppId() !== $appId) {
			throw new SDKException('Access token metadata contains unexpected app ID.', 401);
		}
	}


	/**
	 * Ensures the user ID from the access token
	 * metadata is what we expect.
	 *
	 * @throws SDKException
	 */
	public function validateUserId(string $userId): void
	{
		if ($this->getUserId() !== $userId) {
			throw new SDKException('Access token metadata contains unexpected user ID.', 401);
		}
	}


	/**
	 * Ensures the access token has not expired yet.
	 *
	 * @throws SDKException
	 */
	public function validateExpiration(): void
	{
		if (!$this->getExpiresAt() instanceof DateTime) {
			return;
		}

		if ($this->getExpiresAt()->getTimestamp() < time()) {
			throw new SDKException('Inspection of access token metadata shows that the access token has expired.', 401);
		}
	}


	/**
	 * Converts a unix timestamp into a DateTime entity.
	 */
	private function convertTimestampToDateTime(int $timestamp): DateTime
	{
		$dateTime = new DateTime;
		$dateTime->setTimestamp($timestamp);

		return $dateTime;
	}


	/**
	 * Casts the unix timestamps as DateTime entities.
	 */
	private function castTimestampsToDateTime(): void
	{
		foreach (static::$dateProperties as $dateProperty) {
			if (isset($this->metadata[$dateProperty]) && $this->metadata[$dateProperty] !== 0) {
				$this->metadata[$dateProperty] = $this->convertTimestampToDateTime($this->metadata[$dateProperty]);
			}
		}
	}
}
