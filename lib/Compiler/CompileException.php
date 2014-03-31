<?php

namespace ParzRKit\Compiler;

class CompileException extends \RuntimeException {
	public function __construct($message=null, $code=null, $previous=null)
	{
		parent::__construct(($message? 'Compile error: '.$message : null), $code, $previous);
	}
}