<?php

namespace ParzRKit\Parser;

class ReturnToParentException extends \Exception
{
	public function __construct()
	{
		parent::__construct('Parent closing tag detected in child.');
	}
}