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

namespace JanuSoftware\Facebook\GraphNode;

use ArrayAccess;
use ArrayIterator;
use Closure;
use Countable;
use IteratorAggregate;
use JanuSoftware\Facebook\Exception\SDKException;
use JanuSoftware\Facebook\Request;
use JanuSoftware\Facebook\Url\UrlManipulator;
use function Safe\json_encode;
use Stringable;


class GraphEdge implements ArrayAccess, Countable, IteratorAggregate, Stringable
{
	/**
	 * Init this collection of GraphNode's.
	 *
	 * @param Request     $request            the original request that generated this data
	 * @param array       $items              an array of GraphNode's
	 * @param array       $metaData           an array of Graph meta data like pagination, etc
	 * @param string|null $parentEdgeEndpoint the parent Graph edge endpoint that generated the list
	 * @param string|null $subclassName the subclass of the child GraphNode's
	 */
	public function __construct(
		protected Request $request,
		protected array $items = [],
		protected array $metaData = [],
		protected ?string $parentEdgeEndpoint = null,
		protected ?string $subclassName = null,
	) {
	}


	/**
	 * Gets the value of a field from the Graph node.
	 *
	 * @param string $name the field to retrieve
	 * @param mixed $default the default to return if the field doesn't exist
	 */
	public function getField(string $name, mixed $default = null): mixed
	{
		return $this->items[$name] ?? $default;
	}


	/**
	 * Returns a list of all fields set on the object.
	 * @return int[]|string[]
	 */
	public function getFieldNames(): array
	{
		return array_keys($this->items);
	}


	/**
	 * Get all of the items in the collection.
	 * @return mixed[]
	 */
	public function all(): array
	{
		return $this->items;
	}


	/**
	 * Get the collection of items as a plain array.
	 * @return mixed[]
	 */
	public function asArray(): array
	{
		return array_map(function ($value) {
			if ($value instanceof GraphNode || $value instanceof self) {
				return $value->asArray();
			}

			return $value;
		}, $this->items);
	}


	public function map(Closure $callback): self
	{
		return new self($this->request, array_map($callback, $this->items, array_keys($this->items)), $this->metaData, $this->parentEdgeEndpoint, $this->subclassName);
	}


	/**
	 * Get the collection of items as JSON.
	 */
	public function asJson(int $options = 0): string
	{
		return json_encode($this->asArray(), $options);
	}


	/**
	 * Count the number of items in the collection.
	 */
	public function count(): int
	{
		return count($this->items);
	}


	/**
	 * Get an iterator for the items.
	 */
	public function getIterator(): ArrayIterator
	{
		return new ArrayIterator($this->items);
	}


	/**
	 * Determine if an item exists at an offset.
	 */
	public function offsetExists(mixed $offset): bool
	{
		return array_key_exists($offset, $this->items);
	}


	/**
	 * Get an item at a given offset.
	 */
	public function offsetGet(mixed $offset): mixed
	{
		return $this->items[$offset];
	}


	/**
	 * Set the item at a given offset.
	 */
	public function offsetSet(mixed $offset, mixed $value): void
	{
		if ($offset === null) {
			$this->items[] = $value;
		} else {
			$this->items[$offset] = $value;
		}
	}


	/**
	 * Unset the item at a given offset.
	 */
	public function offsetUnset(mixed $offset): void
	{
		unset($this->items[$offset]);
	}


	/**
	 * Convert the collection to its string representation.
	 */
	public function __toString(): string
	{
		return $this->asJson();
	}


	/**
	 * Gets the parent Graph edge endpoint that generated the list.
	 */
	public function getParentGraphEdge(): ?string
	{
		return $this->parentEdgeEndpoint;
	}


	/**
	 * Gets the subclass name that the child GraphNode's are cast as.
	 */
	public function getSubClassName(): ?string
	{
		return $this->subclassName;
	}


	/**
	 * Returns the raw meta data associated with this GraphEdge.
	 * @return mixed[]
	 */
	public function getMetaData(): array
	{
		return $this->metaData;
	}


	/**
	 * Returns the next cursor if it exists.
	 */
	public function getNextCursor(): ?string
	{
		return $this->getCursor('after');
	}


	/**
	 * Returns the previous cursor if it exists.
	 */
	public function getPreviousCursor(): ?string
	{
		return $this->getCursor('before');
	}


	/**
	 * Returns the cursor for a specific direction if it exists.
	 *
	 * @param string $direction The direction of the page: after|before
	 */
	public function getCursor(string $direction): ?string
	{
		return $this->metaData['paging']['cursors'][$direction] ?? null;
	}


	/**
	 * Generates a pagination URL based on a cursor.
	 *
	 * @param string $direction The direction of the page: next|previous
	 *
	 * @throws SDKException
	 */
	public function getPaginationUrl(string $direction): ?string
	{
		$this->validateForPagination();

		// Do we have a paging URL?
		if (!isset($this->metaData['paging'][$direction])) {
			return null;
		}

		$pageUrl = $this->metaData['paging'][$direction];

		return UrlManipulator::baseGraphUrlEndpoint($pageUrl);
	}


	/**
	 * Validates whether or not we can paginate on this request.
	 * @throws SDKException
	 */
	public function validateForPagination(): void
	{
		if ($this->request->getMethod() !== 'GET') {
			throw new SDKException('You can only paginate on a GET request.', 720);
		}
	}


	/**
	 * Gets the request object needed to make a next|previous page request.
	 *
	 * @param string $direction The direction of the page: next|previous
	 *
	 * @throws SDKException
	 */
	public function getPaginationRequest(string $direction): ?Request
	{
		$pageUrl = $this->getPaginationUrl($direction);
		if ($pageUrl === null) {
			return null;
		}

		$newRequest = clone $this->request;
		$newRequest->setEndpoint($pageUrl);

		return $newRequest;
	}


	/**
	 * Gets the request object needed to make a "next" page request.
	 * @throws SDKException
	 */
	public function getNextPageRequest(): ?Request
	{
		return $this->getPaginationRequest('next');
	}


	/**
	 * Gets the request object needed to make a "previous" page request.
	 * @throws SDKException
	 */
	public function getPreviousPageRequest(): ?Request
	{
		return $this->getPaginationRequest('previous');
	}


	/**
	 * The total number of results according to Graph if it exists.
	 * This will be returned if the summary=true modifier is present in the request.
	 */
	public function getTotalCount(): ?int
	{
		return $this->metaData['summary']['total_count'] ?? null;
	}
}
