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

namespace JanuSoftware\Facebook\FileUpload;

use JanuSoftware\Facebook\Application;
use JanuSoftware\Facebook\Authentication\AccessToken;
use JanuSoftware\Facebook\Client;
use JanuSoftware\Facebook\Exception\ResponseException;
use JanuSoftware\Facebook\Exception\ResumableUploadException;
use JanuSoftware\Facebook\Exception\SDKException;
use JanuSoftware\Facebook\Request;
use Safe\Exceptions\FilesystemException;


class ResumableUploader
{
	public function __construct(
		protected Application $application,
		protected Client $client,
		protected AccessToken|string|null $accessToken,
		protected string $graphVersion,
	) {
	}


	/**
	 * Upload by chunks - start phase.
	 * @throws SDKException|FilesystemException
	 */
	public function start(string $endpoint, File $file): TransferChunk
	{
		$params = [
			'upload_phase' => 'start',
			'file_size' => $file->getSize(),
		];
		$response = $this->sendUploadRequest($endpoint, $params);

		return new TransferChunk($file, (int) $response['upload_session_id'], (int) $response['video_id'], (int) $response['start_offset'], (int) $response['end_offset']);
	}


	/**
	 * Upload by chunks - transfer phase.
	 *
	 * @throws ResponseException
	 */
	public function transfer(string $endpoint, TransferChunk $transferChunk, bool $allowToThrow = false): TransferChunk
	{
		$params = [
			'upload_phase' => 'transfer',
			'upload_session_id' => $transferChunk->getUploadSessionId(),
			'start_offset' => $transferChunk->getStartOffset(),
			'video_file_chunk' => $transferChunk->getPartialFile(),
		];

		try {
			$response = $this->sendUploadRequest($endpoint, $params);
		} catch (ResponseException $e) {
			$throwable = $e->getPrevious();
			if ($allowToThrow || !$throwable instanceof ResumableUploadException) {
				throw $e;
			}

			// Return the same chunk entity so it can be retried.
			return $transferChunk;
		}

		return new TransferChunk($transferChunk->getFile(), $transferChunk->getUploadSessionId(), $transferChunk->getVideoId(), (int) $response['start_offset'], (int) $response['end_offset']);
	}


	/**
	 * Upload by chunks - finish phase.
	 *
	 * @throws SDKException
	 */
	public function finish(string $endpoint, string|int $uploadSessionId, array $metadata = []): bool
	{
		$params = array_merge($metadata, [
			'upload_phase' => 'finish',
			'upload_session_id' => $uploadSessionId,
		]);
		$response = $this->sendUploadRequest($endpoint, $params);

		return $response['success'];
	}


	/**
	 * Helper to make a Request and send it.
	 *
	 * @param string $endpoint the endpoint to POST to
	 * @param array  $params   the params to send with the request
	 * @return mixed[]
	 */
	private function sendUploadRequest(string $endpoint, array $params = []): array
	{
		$request = new Request($this->application, $this->accessToken, 'POST', $endpoint, $params, null, $this->graphVersion);

		return $this->client->sendRequest($request)
			->getDecodedBody();
	}
}
