<?php declare(strict_types = 1);

namespace PHPStan\Type\BeberleiAssert;

use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierAwareExtension;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\StaticMethodTypeSpecifyingExtension;
use function array_key_exists;
use function count;
use function lcfirst;
use function substr;

class AssertTypeSpecifyingExtension implements StaticMethodTypeSpecifyingExtension, TypeSpecifierAwareExtension
{

	/** @var TypeSpecifier */
	private $typeSpecifier;

	public function setTypeSpecifier(TypeSpecifier $typeSpecifier): void
	{
		$this->typeSpecifier = $typeSpecifier;
	}

	public function getClass(): string
	{
		return 'Assert\Assertion';
	}

	public function isStaticMethodSupported(
		MethodReflection $staticMethodReflection,
		StaticCall $node,
		TypeSpecifierContext $context
	): bool
	{
		if (substr($staticMethodReflection->getName(), 0, 6) === 'allNot') {
			$methods = [
				'allNotIsInstanceOf' => 2,
				'allNotNull' => 1,
				'allNotSame' => 2,
				'allNotBlank' => 1,
			];
			return array_key_exists($staticMethodReflection->getName(), $methods)
				&& count($node->getArgs()) >= $methods[$staticMethodReflection->getName()];
		}

		$trimmedName = self::trimName($staticMethodReflection->getName());
		return AssertHelper::isSupported($trimmedName, $node->getArgs());
	}

	private static function trimName(string $name): string
	{
		if (substr($name, 0, 6) === 'nullOr') {
			$name = substr($name, 6);
		}
		if (substr($name, 0, 3) === 'all') {
			$name = substr($name, 3);
		}

		return lcfirst($name);
	}

	public function specifyTypes(
		MethodReflection $staticMethodReflection,
		StaticCall $node,
		Scope $scope,
		TypeSpecifierContext $context
	): SpecifiedTypes
	{
		if (substr($staticMethodReflection->getName(), 0, 6) === 'allNot') {
			return AssertHelper::handleAllNot(
				$this->typeSpecifier,
				$scope,
				lcfirst(substr($staticMethodReflection->getName(), 3)),
				$node->getArgs()
			);
		}

		$specifiedTypes = AssertHelper::specifyTypes(
			$this->typeSpecifier,
			$scope,
			self::trimName($staticMethodReflection->getName()),
			$node->getArgs(),
			substr($staticMethodReflection->getName(), 0, 6) === 'nullOr'
		);

		if (substr($staticMethodReflection->getName(), 0, 3) === 'all') {
			return AssertHelper::handleAll($this->typeSpecifier, $scope, $specifiedTypes);
		}

		return $specifiedTypes;
	}

}
