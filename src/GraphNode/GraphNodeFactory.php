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

use JanuSoftware\Facebook\Exception\SDKException;
use JanuSoftware\Facebook\Response;


/**
 * Class GraphNodeFactory.
 * ## Assumptions ##
 * GraphEdge - is ALWAYS a numeric array
 * GraphEdge - is ALWAYS an array of GraphNode types
 * GraphNode - is ALWAYS an associative array
 * GraphNode - MAY contain GraphNode's "recurrable"
 * GraphNode - MAY contain GraphEdge's "recurrable"
 * GraphNode - MAY contain DateTime's "primitives"
 * GraphNode - MAY contain string's "primitives"
 */
class GraphNodeFactory
{
	/**
	 * @const string The base graph object class.
	 */
	final public const BaseGraphNodeClass = GraphNode::class;

	/**
	 * @const string The base graph edge class.
	 */
	final public const BaseGraphEdgeClass = GraphEdge::class;

	/**
	 * @const string The graph object prefix.
	 */
	final public const BaseGraphObjectPrefix = '\JanuSoftware\Facebook\GraphNode\\';

	/**
	 * The decoded body of the Response entity from Graph
	 */
	protected array $decodedBody;


	/**
	 * Init this Graph object.
	 *
	 * @param Response $response the response entity from Graph
	 */
	public function __construct(
		protected Response $response,
	) {
		$this->decodedBody = $response->getDecodedBody();
	}


	/**
	 * Tries to convert a Response entity into a GraphNode.
	 *
	 * @param string|null $subclassName the GraphNode sub class to cast to
	 *
	 * @throws SDKException
	 */
	public function makeGraphNode(
		?string $subclassName = null,
	): GraphNode
	{
		$this->validateResponseCastableAsGraphNode();

		return $this->castAsGraphNodeOrGraphEdge($this->decodedBody, $subclassName);
	}


	/**
	 * Tries to convert a Response entity into a GraphEdge.
	 *
	 * @param string|null $subclassName the GraphNode sub class to cast the list items to
	 * @param bool        $auto_prefix  toggle to auto-prefix the subclass name
	 *
	 * @throws SDKException
	 */
	public function makeGraphEdge(?string $subclassName = null, bool $auto_prefix = true): GraphEdge
	{
		$this->validateResponseCastableAsGraphEdge();

		if ($subclassName !== null && $auto_prefix) {
			$subclassName = static::BaseGraphObjectPrefix . $subclassName;
		}

		return $this->castAsGraphNodeOrGraphEdge($this->decodedBody, $subclassName);
	}


	/**
	 * Validates that the return data can be cast as a GraphNode.
	 * @throws SDKException
	 */
	public function validateResponseCastableAsGraphNode(): void
	{
		if (isset($this->decodedBody['data']) && static::isCastableAsGraphEdge($this->decodedBody['data'])) {
			throw new SDKException('Unable to convert response from Graph to a GraphNode because the response looks like a GraphEdge. Try using GraphNodeFactory::makeGraphEdge() instead.', 620);
		}
	}


	/**
	 * Validates that the return data can be cast as a GraphEdge.
	 * @throws SDKException
	 */
	public function validateResponseCastableAsGraphEdge(): void
	{
		if (!(isset($this->decodedBody['data']) && static::isCastableAsGraphEdge($this->decodedBody['data']))) {
			throw new SDKException('Unable to convert response from Graph to a GraphEdge because the response does not look like a GraphEdge. Try using GraphNodeFactory::makeGraphNode() instead.', 620);
		}
	}


