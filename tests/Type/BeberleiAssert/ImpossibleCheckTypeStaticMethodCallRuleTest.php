<?php declare(strict_types = 1);

namespace PHPStan\Type\BeberleiAssert;

use PHPStan\Rules\Comparison\ImpossibleCheckTypeHelper;
use PHPStan\Rules\Comparison\ImpossibleCheckTypeStaticMethodCallRule;
use PHPStan\Rules\Rule;

/**
 * @extends \PHPStan\Testing\RuleTestCase<ImpossibleCheckTypeStaticMethodCallRule>
 */
class ImpossibleCheckTypeStaticMethodCallRuleTest extends \PHPStan\Testing\RuleTestCase
{

	protected function getRule(): Rule
	{
		return new ImpossibleCheckTypeStaticMethodCallRule(new ImpossibleCheckTypeHelper($this->createBroker(), $this->getTypeSpecifier(), [], true), true, true);
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
				'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.',
			],
		]);
	}

}
