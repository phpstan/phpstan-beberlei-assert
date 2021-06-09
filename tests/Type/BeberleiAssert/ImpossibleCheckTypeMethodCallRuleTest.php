<?php declare(strict_types = 1);

namespace PHPStan\Type\BeberleiAssert;

use PHPStan\Rules\Comparison\ImpossibleCheckTypeMethodCallRule;
use PHPStan\Rules\Rule;

/**
 * @extends \PHPStan\Testing\RuleTestCase<ImpossibleCheckTypeMethodCallRule>
 */
class ImpossibleCheckTypeMethodCallRuleTest extends \PHPStan\Testing\RuleTestCase
{

	protected function getRule(): Rule
	{
		return self::getContainer()->getByType(ImpossibleCheckTypeMethodCallRule::class);
	}

	public function testExtension(): void
	{
		$this->analyse([__DIR__ . '/data/impossible-check.php'], [
			[
				'Call to method Assert\AssertionChain::string() will always evaluate to true.',
				12,
			],
			[
				'Call to method Assert\AssertionChain::string() will always evaluate to true.',
				16,
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
