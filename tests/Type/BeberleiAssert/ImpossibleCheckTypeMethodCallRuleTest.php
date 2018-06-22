<?php declare(strict_types = 1);

namespace PHPStan\Type\BeberleiAssert;

use PHPStan\Rules\Comparison\ImpossibleCheckTypeHelper;
use PHPStan\Rules\Comparison\ImpossibleCheckTypeMethodCallRule;
use PHPStan\Rules\Rule;

class ImpossibleCheckTypeMethodCallRuleTest extends \PHPStan\Testing\RuleTestCase
{

	protected function getRule(): Rule
	{
		return new ImpossibleCheckTypeMethodCallRule(new ImpossibleCheckTypeHelper($this->getTypeSpecifier()), true);
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

	public function testExtension(): void
	{
		self::markTestIncomplete('Does not work yet - needs to rework ImpossibleCheckType rules, dynamic return type extension now has priority when resolving return type.');
		$this->analyse([__DIR__ . '/data/impossible-check.php'], []);
	}

}
