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

namespace JanuSoftware\Facebook\FileUpload;

class TransferChunk
{
	public function __construct(
		private File $file,
		private int $uploadSessionId,
		private int $videoId,
		private int $startOffset,
		private int $endOffset,
	) {
	}


	/**
	 * Return the file entity.
	 */
	public function getFile(): File
	{
		return $this->file;
	}


	/**
	 * Return a File entity with partial content.
	 */
	public function getPartialFile(): File
	{
		$maxLength = $this->endOffset - $this->startOffset;

		return new File($this->file->getFilePath(), $maxLength, $this->startOffset);
	}


	/**
	 * Return upload session Id.
	 */
	public function getUploadSessionId(): int
	{
		return $this->uploadSessionId;
	}


	/**
	 * Check whether is the last chunk.
	 */
	public function isLastChunk(): bool
	{
		return $this->startOffset === $this->endOffset;
	}


	public function getStartOffset(): int
	{
		return $this->startOffset;
	}


	/**
	 * Get uploaded video Id.
	 */
	public function getVideoId(): int
	{
		return $this->videoId;
	}
}
