<?php

namespace ParzRKit\Parser;

use ParzRKit\Parser\Lexer\AbstractToken;

abstract class Lexer
{
	/**
	 * @var Lexer
	 */
	protected static $instance;
	
	/**
	 * @var array
	 */
	protected $tokeners = array();
	
	/**
	 * @var string
	 */
	protected $dataTokener = 'ParzRKit\Parser\Lexer\DataStreamToken';
	
	protected $composedTokener = 'ParzRKit\Parser\Lexer\ComposedToken';
	
	/**
	 * Protected constructor
	 */
	protected function __construct()
	{
		$this->registerTokeners();
	}
	
	/**
	 * Registering tokeners for this Lexer
	 */
	abstract protected function registerTokeners();
	
	/**
	 * Returns a singleton instance of Lexer
	 * @return Lexer
	 */
	public static function getLexer()
	{
		if (static::$instance !== null) return static::$instance;
		
		static::$instance = new static();
		
		return static::$instance;
	}
	
	/**
	 * Adds a tokener class to the Lexer
	 * @param string $tokenerClass
	 * @throws \InvalidArgumentException
	 * @return Lexer
	 */
	public function addTokener($tokenerClass)
	{
		if (!is_string($tokenerClass) or !class_exists($tokenerClass)) throw new \InvalidArgumentException('Argument $tokenerClass expected as a valid string contains a classname.');
		
		$this->tokeners[$tokenerClass] = $tokenerClass;
		
		return $this;
	}
	
	/**
	 * Sets the special data-stream token class which used to tokenize unidentifiable contents
	 * @param string $tokenerClass
	 * @throws \InvalidArgumentException
	 * @return Lexer
	 */
	public function setDataTokener($tokenerClass)
	{
		if (!is_string($tokenerClass) or !class_exists($tokenerClass)) throw new \InvalidArgumentException('Argument $tokenerClass expected as a valid string contains a classname.');
		
		$this->dataTokener = $tokenerClass;
		
		return $this;
	}
	
	/**
	 * Returns an array of all added tokener instances which are created newly
	 * @param string $stream
	 * @return array
	 */
	public function getCreatedTokeners($stream)
	{
		$result = array();
		foreach ($this->tokeners as $tokenerClass) {
			$result[$tokenerClass] = new $tokenerClass($stream);
		}
		return $result;
	}
	
	/**
	 * Returns a newly created instance of data-stream tokener
	 * @param string $stream
	 * @return AbstractToken
	 */
	public function getCreatedDataTokener($stream)
	{
		$tokenerClass = $this->dataTokener;
		return new $tokenerClass($stream);
	}
	
	/**
	 * Returns a newly created instance of data-stream tokener
	 * @param string $stream
	 * @param AbstractToken $initialItem First item of the composed token
	 * @return ComposedToken
	 */
	public function getCreatedComposedTokener($stream, AbstractToken $initialItem)
	{
		$tokenerClass = $this->composedTokener;
		return new $tokenerClass($stream, $initialItem);
	}
	
	/**
	 * Returns the data-stream tokener classname
	 * @return string
	 */
	public function getDataTokenerClass()
	{
		return $this->dataTokener;
	}
	
	/**
	 * Tokenizes (returns a Token) the stream contained by passed tokeners
	 * @param array $tokeners Created by getCreatedTokeners() method
	 * @param AbstractToken $dataTokener Created by getCreatedDataTokener() method
	 * @return AbstractToken
	 * @see getCreatedTokeners
	 * @see getCreatedDataTokener
	 */
	public static function tokenize(array $tokeners, AbstractToken $dataTokener)
	{
		foreach ($tokeners as $tokener) {
			if ($tokener->isProcessable()) {
				return $tokener;
			}
		}
		
		return $dataTokener;
	}
}