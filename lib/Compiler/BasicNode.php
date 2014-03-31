<?php

namespace ParzRKit\Compiler;

use ParzRKit\Parser\Lexer\AbstractToken;
use ParzRKit\Parser\Lexer\ComposedToken;
use ParzRKit\Compiler\Exception\NotAllowedException;
use ParzRKit\Compiler;
use ParzRKit\Linker;

/**
 * BasicNode implements a Node which has no extra features
 * 
 * @author assarte
 */
class BasicNode
{
	/**
	 * @var AbstractToken
	 */
	protected $token;
	
	/**
	 * @var BasicNode|array
	 */
	protected $nodes = 0;
	
	/**
	 * Constructor
	 * @param AbstractToken $token
	 */
	public function __construct(AbstractToken $token)
	{
		$this->token = $token;
	}
	
	/**
	 * Returns the Token passed to constructor
	 * @return AbstractToken
	 */
	public function getToken()
	{
		return $this->token;
	}
	
	/**
	 * Compiling this Token and its sub-tokens
	 * @param Compiler $compiler To access the arguments of the Compiler used
	 */
	public function compile(Compiler $compiler)
	{
		$linker = $compiler->getLinker();
		if (!($this->getToken() instanceof ComposedToken)) {
			$this->make($compiler, $linker);
			if ($this->getToken()->getSubparser() !== null) {
				$linker = $linker->appendChild();
			}
		}
		
		$stackIt = array();
		$stackLinker = array();
		
		$it = new RecursiveNodeIterator($this);
		
		while ($it->valid()) {
			$it->current()->make($compiler, $linker);
			
			if ($it->hasChildren()) {
				$stackIt[] = $it;
				$stackLinker[] = $linker;
				$it = $it->getChildren();
				$linker = $linker->appendChild();
				continue;
			}
			$it->next();
			// stepping back with a level is neccessary if no more items here when stack is not empty
			while (!$it->valid() and count($stackIt) > 0) {
				$it = array_pop($stackIt);	// pop the stacks
				$linker = array_pop($stackLinker);
				$it->next();				// skip next item on popped
			}
		}
	}
	
	/**
	 * Make the compile-magic, build the links on the passed level of the Linker
	 * @param Compiler $compiler To access the arguments and Linker of the Compiler used
	 * @param Linker $linker To appending that on the actual level if it is neccessary
	 */
	public function make(Compiler $compiler, Linker $linker)
	{
		if (!$this->isAllowed()) {
			throw new NotAllowedException($this, $this->getParentNode());
		}
		
		// Checking if this method is not overridden when it called
		// This is just for debugging/testing purposes
		if (get_class($this) == get_class()) {
			$linker->append($this->getToken()->getProcessedData());
		}
	}
	
	/**
	 * Checks wheter this Node is allowed by some points of compiling or not
	 * @return bool
	 */
	public function isAllowed()
	{
		return true;
	}
	
	/**
	 * Returns this Node and its sub-nodes recursively
	 * @return AbstractToken|array|null
	 */
	public function getNodes()
	{
		if ($this->nodes !== 0) return $this->nodes;
		
		// Get the generator Token's sub-parser
		$token = $this->getToken(); 
		$subparser = $token->getSubparser();
		
		// check if Parser has a sub-parser (contains a sub-token)
		if ($subparser !== null or $token instanceof ComposedToken) {
			// get the sub-token if neccessary
			if ($subparser !== null) {
				$token = $subparser->getToken();
			}
			
			// check if Token has a composed sub-token
			if (is_object($token) and $token instanceof ComposedToken) {
				$data = $token->getProcessedData();
				// check if Token has some sub-token
				if (isset($data['composition']) and count($data['composition']) > 0) {
					$this->nodes = array();
					foreach ($data['composition'] as $token) {
						/** @var $token AbstractToken */
						$node = $token->getNode();
						$this->nodes[] = $node;
					}
				} else {
					$this->nodes = null;	// It's strange...
				}
			} else {
				// not a composed token or NULL
				$this->nodes = null;
				if ($token !== null) {
					$this->nodes = $token->getNode();
				}
			}
		} else {
			$this->nodes = null;
		}
		
		return $this->nodes;
	}
	
	/**
	 * Returns the parent Node if presented, NULL otherwise
	 * @return BasicNode|null
	 */
	public function getParentNode()
	{
		$parent = $this->getToken()->getParent();
		return ($parent? $parent->getNode() : null);
	}
	
	/**
	 * Returns the first occurenced parent node which matched by the $isValid criteria
	 * @param string|array|callable $isValid Name or list of classnames, or a callback(BasicNode $parentNode):bool
	 * Closure or static method reference that checks the validity of the given Node
	 * @param number $level Preserved mainly for continous searching
	 * @throws \InvalidArgumentException
	 * @return BasicNode|null Returns NULL if no matched parent node found
	 */
	public function getFirstParentNode($isValid, &$level=0)
	{
		$isValid = $this->createIsValidCallback($isValid);
		$startLevel = $level;
		$node = $this->getParentNode();
		while ($node !== null) {
			if ($level >= $startLevel) {
				if ($isValid($node)) return $node;
			}
			$level++;
			$node = $node->getParentNode();
		}
	}
	
	/**
	 * Returns the first occurenced child node which matched by the $isValid criteria
	 * @param string|array|callable $isValid Name or list of classnames, or a callback(BasicNode $childNode):bool
	 * Closure or static method reference that checks the validity of the given Node
	 * @param BasicNode $node Preserved mainly for continous searching (there's no step-back)
	 * @throws \InvalidArgumentException
	 * @return BasicNode|null Returns NULL if no matched parent node found
	 */
	public function getFirstChildNode($isValid, BasicNode $node=null)
	{
		$isValid = $this->createIsValidCallback($isValid);
		if ($node === null) $node = $this;
		$stack = array();
		
		$it = new RecursiveNodeIterator($node);
		while ($it->valid()) {
			if ($isValid($it->current())) return $it->current();
			
			if ($it->hasChildren()) {
				$stack[] = $it;
				$it = $it->getChildren();
				continue;
			}
			$it->next();
			// stepping back with a level is neccessary if no more items here when stack is not empty
			while (!$it->valid() and count($stack) > 0) {
				$it = array_pop($stack);	// pop the stack
				$it->next();				// skip next on the stack
			}
		}
	}
	
	protected function createIsValidCallback($isValid)
	{
		if (!is_string($isValid) and !is_array($isValid) and !is_callable($isValid)) throw new \InvalidArgumentException('Argument $isValid expected as string or array or callback, but '.gettype($isValid).' given.');
		
		if (is_string($isValid)) $isValid = array($isValid);
		if (is_array($isValid)) {
			$fn = function(BasicNode $node) use ($isValid) {
				foreach ($isValid as $classname) {
					if ($node instanceof $classname) return true;
				}
				return false;
			};
			$isValid = $fn;
		}
		
		return $isValid;
	}
}