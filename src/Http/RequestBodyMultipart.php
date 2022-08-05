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

namespace JanuSoftware\Facebook\Http;

use JanuSoftware\Facebook\FileUpload\File;
use function Safe\sprintf;

/**
 * Some things copied from Guzzle.
 *
 * @see https://github.com/guzzle/guzzle/blob/master/src/Post/MultipartBody.php
 */
class RequestBodyMultipart implements RequestBodyInterface
{
	private string $boundary;


	/**
	 * @param array  $params   the parameters to send with this request
	 * @param array  $files    the files to send with this request
	 * @param string|null $boundary provide a specific boundary
	 */
	public function __construct(
		private array $params = [],
		private array $files = [],
		string $boundary = null,
	) {
		$this->boundary = $boundary ?? uniqid();
	}


	public function getBody(): string
	{
		$body = '';

		// Compile normal params
		$params = $this->getNestedParams($this->params);
		foreach ($params as $k => $v) {
			$body .= $this->getParamString($k, $v);
		}

		// Compile files
		foreach ($this->files as $k => $v) {
			$body .= $this->getFileString($k, $v);
		}

		// Peace out
		$body .= "--{$this->boundary}--\r\n";

		return $body;
	}


	/**
	 * Get the boundary.
	 */
	public function getBoundary(): string
	{
		return $this->boundary;
	}


	/**
	 * Get the string needed to transfer a file.
	 */
	private function getFileString(string $name, File $file): string
	{
		return sprintf(
			"--%s\r\nContent-Disposition: form-data; name=\"%s\"; filename=\"%s\"%s\r\n\r\n%s\r\n",
			$this->boundary,
			$name,
			$file->getFileName(),
			$this->getFileHeaders($file),
			$file->getContents(),
		);
	}


	/**
	 * Get the string needed to transfer a POST field.
	 */
	private function getParamString(string $name, string $value): string
	{
		return sprintf(
			"--%s\r\nContent-Disposition: form-data; name=\"%s\"\r\n\r\n%s\r\n",
			$this->boundary,
			$name,
			$value,
		);
	}


	/**
	 * Returns the params as an array of nested params.
	 * @return array<string, string>
	 */
	private function getNestedParams(array $params): array
	{
		$query = http_build_query($params);
		$params = explode('&', $query);
		$result = [];

		foreach ($params as $param) {
			[$key, $value] = explode('=', $param, 2);
			$result[urldecode($key)] = urldecode($value);
		}

		return $result;
	}


	/**
	 * Get the headers needed before transferring the content of a POST file.
	 */
	protected function getFileHeaders(File $file): string
	{
		return "\r\nContent-Type: {$file->getMimetype()}";
	}
}