	/**
	 * Safely instantiates a GraphNode of $subclassName.
	 *
	 * @param array       $data         the array of data to iterate over
	 * @param string|null $subclassName the subclass to cast this collection to
	 *
	 * @throws SDKException
	 */
	public function safelyMakeGraphNode(array $data, ?string $subclassName = null): GraphNode
	{
		$subclassName ??= static::BaseGraphNodeClass;
		static::validateSubclass($subclassName);

		// Remember the parent node ID
		$parentNodeId = $data['id'] ?? null;

		$items = [];

		foreach ($data as $k => $v) {
			// Array means could be recurable
			if (is_array($v)) {
				// Detect any smart-casting from the $graphNodeMap array.
				// This is always empty on the GraphNode collection, but subclasses can define
				// their own array of smart-casting types.
				$graphNodeMap = $subclassName::getNodeMap();
				$objectSubClass = $graphNodeMap[$k] ?? null;

				// Could be a GraphEdge or GraphNode
				$items[$k] = $this->castAsGraphNodeOrGraphEdge($v, $objectSubClass, $k, $parentNodeId);
			} else {
				$items[$k] = $v;
			}
		}

		return new $subclassName($items);
	}


	/**
	 * Takes an array of values and determines how to cast each node.
	 *
	 * @param array           $data         the array of data to iterate over
	 * @param string|null     $subclassName the subclass to cast this collection to
	 * @param string|int|null $parentKey    the key of this data (Graph edge)
	 * @param string|null     $parentNodeId the parent Graph node ID
	 *
	 * @throws SDKException
	 */
	public function castAsGraphNodeOrGraphEdge(
		array $data,
		?string $subclassName = null,
		string|int|null $parentKey = null,
		?string $parentNodeId = null,
	): GraphEdge|GraphNode
	{
		if (isset($data['data'])) {
			// Create GraphEdge
			if (static::isCastableAsGraphEdge($data['data'])) {
				return $this->safelyMakeGraphEdge($data, $subclassName, $parentKey, $parentNodeId);
			}
			// Sometimes Graph is a weirdo and returns a GraphNode under the "data" key
			$data = $data['data'];
		}

		// Create GraphNode
		return $this->safelyMakeGraphNode($data, $subclassName);
	}


	/**
	 * Return an array of GraphNode's.
	 *
	 * @param array       $data         the array of data to iterate over
	 * @param string|null $subclassName the GraphNode subclass to cast each item in the list to
	 * @param string|int|null $parentKey the key of this data (Graph edge)
	 * @param string|null $parentNodeId the parent Graph node ID
	 *
	 * @throws SDKException
	 */
	public function safelyMakeGraphEdge(
		array $data,
		?string $subclassName = null,
		string|int|null $parentKey = null,
		?string $parentNodeId = null,
	): GraphEdge
	{
		if (!isset($data['data'])) {
			throw new SDKException('Cannot cast data to GraphEdge. Expected a "data" key.', 620);
		}

		$dataList = [];
		foreach ($data['data'] as $graphNode) {
			$dataList[] = $this->safelyMakeGraphNode($graphNode, $subclassName);
		}

		$metaData = $this->getMetaData($data);

		// We'll need to make an edge endpoint for this in case it's a GraphEdge (for cursor pagination)
		$parentGraphEdgeEndpoint = $parentNodeId !== null && $parentKey !== null
			? '/' . $parentNodeId . '/' . $parentKey
			: null;
		$className = static::BaseGraphEdgeClass;

		return new $className($this->response->getRequest(), $dataList, $metaData, $parentGraphEdgeEndpoint, $subclassName);
	}


	/**
	 * Get the meta data from a list in a Graph response.
	 *
	 * @param array $data the Graph response
	 * @return mixed[]
	 */
	public function getMetaData(array $data): array
	{
		unset($data['data']);

		return $data;
	}


	/**
	 * Determines whether or not the data should be cast as a GraphEdge.
	 */
	public static function isCastableAsGraphEdge(array $data): bool
	{
		if ($data === []) {
			return true;
		}

		// Checks for a sequential numeric array which would be a GraphEdge
		return array_keys($data) === range(0, count($data) - 1);
	}


	/**
	 * Ensures that the subclass in question is valid.
	 *
	 * @param string $subclassName the GraphNode subclass to validate
	 *
	 * @throws SDKException
	 */
	public static function validateSubclass(string $subclassName): void
	{
		if (
			$subclassName == static::BaseGraphNodeClass
			|| is_subclass_of($subclassName, static::BaseGraphNodeClass)
		) {
			return;
		}

		throw new SDKException('The given subclass "' . $subclassName . '" is not valid. Cannot cast to an object that is not a GraphNode subclass.', 620);
	}
}
