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

	/**
	 * @return \PHPStan\Type\MethodTypeSpecifyingExtension[]
	 */
	protected function getMethodTypeSpecifyingExtensions(): array
	{
		return [
			new AssertionChainTypeSpecifyingExtension(),
		];
	}

	/**
	 * @return \PHPStan\Type\DynamicMethodReturnTypeExtension[]
	 */
	public function getDynamicMethodReturnTypeExtensions(): array
	{
		return [
			new AssertionChainDynamicReturnTypeExtension(),
		];
	}

	/**
	 * @return \PHPStan\Type\DynamicStaticMethodReturnTypeExtension[]
	 */
	public function getDynamicStaticMethodReturnTypeExtensions(): array
	{
		return [
			new AssertThatDynamicMethodReturnTypeExtension(),
		];
	}

	/**
	 * @return \PHPStan\Type\DynamicFunctionReturnTypeExtension[]
	 */
	public function getDynamicFunctionReturnTypeExtensions(): array
	{
		return [
			new AssertThatFunctionDynamicReturnTypeExtension(),
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
				'Variable $l is: callable(): mixed',
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
				'Variable $z is: array(1, -2|2, -3|3)',
				104,
			],
			[
				'Variable $aa is: PHPStan\Type\BeberleiAssert\Foo|string',
				107,
			],
			[
				'Variable $ab is: array<PHPStan\Type\BeberleiAssert\Foo>',
				110,
			],
			[
				'Variable $ac is: string',
				113,
			],
			[
				'Variable $that is: Assert\AssertionChain<int|null>',
				119,
			],
			[
				'Variable $thatOrNull is: Assert\AssertionChain<int|null>',
				122,
			],
			[
				'Variable $a is: int',
				123,
			],
			[
				'Variable $assertNullOr is: Assert\AssertionChain<mixed>-nullOr',
				126,
			],
			[
				'Variable $b is: string|null',
				128,
			],
			[
				'Variable $assertNullOr2 is: Assert\AssertionChain<mixed>-nullOr',
				131,
			],
			[
				'Variable $c is: string|null',
				133,
			],
			[
				'Variable $assertAll is: Assert\AssertionChain<array>-all',
				136,
			],
			[
				'Variable $d is: array<string>',
				138,
			],
			[
				'Variable $assertAll2 is: Assert\AssertionChain<iterable>-all',
				141,
			],
			[
				'Variable $e is: iterable<string>',
				143,
			],
			[
				'Variable $f is: array<string>',
				148,
			],
			[
				'Variable $assertThatFunction is: Assert\AssertionChain<mixed>',
				151,
			],
			[
				'Variable $assertThatNullOrFunction is: Assert\AssertionChain<mixed>-nullOr',
				154,
			],
			[
				'Variable $assertThatAllFunction is: Assert\AssertionChain<mixed>-all',
				157,
			],
		]);
	}

}
