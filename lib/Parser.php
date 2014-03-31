<?php

namespace ParzRKit;

use ParzRKit\Parser\Lexer;
use ParzRKit\Parser\Lexer\AbstractToken;
use ParzRKit\Parser\Lexer\ComposedToken;

class Parser
{
	/**
	 * @var Lexer
	 */
	protected $lexer;
	
	/**
	 * @var string
	 */
	protected $stream;
	
	/**
	 * @var AbstractToken
	 */
	protected $token;
	
	/**
	 * Constructor
	 * @param string $stream
	 * @param Lexer $lexer
	 */
	public function __construct($stream, Lexer $lexer)
	{
		$this->stream = $stream;
		$this->lexer = $lexer;
	}
	
	/**
	 * Returns the Lexer used by this Parser
	 * @return Lexer
	 */
	public function getLexer()
	{
		return $this->lexer;
	}
	
	/**
	 * Creates a new instance of this Parser with the given stream and returns that
	 * @param string $stream
	 * @return Parser
	 */
	public function getNewInstance($stream)
	{
		return new static($stream, $this->lexer);
	}
	
	/**
	 * Parses the Token passed or creates a root Token based on the $stream of this Parser
	 * @param AbstractToken $token
	 * @param string $dataBefore
	 * @return Parser
	 * @throws \RuntimeException
	 */
	public function parse(AbstractToken $token=null, $dataBefore=null)
	{
		// If this is the root parser
		if ($token === null) {
			$tokeners = $this->lexer->getCreatedTokeners($this->stream);
			$dataTker = $this->lexer->getCreatedDataTokener($this->stream);
			$token = Lexer::tokenize($tokeners, $dataTker);
			
			$this->token = $token;
		} else if ($this->token === null) {
			$this->token = $this->lexer->getCreatedDataTokener($dataBefore)
				->setParent($token->getParent())
				->autoClose()
			;
			$this->token = $this->composeToken($this->token->getStream(true));
		}
		
		// processing the Token
		$token->process($this);
		if ($this->token instanceof ComposedToken) {
			$this->token->addToken($token);
		}
		
		// start parsing the identical level of remaining stream
		$remaining = $token->getRemainingStream();
		while ($remaining !== '') {
			// create the appropiate Token by remaining stream
			$tokeners = $this->lexer->getCreatedTokeners($remaining);
			$dataTker = $this->lexer->getCreatedDataTokener($remaining);
			$token = Lexer::tokenize($tokeners, $dataTker)
				->setParent($this->token->getParent())
				// process the new Token and getting whether if it is closed or not
				->process($this)
			;
			
			// switch to composed token first if more processable data found in stream
			if (!($this->token instanceof ComposedToken)) {
				$this->token = $this->composeToken($this->token->getStream(true));
			}
			
			// add this new Token to the composition
			$this->token->addToken($token);
			
			// getting new state of remaining stream based on processed Token above
			$remaining = $token->getRemainingStream();
		}
		
		return $this;
	}
	
	/**
	 * Gets the Token used by this Parser
	 * @return AbstractToken
	 */
	public function getToken()
	{
		return $this->token;
	}
	
	/**
	 * Switches the $token to a ComposedToken
	 * @param stream $stream
	 */
	public function composeToken($stream)
	{
		$this->token = $this->lexer->getCreatedComposedTokener($stream, $this->getToken());
		$this->token->setParser($this);
		
		return $this->token;
	}
}