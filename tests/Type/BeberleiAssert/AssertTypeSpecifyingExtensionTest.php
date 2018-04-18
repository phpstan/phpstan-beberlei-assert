<?php declare(strict_types = 1);

namespace PHPStan\Type\BeberleiAssert;

use PHPStan\Rules\Rule;

class AssertTypeSpecifyingExtensionTest extends \PHPStan\Testing\RuleTestCase
{

	protected function getRule(): Rule
	{
		return new VariableTypeReportingRule();
	}

	/**
	 * @return \PHPStan\Type\StaticMethodTypeSpecifyingExtension[]
	 */
	protected function getStaticMethodTypeSpecifyingExtensions(): array
	{
		return [
			new AssertTypeSpecifyingExtension(),
		];
	}

	public function testExtension(): void
	{
		$this->analyse([__DIR__ . '/data/data.php'], [
			[
				'Variable $a is: mixed',
				12,
			],
			[
				'Variable $a is: int',
				15,
			],
			[
				'Variable $b is: int|null',
				18,
			],
			[
				'Variable $c is: array<int>',
				21,
			],
			[
				'Variable $d is: iterable<int>',
				24,
			],
			[
				'Variable $e is: string',
				27,
			],
			[
				'Variable $f is: float',
				30,
			],
			[
				'Variable $g is: float|int|string',
				33,
			],
			[
				'Variable $h is: bool',
				36,
			],
			[
				'Variable $i is: bool|float|int|string',
				39,
			],
			[
				'Variable $j is: object',
				42,
			],
			[
				'Variable $k is: resource',
				45,
			],
			[
				'Variable $l is: callable',
				48,
			],
			[
				'Variable $m is: array',
				51,
			],
			[
				'Variable $n is: string',
				54,
			],
			[
				'Variable $p is: PHPStan\Type\BeberleiAssert\Foo',
				57,
			],
			[
				'Variable $q is: PHPStan\Type\BeberleiAssert\Foo',
				62,
			],
			[
				'Variable $r is: true',
				65,
			],
			[
				'Variable $s is: false',
				68,
			],
			[
				'Variable $t is: null',
				71,
			],
			[
				'Variable $u is: int',
				74,
			],
			[
				'Variable $v is: array<PHPStan\Type\BeberleiAssert\Foo>',
				79,
			],
			[
				'Variable $w is: array<int>',
				84,
			],
			[
				'Variable $x is: 1',
				87,
			],
			[
				'Variable $y is: -1|1',
				95,
			],
			[
				'Variable $y is: -1',
				97,
			],
			[
				'Variable $z is: array<0|1|2, 1>',
				104,
			],
			[
				'Variable $aa is: PHPStan\Type\BeberleiAssert\Foo',
				107,
			],
			[
				'Variable $ab is: array<PHPStan\Type\BeberleiAssert\Foo>',
				110,
			],
		]);
	}

}
