<?php

namespace ImpossibleCheck;

use Assert\Assertion;

class Foo
{

	public function doFoo(string $a, array $b)
	{
		Assertion::string($a);
		Assertion::allString($b);
	}

	/**
	 * @param string[] $strings
	 */
	public function doBar(array $strings)
	{
		Assertion::allString($strings);
	}

}
