<?php

namespace ParzRKit;

abstract class Linker extends \ArrayObject
{
	/**
	 * @var Compiler
	 */
	protected $compiler;
	
	/**
	 * Constructor
	 * @param Compiler $compiler
	 */
	public function __construct(Compiler $compiler=null)
	{
		$this->compiler = $compiler;
		parent::__construct();
	}
	
	public function setCompiler(Compiler $compiler)
	{
		$this->compiler = $compiler;
	}
	
	/**
	 * Returns the Compiler passed to constructor
	 * @return Compiler
	 */
	public function getCompiler()
	{
		return $this->compiler;
	}
	
	/**
	 * Appends a child-linker then returns with it
	 * @param Linker $child
	 * @return Linker
	 */
	public function appendChild(Linker $child=null)
	{
		if ($child === null) $child = new static($this->getCompiler());
		
		$this->append($child);
		
		return $child;
	}
	
	/**
	 * Sets the last item's value
	 * @param mixed $value
	 */
	public function setLast($value)
	{
		$this->offsetSet($this->count() - 1, $value);
	}
	
	/**
	 * Make the linking process - depends on the Linker itself
	 * @return mixed The returning stream of the linking
	 */
	abstract public function link();
}