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

namespace JanuSoftware\Facebook\Tests\Authentication;

use JanuSoftware\Facebook\Client;
use JanuSoftware\Facebook\Request;
use JanuSoftware\Facebook\Response;

class FooClientForOAuth2 extends Client
{
	protected string $response = '';


	public function setMetadataResponse(): void
	{
		$this->response = '{"data":{"user_id":"444"}}';
	}


	public function setAccessTokenResponse(): void
	{
		$this->response = '{"access_token":"my_access_token","expires":"1422115200"}';
	}


	public function setCodeResponse(): void
	{
		$this->response = '{"code":"my_neat_code"}';
	}


	public function sendRequest(Request $request): Response
	{
		return new Response(
			$request,
			$this->response,
			200,
			[],
		);
	}
}
