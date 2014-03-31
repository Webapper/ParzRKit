<?php

namespace ParzRKit\Compiler;

class RecursiveNodeIterator implements \RecursiveIterator
{
	/**
	 * @var BasicNode
	 */
	protected $node;
	
	protected $index = 0;
	
	protected $nodes = array();
	
	/**
	 * Constructor
	 * @param BasicNode $node
	 * @throws \InvalidArgumentException
	 */
	public function __construct(BasicNode $node)
	{
		$this->node = $node;
		
		$subnodes = $node->getNodes();
		if (is_object($subnodes)) {
			$this->nodes[] = $subnodes;
		} else if (is_array($subnodes)) {
			$this->nodes = $subnodes;
		}
	}
	
	/**
	 * Returns the iterated Node
	 * @return BasicNode
	 */
	public function getNode()
	{
		return $this->node;
	}
	
	/**
	 * {@inheritDoc}
	 */
	public function getChildren()
	{
		return new static($this->nodes[$this->index]);
	}
	
	/**
	 * {@inheritDoc}
	 */
	public function hasChildren()
	{
		return ($this->nodes[$this->index]->getNodes() !== null);
	}
	
	/**
	 * {@inheritDoc}
	 */
	public function current()
	{
		return $this->nodes[$this->index];
	}
	
	/**
	 * {@inheritDoc}
	 */
	public function key()
	{
		return $this->index;
	}
	
	/**
	 * {@inheritDoc}
	 */
	public function next()
	{
		$this->index++;
	}
	
	/**
	 * {@inheritDoc}
	 */
	public function rewind()
	{
		$this->index = 0;
	}
	
	/**
	 * {@inheritDoc}
	 */
	public function valid()
	{
		return (isset($this->nodes[$this->index]));
	}
}