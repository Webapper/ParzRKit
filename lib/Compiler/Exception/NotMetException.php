<?php

namespace ParzRKit\Compiler\Exception;

use ParzRKit\Compiler\CompileException;
use ParzRKit\Compiler\BasicNode;

class NotMetException extends CompileException
{
	public function __construct(BasicNode $node, array $mandatory, array $met)
	{
		$notmet = array_diff($mandatory, $met);
		parent::__construct('The node '.get_class($node).' has mandatory constraints which are not met: '.join(', ', $notmet));
	}
}