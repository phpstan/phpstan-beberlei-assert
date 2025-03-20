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

	public function nonEmptyStringAndSomethingUnknownNarrow(string $a, array $b): void
	{
		Assert::that($a)->isJsonString();
		Assert::that($a)->isJsonString(); // only this should report

		Assert::thatAll($b)->isJsonString();
		Assert::thatAll($b)->isJsonString(); // only this should report
	}
}
