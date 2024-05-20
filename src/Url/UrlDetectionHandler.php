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

namespace JanuSoftware\Facebook\Url;

use function Safe\preg_match;
use function Safe\preg_replace;


class UrlDetectionHandler implements UrlDetectionInterface
{
	public function getCurrentUrl(): string
	{
		return $this->getHttpScheme() . '://' . $this->getHostName() . $this->getServerVar('REQUEST_URI');
	}


	/**
	 * Get the currently active URL scheme.
	 */
	protected function getHttpScheme(): string
	{
		return $this->isBehindSsl() ? 'https' : 'http';
	}


	/**
	 * Tries to detect if the server is running behind an SSL.
	 */
	protected function isBehindSsl(): bool
	{
		// Check for proxy first
		$protocol = $this->getHeader('X_FORWARDED_PROTO');
		if ($protocol !== '' && $protocol !== '0') {
			return $this->protocolWithActiveSsl($protocol);
		}

		$protocol = $this->getServerVar('HTTPS');
		if ($protocol !== '' && $protocol !== '0') {
			return $this->protocolWithActiveSsl($protocol);
		}

		return $this->getServerVar('SERVER_PORT') === '443';
	}


	/**
	 * Detects an active SSL protocol value.
	 */
	protected function protocolWithActiveSsl(string $protocol): bool
	{
		$protocol = strtolower($protocol);

		return in_array($protocol, ['on', '1', 'https', 'ssl'], true);
	}


	/**
	 * Tries to detect the host name of the server.
	 * Some elements adapted from
	 * @see https://github.com/symfony/HttpFoundation/blob/master/Request.php
	 */
	protected function getHostName(): string
	{
		// Check for proxy first
		$header = $this->getHeader('X_FORWARDED_HOST');
		if ($header !== '' && $this->isValidForwardedHost($header)) {
			$elements = explode(',', $header);
			$host = $elements[count($elements) - 1];
		} else {
			$host = $this->getHeader('HOST');
			if ($host === '') {
				$host = $this->getServerVar('SERVER_NAME');
				if ($host === '') {
					$host = $this->getServerVar('SERVER_ADDR');
				}
			}
		}

		// trim and remove port number from host
		// host is lowercase as per RFC 952/2181
		$host = strtolower(preg_replace('/:\d+$/', '', trim($host)));

		// Port number
		$scheme = $this->getHttpScheme();
		$currentPort = $this->getCurrentPort();
		$appendPort = ':' . $currentPort;

		// Don't append port number if a normal port.
		if (($scheme === 'http' && $currentPort === '80') || ($scheme === 'https' && $currentPort === '443')) {
			$appendPort = '';
		}

		return $host . $appendPort;
	}


	protected function getCurrentPort(): string
	{
		// Check for proxy first
		$port = $this->getHeader('X_FORWARDED_PORT');
		if ($port !== '' && $port !== '0') {
			return $port;
		}

		$protocol = $this->getHeader('X_FORWARDED_PROTO');
		if ($protocol === 'https') {
			return '443';
		}

		return $this->getServerVar('SERVER_PORT');
	}


	/**
	 * Returns the a value from the $_SERVER super global.
	 */
	protected function getServerVar(string $key): string
	{
		return $_SERVER[$key] ?? '';
	}


	/**
	 * Gets a value from the HTTP request headers.
	 */
	protected function getHeader(string $key): string
	{
		return $this->getServerVar('HTTP_' . $key);
	}


	/**
	 * Checks if the value in X_FORWARDED_HOST is a valid hostname
	 * Could prevent unintended redirections.
	 */
	protected function isValidForwardedHost(string $header): bool
	{
		$elements = explode(',', $header);
		$host = $elements[count($elements) - 1];

		return preg_match('/^([a-z\\d](-*[a-z\\d])*)(\\.([a-z\\d](-*[a-z\\d])*))*$/i', $host) === 1 //valid chars check
			&& 0 < strlen($host) && strlen($host) < 254 //overall length check
			&& preg_match('/^[^\\.]{1,63}(\\.[^\\.]{1,63})*$/', $host) === 1; //length of each label
	}
}
