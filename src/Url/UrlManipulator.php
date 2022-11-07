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

use function Safe\ksort;
use function Safe\parse_url;
use function Safe\preg_replace;

class UrlManipulator
{
	/**
	 * Remove params from a URL.
	 *
	 * @param string $url            the URL to filter
	 * @param array  $paramsToFilter the params to filter from the URL
	 *
	 * @return string the URL with the params removed
	 */
	public static function removeParamsFromUrl(string $url, array $paramsToFilter): string
	{
		$parts = parse_url($url);

		$query = '';
		if (isset($parts['query'])) {
			$params = [];
			parse_str($parts['query'], $params);

			// Remove query params
			foreach ($paramsToFilter as $paramToFilter) {
				unset($params[$paramToFilter]);
			}

			if ($params !== []) {
				$query = '?' . http_build_query($params);
			}
		}

		$scheme = isset($parts['scheme']) ? $parts['scheme'] . '://' : '';
		$host = $parts['host'] ?? '';
		$port = isset($parts['port']) ? ':' . $parts['port'] : '';
		$path = $parts['path'] ?? '';
		$fragment = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';

		return $scheme . $host . $port . $path . $query . $fragment;
	}


	/**
	 * Gracefully appends params to the URL.
	 *
	 * @param string $url       the URL that will receive the params
	 * @param array  $newParams the params to append to the URL
	 */
	public static function appendParamsToUrl(string $url, array $newParams = []): string
	{
		if ($newParams === []) {
			return $url;
		}

		if (!str_contains($url, '?')) {
			return $url . '?' . http_build_query($newParams);
		}

		[$path, $query] = explode('?', $url, 2);
		$existingParams = [];
		parse_str($query, $existingParams);

		// Favor params from the original URL over $newParams
		$newParams = array_merge($newParams, $existingParams);

		// Sort for a predicable order
		ksort($newParams);

		return $path . '?' . http_build_query($newParams);
	}


	/**
	 * Returns the params from a URL in the form of an array.
	 *
	 * @param string $url the URL to parse the params from
	 * @return mixed[]
	 */
	public static function getParamsAsArray(string $url): array
	{
		$query = parse_url($url, PHP_URL_QUERY);
		if ($query === null) {
			return [];
		}
		$params = [];
		parse_str($query, $params);

		return $params;
	}


	/**
	 * Adds the params of the first URL to the second URL.
	 * Any params that already exist in the second URL will go untouched.
	 *
	 * @param string $urlToStealFrom the URL harvest the params from
	 * @param string $urlToAddTo     the URL that will receive the new params
	 *
	 * @return string the $urlToAddTo with any new params from $urlToStealFrom
	 */
	public static function mergeUrlParams(string $urlToStealFrom, string $urlToAddTo): string
	{
		$newParams = static::getParamsAsArray($urlToStealFrom);
		// Nothing new to add, return as-is
		if ($newParams === []) {
			return $urlToAddTo;
		}

		return static::appendParamsToUrl($urlToAddTo, $newParams);
	}


	/**
	 * Check for a "/" prefix and prepend it if not exists.
	 */
	public static function forceSlashPrefix(?string $string): string
	{
		if ($string === null || $string === '') {
			return '';
		}

		return str_starts_with($string, '/') ? $string : '/' . $string;
	}


	/**
	 * Trims off the hostname and Graph version from a URL.
	 *
	 * @param string $urlToTrim the URL the needs the surgery
	 *
	 * @return string the $urlToTrim with the hostname and Graph version removed
	 */
	public static function baseGraphUrlEndpoint(string $urlToTrim): string
	{
		return '/' . preg_replace('/^https:\/\/.+\.facebook\.com(\/v.+?)?\//', '', $urlToTrim);
	}
}
