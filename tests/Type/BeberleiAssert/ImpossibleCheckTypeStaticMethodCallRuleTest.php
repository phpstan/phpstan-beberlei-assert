<?php declare(strict_types = 1);

namespace PHPStan\Type\BeberleiAssert;

use PHPStan\Rules\Comparison\ImpossibleCheckTypeHelper;
use PHPStan\Rules\Comparison\ImpossibleCheckTypeStaticMethodCallRule;
use PHPStan\Rules\Rule;

class ImpossibleCheckTypeStaticMethodCallRuleTest extends \PHPStan\Testing\RuleTestCase
{

	protected function getRule(): Rule
	{
		return new ImpossibleCheckTypeStaticMethodCallRule(new ImpossibleCheckTypeHelper($this->createBroker(), $this->getTypeSpecifier()), true);
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
		$this->analyse([__DIR__ . '/data/impossible-static-check.php'], [
			[
				'Call to static method Assert\Assertion::string() with string will always evaluate to true.',
				12,
			],
			[
				'Call to static method Assert\Assertion::allString() with array<string> will always evaluate to true.',
				21,
			],
		]);
	}

}
