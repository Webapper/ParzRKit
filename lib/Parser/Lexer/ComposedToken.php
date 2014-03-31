<?php

namespace ParzRKit\Parser\Lexer;

class ComposedToken extends AbstractToken
{
	/**
	 * @var array
	 */
	protected $tokens = array();
	
	/**
	 * Constructor
	 * @param string $stream
	 * @param AbstractToken $initialToken
	 */
	public function __construct($stream, AbstractToken $initialToken)
	{
		parent::__construct($stream);
		$this->processed = '';
		$this->remaining = '';
		$this->setParent($initialToken->getParent());
		$this->addToken($initialToken);
	}
	
	/**
	 * {@inheritDoc}
	 */
	final public function identifyOpenTag()
	{
		$this->openTag = '';
		return true;
	}
	
	/**
	 * {@inheritDoc}
	 */
	final public function identifyCloseTag($inStream=null)
	{
		$this->closeTag = '';
		return true;
	}
	
	/**
	 * {@inheritDoc}
	 */
	public function guessCloseTag()
	{
		return null;
	}
	
	/**
	 * This method always returned with TRUE
	 * {@inheritDoc}
	 */
	public function isClosed()
	{
		return true;
	}
	
	/**
	 * Adds a Token under the composition
	 * @param AbstractToken $token
	 */
	public function addToken(AbstractToken $token)
	{
		if ($token->isDataStream() and $token->getStream() === '') return;
		
		$this->processed .= (string)$token;
		
		$this->tokens[] = $token;
	}
	
	/**
	 * {@inheritDoc}
	 */
	protected function getExtraData()
	{
		return array('composition'=>$this->tokens);
	}
}