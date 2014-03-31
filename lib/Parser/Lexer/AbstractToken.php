<?php

namespace ParzRKit\Parser\Lexer;

use ParzRKit\Parser;
use ParzRKit\Compiler\BasicNode;
use ParzRKit\Parser\Lexer;

/**
 * AbstractToken implements a Token with all base functionality as the atomic part of a parse
 * 
 * Any stream passed for parsing will  
 * @author assarte
 */
abstract class AbstractToken
{
	const BOUNDARY_END = 1;
	const BOUNDARY_TOKEN = 2;
	
	/**
	 * @var string
	 */
	protected $stream;
	
	/**
	 * @var string
	 */
	protected $processed;
	
	/**
	 * @var string
	 */
	protected $remaining;
	
	/**
	 * @var string
	 */
	protected $openTag;
	
	/**
	 * @var string
	 */
	protected $closeTag;
	
	/**
	 * @var Parser
	 */
	protected $parser;
	
	/**
	 * @var AbstractToken
	 */
	protected $parent;
	
	/**
	 * @var BasicNode
	 */
	protected $node;

	/**
	 * @var Parser
	 */
	protected $subparser;
	
	/**
	 * @var int
	 */
	protected $cursor;
	
	/**
	 * @var bool
	 */
	protected $closed = false;
	
	/**
	 * Constructor
	 * @param string $stream
	 * @param Parser $parser
	 */
	public function __construct($stream)
	{
		$this->stream = $stream;
		$this->identifyOpenTag();
	}
	
	/**
	 * Detects open tag and stores it in $openTag if detected. 
	 * @return bool TRUE if opening tag detected, FALSE otherwise
	 */
	abstract public function identifyOpenTag();
	
	/**
	 * Detects close tag and stores it in $closeTag if detected.
	 * @param string $inStream Check the beginning of passed stream instead
	 * @return bool TRUE if closing tag detected, FALSE otherwise
	 */
	abstract public function identifyCloseTag($inStream=null);
	
	/**
	 * Opens this Token after a successful identification of opening tag
	 * @throws \BadMethodCallException If opening tag not identified
	 */
	public function open()
	{
		if ($this->getOpenTag() === null) throw new \BadMethodCallException('Opening tag not identifed, Token cannot be opened.');
		if ($this->processed !== null) throw new \LogicException('How many times do you need to open this Token?...');
		
		$this->processed = substr($this->getStream(true), strlen($this->getOpenTag()));
	}
	
	/**
	 * Closes this Token after a successful identification of closing tag
	 * @param bool $force
	 * @throws \BadMethodCallException If closing tag not identified
	 */
	public function close($force=false)
	{
		if (!$force) {
			if ($this->getCloseTag() === null) throw new \BadMethodCallException('Closing tag not identifed, Token cannot be closed.');
			if ($this->isClosed()) throw new \LogicException('How many times do you need to close this Token?...');
		}
		
		$this->remaining = (string)substr($this->getStream(), $this->getCursor() + strlen($this->getCloseTag()));
		$this->processed = substr($this->getStream(), 0, $this->getCursor());
		$this->closed = true;
	}
	
	/**
	 * Gets the actual position of the parsing cursor
	 * @return int
	 */
	public function getCursor()
	{
		return $this->cursor;
	}
	
	/**
	 * Sets the actual position of the parsing cursor
	 * @param int $pos
	 */
	public function setCursor($pos)
	{
		$this->cursor = $pos;
	}
	
	/**
	 * Guesses a close tag based on the opening tag for knowCloseTag and for parser
	 * @return string|null Use NULL if no closing tag guessable
	 */
	public function guessCloseTag()
	{
		if (!$this->identifyOpenTag()) throw new \BadMethodCallException('Opening tag not identified, unable to guess closing tag.');
	}
	
	/**
	 * Returns the detected opening tag
	 * @return string
	 */
	public function getOpenTag()
	{
		return $this->openTag;
	}
	
	/**
	 * Returns the detected closing tag
	 * @return string
	 */
	public function getCloseTag()
	{
		return $this->closeTag;
	}
	
	/**
	 * Returns the $stream or the $processed (if Token processed) stream
	 * @param bool $original Passing TRUE results that $stream will be returned 
	 * @return string
	 */
	public function getStream($original=false)
	{
		return ($original || $this->processed === null? $this->stream : $this->processed);
	}
	
	/**
	 * Gets the remaining stream after a succeeded knowCloseTag() call
	 * @return string
	 */
	public function getRemainingStream()
	{
		return $this->remaining;
	}

	/**
	 * Returns the sub-parser which owns the sub-token of this Token (if any)
	 * @return Parser
	 */
	public function getSubparser()
	{
		return $this->subparser;
	}
	
	/**
	 * Helper method for substr()'ing the $processed stream
	 * @param int $pos
	 * @param int $length
	 * @param string $inStream Use this stream instead of $processed
	 * @return string
	 */
	protected function getSubStream($pos, $length=null, $inStream=null)
	{
		if ($inStream === null) $inStream = $this->processed;
		if ($pos >= strlen($inStream)) return '';
		
		return substr($inStream, $pos, ($length === null? strlen($inStream) : $length));
	}
	
	/**
	 * Setting the parser of this Token
	 * @param Parser $parser
	 * @return AbstractToken
	 */
	public function setParser(Parser $parser)
	{
		$this->parser = $parser;
		return $this;
	}
	
	/**
	 * Returns the Parser used by process() method
	 * @return Parser|null
	 */
	public function getParser()
	{
		return $this->parser;
	}
	
	/**
	 * Sets the parent Token of this Token
	 * @param AbstractToken $token
	 * @return AbstractToken
	 */
	public function setParent(AbstractToken $token=null)
	{
		$this->parent = $token;
		return $this;
	}
	
