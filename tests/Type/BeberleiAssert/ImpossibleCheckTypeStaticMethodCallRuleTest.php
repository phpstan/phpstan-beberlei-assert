<?php declare(strict_types = 1);

namespace PHPStan\Type\BeberleiAssert;

use PHPStan\Rules\Comparison\ImpossibleCheckTypeStaticMethodCallRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<ImpossibleCheckTypeStaticMethodCallRule>
 */
class ImpossibleCheckTypeStaticMethodCallRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return self::getContainer()->getByType(ImpossibleCheckTypeStaticMethodCallRule::class);
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

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/../../../extension.neon',
			__DIR__ . '/../../../vendor/phpstan/phpstan-strict-rules/rules.neon',
		];
	}

}
