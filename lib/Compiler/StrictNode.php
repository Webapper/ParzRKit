<?php

namespace ParzRKit\Compiler;

use ParzRKit\Compiler\Exception\NotAllowedException;
use ParzRKit\Compiler\Exception\NotMetException;
/**
 * StrictNode implements a Node which has some syntax-checking feature
 * 
 * @author assarte
 */
class StrictNode extends BasicNode
{
	/**
	 * @var array
	 */
	protected $allowedNodeClasses = array(
		'ParzRKit\Compiler\BasicNode'	=> true
	);
	
	/**
	 * @var array
	 */
	protected $mandatoryNodeClasses = array();
	
	/**
	 * {@inheritDoc}
	 */
	public function isAllowed()
	{
		if (!parent::isAllowed()) return false;
		
		$allowed = array_keys($this->allowedNodeClasses, true);
		$mandatory = array_keys($this->mandatoryNodeClasses, true);
		
		$it = new \RecursiveIteratorIterator(new RecursiveNodeIterator($this), \RecursiveIteratorIterator::SELF_FIRST);
		$achived = array();
		foreach ($it as $node) {
			if ($node === $this) continue;
			
			$isAllowed = false;
			foreach ($allowed as $classname) {
				if ($node instanceof $classname) {
					$isAllowed = true;
					break;
				}
			}
			
			if (!$isAllowed) throw new NotAllowedException($node, $this);
			
			foreach ($mandatory as $classname) {
				if ($node instanceof $classname) {
					$achived[$classname] = $classname;
					break;
				}
			}
			
			if (count($achived) == count($mandatory)) return true;
		}
		
		throw new NotMetException($this, $mandatory, $achived);
	}
	
	/**
	 * Make a Node-class allowed for now
	 * @param string $classname
	 * @throws \InvalidArgumentException
	 */
	public function allowNodeClass($classname)
	{
		if (!is_string($classname) or !class_exists($classname)) throw new \InvalidArgumentException('Argument $classname expected as a valid classname, but \''.(string)$classname.'\' is not a string or the related class does not exists.');
		
		$this->allowedNodeClasses[$classname] = true;
	}
	
	/**
	 * Make a Node-class disallowed for now
	 * @param string $classname
	 * @throws \InvalidArgumentException
	 */
	public function disallowNodeClass($classname)
	{
		if (!is_string($classname) or !class_exists($classname)) throw new \InvalidArgumentException('Argument $classname expected as a valid classname, but \''.(string)$classname.'\' is not a string or the related class does not exists.');
		
		$this->allowedNodeClasses[$classname] = false;
		if (isset($this->mandatoryNodeClasses[$classname])) $this->mandatoryNodeClasses[$classname] = false;
	}
	
	/**
	 * Sets a Node-class to mandatory
	 * @param string $classname
	 * @throws \InvalidArgumentException
	 */
	public function nodeClassIsMandatory($classname)
	{
		if (!is_string($classname) or !class_exists($classname)) throw new \InvalidArgumentException('Argument $classname expected as a valid classname, but \''.(string)$classname.'\' is not a string or the related class does not exists.');
		
		$this->allowNodeClass($classname);
		$this->mandatoryNodeClasses[$classname] = true;
	}
	
	/**
	 * Sets a mandatory Node-class to optional
	 * @param string $classname
	 * @throws \InvalidArgumentException
	 */
	public function nodeClassIsOptional($classname)
	{
		if (!is_string($classname) or !class_exists($classname)) throw new \InvalidArgumentException('Argument $classname expected as a valid classname, but \''.(string)$classname.'\' is not a string or the related class does not exists.');
		
		$this->mandatoryNodeClasses[$classname] = false;
	}
}