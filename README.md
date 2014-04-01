ParzRKit
========

ParzRKit is an abstract parser library written in PHP for future purposes

This is a part of a light-weight framework, Prometheus: http://webapper.vallalatiszolgaltatasok.hu/#!/prometheus
(language only in hungarian, sorry)

<a rel="license" href="http://creativecommons.org/licenses/by/4.0/"><img alt="Creative Commons License" style="border-width:0" src="http://i.creativecommons.org/l/by/4.0/88x31.png" /></a><br /><span xmlns:dct="http://purl.org/dc/terms/" property="dct:title">ParzRKit</span> by <a xmlns:cc="http://creativecommons.org/ns#" href="http://webapper.vallalatiszolgaltatasok.hu/#!/prometheus" property="cc:attributionName" rel="cc:attributionURL">Assarte D'Raven</a> is licensed under a <a rel="license" href="http://creativecommons.org/licenses/by/4.0/">Creative Commons Attribution 4.0 International License</a>.

How you help me
---------------

Feel free to use my lib, I hope that you may enjoy that and may help you on your better efficiency. Well, you should donate me some credits via PayPal if my help counts for you on your work:

<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=5KQ66J5DF97RA">
<img src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
</a>

...or give me some positive feedback on my e-mail adress (you can see that in my profile).

Thanks anyway!

How it works
------------

It's a quite easy way to use this lib basically:

1. Create a Lexer class which extends ParzRKit's Lexer, and create its Node classes
2. Create Linker class(es) which are extends ParzRKit's Linker - keep in mind that your Linker classes must fits your needs of your Node classes!
3. Nearly just Parser->parse()->compile()->link(); I said: *nearly*

This is a working example of usage:
```php
use ParzRKit\Parser\Lexer\AbstractToken;
use ParzRKit\Parser\Lexer;
use ParzRKit\Linker;
use ParzRKit\Parser;
use ParzRKit\Compiler;
use ParzRKit\Parser\Lexer\DataStreamToken;

define('SRC', realpath(dirname(dirname(__FILE__))).'/src/vendor/assarte/parzrkit/lib/');

require SRC.'Parser/Lexer/AbstractToken.php';
require SRC.'Parser/Lexer/ComposedToken.php';
require SRC.'Parser/Lexer/DataStreamToken.php';
require SRC.'Parser/ReturnToParentException.php';
require SRC.'Parser/Lexer.php';
require SRC.'Parser.php';
require SRC.'Linker.php';
require SRC.'Compiler/CompileException.php';
require SRC.'Compiler/Exception/NotAllowedException.php';
require SRC.'Compiler/Exception/NotMetException.php';
require SRC.'Compiler/BasicNode.php';
require SRC.'Compiler/StrictNode.php';
require SRC.'Compiler/RecursiveNodeIterator.php';
require SRC.'Compiler.php';

class TestToken extends DataStreamToken
{
	public function isDataStream()
	{
		return false;
	}
	
	public function identifyOpenTag()
	{
		if ($this->stream{0} == '/') {
			$this->openTag = '/';
		}
	}
}

class TestSubToken extends AbstractToken
{
	public function identifyOpenTag()
	{
		if ($this->stream{0} == '[') {
			$this->openTag = '[';
		}
	}

	public function identifyCloseTag($inStream=null)
	{
		$pos = $this->getCursor();
		if ($inStream !== null) {
			$pos = 0;
		} else {
			$inStream = $this->processed;
		}
		
		if ($inStream{$pos} == ']') {
			$this->closeTag = ']';
			return true;
		}

		return false;
	}

	public function guessCloseTag()
	{
		return ']';
	}
}

class TestLexer extends Lexer
{
	protected function registerTokeners()
	{
		$this->addTokener('TestToken');
		$this->addTokener('TestSubToken');
	}
}

class TestLinker extends Linker
{
	public function link()
	{
		$it = new RecursiveIteratorIterator(new RecursiveArrayIterator($this));
		foreach ($it as $link) {
			echo str_repeat('| ', $it->getDepth() - 1).'|-'.json_encode($link).'<br>';
		}
	}
}

$stream = '[876/t687][aa[bb[dd]]cc]';
$parser = new Parser($stream, TestLexer::getLexer());
$parser->parse();
$compiler = new Compiler(new TestLinker(), $parser->getToken()->getNode());
$compiler->compile();
echo $stream.'<br>';
$compiler->getLinker()->link();
```

The example above will print:
```
[876/t687][aa[bb[dd]]cc]
|-"[876\/t687]"
| |-"876"
| |-"\/t687"
|-"[aa[bb[dd]]cc]"
| |-"aa"
| |-"[bb[dd]]"
| | |-"bb"
| | |-"[dd]"
| |-"cc"
```
