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

use JanuSoftware\Facebook\Exception\SDKException;
use Safe\Exceptions\FilesystemException;
use Safe\Exceptions\PcreException;
use Safe\Exceptions\StreamException;
use function Safe\fclose;
use function Safe\filesize;
use function Safe\fopen;
use function Safe\preg_match;
use function Safe\stream_get_contents;


class File
{
	/** @var resource the stream pointing to the file */
	protected $stream;


	/**
	 * Creates a new File entity.
	 *
	 * @throws SDKException
	 */
	public function __construct(
		protected string $path,
		private int $maxLength = -1,
		private int $offset = -1,
	) {
		$this->open();
	}


	/**
	 * Closes the stream when destructed.
	 * @throws FilesystemException
	 */
	public function __destruct()
	{
		$this->close();
	}


	/**
	 * Opens a stream for the file.
	 * @throws SDKException
	 */
	public function open(): void
	{
		if (!$this->isRemoteFile($this->path) && !is_readable($this->path)) {
			throw new SDKException('Failed to create File entity. Unable to read resource: ' . $this->path . '.');
		}

		try {
			$this->stream = fopen($this->path, 'r');
		} catch (FilesystemException $exception) {
			throw new SDKException('Failed to create File entity. Unable to open resource: ' . $this->path . '.', $exception->getCode(), $exception);
		}
	}


	/**
	 * Stops the file stream.
	 * @throws FilesystemException
	 */
	public function close(): void
	{
		fclose($this->stream);
	}


	/**
	 * Return the contents of the file.
	 * @throws StreamException
	 */
	public function getContents(): string
	{
		return stream_get_contents($this->stream, $this->maxLength, $this->offset);
	}


	/**
	 * Return the name of the file.
	 */
	public function getFileName(): string
	{
		return basename($this->path);
	}


	/**
	 * Return the path of the file.
	 */
	public function getFilePath(): string
	{
		return $this->path;
	}


	/**
	 * Return the size of the file.
	 *
	 * @throws FilesystemException
	 */
	public function getSize(): int
	{
		return filesize($this->path);
	}


	/**
	 * Return the mimetype of the file.
	 */
	public function getMimetype(): string
	{
		return Mimetypes::getInstance()
			->fromFilename($this->path) ?? 'text/plain';
	}


	/**
	 * Returns true if the path to the file is remote.
	 * @throws PcreException
	 */
	protected function isRemoteFile(string $pathToFile): bool
	{
		return preg_match('/^(https?|ftp):\/\/.*/', $pathToFile) === 1;
	}
}