	/**
	 * Returns the parent Token of this Token
	 * @return AbstractToken
	 */
	public function getParent()
	{
		return $this->parent;
	}
	
	/**
	 * Gets the Node of this Token
	 * @return BasicNode
	 */
	public function getNode()
	{
		if ($this->node !== null) return $this->node;
		
		$this->node = $this->createNode();
		
		return $this->node;
	}
	
	/**
	 * Gets the processed data
	 * @throws \BadMethodCallException
	 * @return array
	 */
	public function getProcessedData()
	{
		if (!$this->isClosed()) throw new \BadMethodCallException('This tokener seems unprocessed yet.');
		
		$data = array('content'=>(string)$this);
		$result = $this->getExtraData();
		
		return ($result? array_merge($data, $result) : $data);
	}
	
	/**
	 * Calculates and returns the length of this Token (Token must be closed)
	 * @throws \BadMethodCallException
	 * @return int
	 */
	public function getLength()
	{
		if (!$this->isClosed()) throw new \BadMethodCallException('This tokener seems unprocessed yet.');
		
		return strlen((string)$this);
	}
	
	/**
	 * Gets extra data contained by this Token (modifiers, arguments and etc. for example)
	 * @return array|null Must return NULL if no extra data
	 */
	protected function getExtraData()
	{
		return;
	}
	
	/**
	 * Returns TRUE if tokener is a data-stream tokener, FALSE otherwise
	 * @return bool
	 */
	public function isDataStream()
	{
		return false;
	}
	
	/**
	 * Gets whether the token is closed or not.
	 * 
	 * Use getRemaining() to access remaining part of the processed stream
	 * @return bool
	 */
	public function isClosed()
	{
		return $this->closed;
	}
	
	/**
	 * Returns that whether this Token is processable or not
	 * @return bool
	 */
	public function isProcessable()
	{
		return ($this->getOpenTag() !== null);
	}
	
	/**
	 * Detects boundary (new Token or end of stream) on the next position of the stream
	 * @param string $inStream Detecting boundary in the beginning of this stream instead
	 * @return bool
	 */
	public function isBoundary($inStream=null)
	{
		$pos = $this->getCursor();
		if ($inStream !== null) {
			$pos = 0;
		} else {
			$inStream = $this->processed;
		}
		if ($pos + 1 >= strlen($inStream)) return static::BOUNDARY_END;
		
		$stream = $this->getSubStream($pos, null, $inStream);
		$lexer = $this->getParser()->getLexer();
		$tokeners = $lexer->getCreatedTokeners($stream);
		$dataTker = $lexer->getCreatedDataTokener($stream);
		$token = Lexer::tokenize($tokeners, $dataTker);
	
		if (!$token->isDataStream()) return static::BOUNDARY_TOKEN;
	
		return false;
	}
	
	/**
	 * Creates and returns a Node for this token
	 * @return BasicNode
	 */
	protected function createNode()
	{
		return new BasicNode($this);
	}
	
	/**
	 * Processes the stream
	 * @param Parser $parser The processor Parser (if this is not a sub-process call)
	 * @return AbstractToken
	 * @throws \BadMethodCallException
	 * @throws \RuntimeException
	 */
	public function process(Parser $parser)
	{
		if ($this->isClosed()) throw new \BadMethodCallException('Token is closed already.');
		
		$this->parser = $parser;
		$lexer = $parser->getLexer();
		
		// start processing the stream
		$this->open();
		for ($pos = 0, $stream = $this->getStream(), $len = strlen($stream); $pos < $len; $pos++) {
			$this->setCursor($pos);
			$pos += $this->iteration($lexer);
			if ($this->isClosed()) {
				break;
			}
		}
		
		// check to throw an Exception if unclosed
		if (!$this->isClosed()) {
			$openTag = $this->getOpenTag();
			$closeTag = $this->guessCloseTag();
			throw new \RuntimeException('Unclosed '.($openTag? $openTag : get_class($this)).' token expects a closing '.($closeTag? $closeTag.' ' : '').'tag.');
		}
		
		return $this;
	}
	
	/**
	 * This is the core iteration of process() method
	 * @param Lexer $lexer
	 * @return int The returning position-offset for next iteration (will be incremented by 1)
	 */
	protected function iteration(Lexer $lexer)
	{
		$offset = 0;
		
		// stop processing when simple closing sequence found
		if ($this->identifyCloseTag()) {
			$this->close();
			return $offset;
		}
		
		// create a token on current position for checking if there's a sub-token
		$substream = $this->getSubStream($this->getCursor());
		$tokeners = $lexer->getCreatedTokeners($substream);
		$dataTker = $lexer->getCreatedDataTokener($substream);
		$token = Lexer::tokenize($tokeners, $dataTker);
		
		// check if a sub-token found
		if (!$token->isDataStream()) {
			// getting a new instance of used Parser
			$dataBefore = $this->getSubStream(0, $this->getCursor());
			$this->subparser = $this->getParser()
				->getNewInstance($substream)
				// parse the sub-token after setting its Token parent
				->parse($token->setParent($this), $dataBefore)
			;
			
			// incrementing the position of processing to the end of the sub-token's closing tag
			$offset = $this->subparser->getToken()->getLength();
			$this->setCursor($offset);
			$this->close(true);
			// next iteration increments position to continue the processing
		}
		
		return $offset;
	}
	
	public function __toString()
	{
		if (!$this->isClosed()) throw new \BadMethodCallException('This tokener seems unprocessed yet.');
		
		return $this->getOpenTag().$this->getStream().$this->getCloseTag();
	}
}