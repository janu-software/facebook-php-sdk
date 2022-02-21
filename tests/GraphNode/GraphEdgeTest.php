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

namespace JanuSoftware\Facebook\Tests\GraphNode;

use IteratorAggregate;
use JanuSoftware\Facebook\Application;
use JanuSoftware\Facebook\Exception\SDKException;
use JanuSoftware\Facebook\GraphNode\GraphEdge;
use JanuSoftware\Facebook\GraphNode\GraphNode;
use JanuSoftware\Facebook\Request;
use PHPUnit\Framework\TestCase;

class GraphEdgeTest extends TestCase
{
	protected Request $request;

	protected array $pagination = [
		'next' => 'https://graph.facebook.com/v7.12/998899/photos?pretty=0&limit=25&after=foo_after_cursor',
		'previous' => 'https://graph.facebook.com/v7.12/998899/photos?pretty=0&limit=25&before=foo_before_cursor',
	];


	protected function setUp(): void
	{
		$app = new Application('123', 'foo_app_secret');
		$this->request = new Request(
			$app,
			'foo_token',
			'GET',
			'/me/photos?keep=me',
			['foo' => 'bar'],
			'foo_eTag',
			'v1337',
		);
	}


	public function testNonGetRequestsWillThrow(): void
	{
		$this->expectException(SDKException::class);
		$this->request->setMethod('POST');
		$graphEdge = new GraphEdge($this->request);
		$graphEdge->validateForPagination();
	}


	public function testCanReturnGraphGeneratedPaginationEndpoints(): void
	{
		$graphEdge = new GraphEdge(
			$this->request,
			[],
			['paging' => $this->pagination],
		);
		$nextPage = $graphEdge->getPaginationUrl('next');
		$prevPage = $graphEdge->getPaginationUrl('previous');

		$this->assertEquals('/998899/photos?pretty=0&limit=25&after=foo_after_cursor', $nextPage);
		$this->assertEquals('/998899/photos?pretty=0&limit=25&before=foo_before_cursor', $prevPage);
	}


	public function testCanInstantiateNewPaginationRequest(): void
	{
		$graphEdge = new GraphEdge(
			$this->request,
			[],
			['paging' => $this->pagination],
			'/1234567890/likes',
		);
		$nextPage = $graphEdge->getNextPageRequest();
		$prevPage = $graphEdge->getPreviousPageRequest();

		$this->assertInstanceOf(Request::class, $nextPage);
		$this->assertInstanceOf(Request::class, $prevPage);
		$this->assertNotSame($this->request, $nextPage);
		$this->assertNotSame($this->request, $prevPage);
		$this->assertEquals('/v1337/998899/photos?access_token=foo_token&after=foo_after_cursor&appsecret_proof=857d5f035a894f16b4180f19966e055cdeab92d4d53017b13dccd6d43b6497af&foo=bar&limit=25&pretty=0', $nextPage->getUrl());
		$this->assertEquals('/v1337/998899/photos?access_token=foo_token&appsecret_proof=857d5f035a894f16b4180f19966e055cdeab92d4d53017b13dccd6d43b6497af&before=foo_before_cursor&foo=bar&limit=25&pretty=0', $prevPage->getUrl());
	}


	public function testCanMapOverNodes(): void
	{
		$graphEdge = new GraphEdge(
			$this->request,
			[
				new GraphNode(['name' => 'dummy1']),
				new GraphNode(['name' => 'dummy2']),
			],
			['paging' => $this->pagination],
			'/1234567890/likes',
		);

		$this->assertEquals(2, count($graphEdge->all()));
		$this->assertTrue($graphEdge->offsetExists(1));
		$this->assertFalse($graphEdge->offsetExists(2));

		$output = '';

		$graphEdge->map(function (GraphNode $node) use (&$output) {
			$output .= $node->getField('name');
		});

		$this->assertEquals('dummy1dummy2', $output);

		$graphEdge->offsetSet(null, new GraphNode(['name' => 'dummy3']));
		$this->assertEquals(3, count($graphEdge->all()));
		$this->assertTrue($graphEdge->offsetExists(2));
		$this->assertFalse($graphEdge->offsetExists(3));

		$graphEdge->offsetSet(2, new GraphNode(['name' => 'dummy3']));
		$this->assertEquals(3, count($graphEdge->all()));
		$this->assertTrue($graphEdge->offsetExists(2));
		$this->assertFalse($graphEdge->offsetExists(3));

		$graphEdge->offsetUnset(2);
		$this->assertEquals(2, count($graphEdge->all()));
		$this->assertTrue($graphEdge->offsetExists(1));
		$this->assertFalse($graphEdge->offsetExists(2));
	}


