<?php

namespace ParzRKit\Compiler\Exception;

use ParzRKit\Compiler\CompileException;
use ParzRKit\Compiler\BasicNode;

class NotAllowedException extends CompileException
{
	public function __construct(BasicNode $expectationalNode, BasicNode $inNode=null)
	{
		parent::__construct('The node ('.get_class($expectationalNode).') is not allowed '.($inNode !== null? 'in a '.get_class($inNode) : 'as the root').' node.');
	}
}