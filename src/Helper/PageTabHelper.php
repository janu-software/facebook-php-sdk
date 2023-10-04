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

namespace JanuSoftware\Facebook\Helper;

use JanuSoftware\Facebook\Application;
use JanuSoftware\Facebook\Client;

class PageTabHelper extends CanvasHelper
{
	protected ?array $pageData = null;


	/**
	 * Initialize the helper and process available signed request data.
	 *
	 * @param Application $application the Application entity
	 * @param Client      $client       the client to make HTTP requests
	 * @param string      $graphVersion the version of Graph to use
	 */
	public function __construct(Application $application, Client $client, string $graphVersion)
	{
		parent::__construct($application, $client, $graphVersion);

		if ($this->signedRequest === null) {
			return;
		}

		$this->pageData = $this->signedRequest->get('page');
	}


	/**
	 * Returns a value from the page data.
	 */
	public function getPageData(string $key, mixed $default = null): mixed
	{
		return $this->pageData[$key] ?? $default;
	}


	/**
	 * Returns true if the user is an admin.
	 */
	public function isAdmin(): bool
	{
		return $this->getPageData('admin') === true;
	}


	/**
	 * Returns the page id if available.
	 */
	public function getPageId(): ?string
	{
		return $this->getPageData('id');
	}
}
