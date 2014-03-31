<?php

namespace ParzRKit;

use ParzRKit\Compiler\BasicNode;

class Compiler
{
	/**
	 * @var Linker
	 */
	protected $linker;
	
	/**
	 * @var BasicNode
	 */
	protected $root;
	
	/**
	 * @var array
	 */
	protected $args = array();
	
	/**
	 * Constructor
	 * @param BasicNode $root
	 */
	public function __construct(Linker $linker, BasicNode $root)
	{
		$this->linker = $linker;
		$this->root = $root;
		
		$this->linker->setCompiler($this);
	}
	
	public function compile()
	{
		$this->root->compile($this);
	}
	
	public function setArguments(array $args)
	{
		$this->args = array_merge($this->args, $args);
	}
	
	public function removeArguments(array $argKeys)
	{
		foreach ($argKeys as $k) {
			if (array_key_exists($k, $this->args)) unset($this->args[$k]);
		}
	}
	
	public function getArguments($argKeys=null)
	{
		if ($argKeys !== null and !is_string($argKeys) and !is_array($argKeys)) throw new \InvalidArgumentException('Argument $argKeys expected as string or array or NULL, but '.gettype($argKeys).' given.');
		if ($argKeys === null) return $this->args;
		
		if (is_string($argKeys)) $argKeys = array($argKeys);
		
		$result = array();
		foreach ($argKeys as $k) {
			if (array_key_exists($k, $this->args)) $result[$k] = $this->args[$k];
		}
		return $result;
	}
	
	/**
	 * Returns the linker passed to constructor
	 * @return Linker
	 */
	public function getLinker()
	{
		return $this->linker;
	}
}