<?php

namespace ParzRKit\Parser\Lexer;

use ParzRKit\Parser\Lexer;

class DataStreamToken extends AbstractToken
{
	/**
	 * @var string
	 */
	protected $parentCloseTag;
	
	/**
	 * This will closes the Token regardless if it is processed or not
	 * @return DataStreamToken
	 */
	public function autoClose()
	{
		$this->closeTag = '';
		$this->processed = $this->stream;
		$this->remaining = '';
		$this->closed = true;
		
		return $this;
	}
	
	/**
	 * {@inheritDoc}
	 */
	public function isDataStream()
	{
		return true;
	}
	
	/**
	 * {@inheritDoc}
	 */
	public function identifyOpenTag()
	{
		$this->openTag = '';
		return true;
	}
	
	/**
	 * {@inheritDoc}
	 */
	public function identifyCloseTag($inStream=null)
	{
		$isBoundaryCome = $this->isBoundary($inStream);
		if (!$isBoundaryCome) return false;
		
		$this->closeTag = '';
		
		return true;
	}
	
	/**
	 * On data-streams, calculated lengths may contains the length of parent Token's closing tag too
	 * {@inheritDoc}
	 */
	public function getLength()
	{
		if (!$this->isClosed()) throw new \BadMethodCallException('This tokener seems unprocessed yet.');
		
		return strlen((string)$this.(string)$this->parentCloseTag);
	}
	
	/**
	 * {@inheritDoc}
	 */
	protected function iteration(Lexer $lexer)
	{
		$offset = 0;
		
		// if this is a data-stream token and parent closing tag found
		if ($this->getParent() !== null and $this->getParent()->identifyCloseTag(substr($this->getStream(), $this->getCursor()))) {
			$this->close(true); // Force closing this data-stream
			$this->parentCloseTag = $this->getParent()->getCloseTag();
			$this->remaining = '';
			return $offset;
		}
		
		// stop processing when simple closing sequence found
		if ($this->identifyCloseTag()) {
			$this->close();
			return $offset;
		}
		
		return $offset;
	}
}