	public function testAnExistingPropertyCanBeAccessed(): void
	{
		$graphEdge = new GraphEdge($this->request, ['foo' => 'bar']);

		$field = $graphEdge->getField('foo');
		$this->assertEquals('bar', $field);
	}


	public function testAMissingPropertyWillReturnNull(): void
	{
		$graphEdge = new GraphEdge($this->request, ['foo' => 'bar']);
		$field = $graphEdge->getField('baz');

		$this->assertNull($field, 'Expected the property to return null.');
	}


	public function testAMissingPropertyWillReturnTheDefault(): void
	{
		$graphEdge = new GraphEdge($this->request, ['foo' => 'bar']);

		$field = $graphEdge->getField('baz', 'faz');
		$this->assertEquals('faz', $field);
	}


	public function testFalseDefaultsWillReturnSameType(): void
	{
		$graphEdge = new GraphEdge($this->request, ['foo' => 'bar']);

		$field = $graphEdge->getField('baz', '');
		$this->assertSame('', $field);

		$field = $graphEdge->getField('baz', 0);
		$this->assertSame(0, $field);

		$field = $graphEdge->getField('baz', false);
		$this->assertFalse($field);
	}


	public function testTheKeysFromTheCollectionCanBeReturned(): void
	{
		$graphEdge = new GraphEdge(
			$this->request,
			[
				'key1' => 'foo',
				'key2' => 'bar',
				'key3' => 'baz',
			],
		);

		$fieldNames = $graphEdge->getFieldNames();
		$this->assertEquals(['key1', 'key2', 'key3'], $fieldNames);
	}


	public function testAnArrayCanBeInjectedViaTheConstructor(): void
	{
		$graphEdge = new GraphEdge($this->request, ['foo', 'bar']);
		$this->assertEquals(['foo', 'bar'], $graphEdge->asArray());
	}


	public function testACollectionCanBeConvertedToProperJson(): void
	{
		$graphEdge = new GraphEdge($this->request, ['foo', 'bar', 123]);

		$graphEdgeAsString = $graphEdge->asJson();

		$this->assertEquals('["foo","bar",123]', $graphEdgeAsString);
	}


	public function testACollectionCanBeCounted(): void
	{
		$graphEdge = new GraphEdge($this->request, ['foo', 'bar', 'baz']);

		$graphEdgeCount = count($graphEdge);

		$this->assertEquals(3, $graphEdgeCount);
	}


	public function testACollectionCanBeAccessedAsAnArray(): void
	{
		$graphEdge = new GraphEdge($this->request, ['foo' => 'bar', 'faz' => 'baz']);

		$this->assertEquals('bar', $graphEdge['foo']);
		$this->assertEquals('baz', $graphEdge['faz']);
	}


	public function testACollectionCanBeIteratedOver(): void
	{
		$graphEdge = new GraphEdge($this->request, ['foo' => 'bar', 'faz' => 'baz']);

		$this->assertInstanceOf(IteratorAggregate::class, $graphEdge);

		$newArray = [];

		foreach ($graphEdge as $k => $v) {
			$newArray[$k] = $v;
		}

		$this->assertEquals(['foo' => 'bar', 'faz' => 'baz'], $newArray);
	}


	public function testAsString(): void
	{
		$graphEdgeString = (string) new GraphEdge($this->request, ['foo' => 'bar', 'faz' => 'baz']);

		$this->assertEquals('{"foo":"bar","faz":"baz"}', $graphEdgeString);
	}


	public function testMetaData(): void
	{
		$graphEdge = new GraphEdge($this->request, ['foo' => 'bar', 'faz' => 'baz'], ['a' => 'b']);

		$this->assertEquals(['a' => 'b'], $graphEdge->getMetaData());
	}


	public function testCursors(): void
	{
		$graphEdge = new GraphEdge($this->request, []);

		$this->assertNull($graphEdge->getNextCursor());
		$this->assertNull($graphEdge->getPreviousCursor());

		$graphEdge = new GraphEdge($this->request, [], ['paging' => ['cursors' => ['after' => 'foo', 'before' => 'bar']]]);

		$this->assertEquals('foo', $graphEdge->getNextCursor());
		$this->assertEquals('bar', $graphEdge->getPreviousCursor());
		$this->assertNull($graphEdge->getPaginationRequest('non_exists'));

		$this->assertNull($graphEdge->getTotalCount());
		$graphEdge = new GraphEdge($this->request, [], ['summary' => ['total_count' => 0]]);
		$this->assertEquals(0, $graphEdge->getTotalCount());
	}
}
