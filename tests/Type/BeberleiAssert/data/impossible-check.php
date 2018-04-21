<?php

namespace ImpossibleCheck;

use Assert\Assert;

class Bar
{

	public function doBar(string $s)
	{
		Assert::that($s)->string();

		/** @var string[] $strings */
		$strings = doFoo();
		Assert::that($strings)->all()->string();
	}

}
