<?php declare(strict_types = 1);

namespace PHPStan\Type\BeberleiAssert;

use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierAwareExtension;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\MethodTypeSpecifyingExtension;

class AssertionChainTypeSpecifyingExtension implements MethodTypeSpecifyingExtension, TypeSpecifierAwareExtension
{

	/** @var \PHPStan\Analyser\TypeSpecifier */
	private $typeSpecifier;

	public function setTypeSpecifier(TypeSpecifier $typeSpecifier): void
	{
		$this->typeSpecifier = $typeSpecifier;
	}

	public function getClass(): string
	{
		return 'Assert\AssertionChain';
	}

	public function isMethodSupported(
		MethodReflection $methodReflection,
		\PhpParser\Node\Expr\MethodCall $node,
		TypeSpecifierContext $context
	): bool
	{
		return AssertHelper::isSupported(
			$methodReflection->getName(),
			array_merge(
				[new \PhpParser\Node\Arg(new \PhpParser\Node\Scalar\LNumber(1))],
				$node->getArgs()
			)
		);
	}

	public function specifyTypes(
		MethodReflection $methodReflection,
		\PhpParser\Node\Expr\MethodCall $node,
		Scope $scope,
		TypeSpecifierContext $context
	): SpecifiedTypes
	{
		$calledOnType = $scope->getType($node->var);
		if (!$calledOnType instanceof AssertThatType
			&& !$calledOnType instanceof AssertThatNullOrType
			&& !$calledOnType instanceof AssertThatAllType
		) {
			return new SpecifiedTypes();
		}

		$args = array_merge([
			new \PhpParser\Node\Arg($calledOnType->getValueExpr()),
		], $node->getArgs());

		if (
			$calledOnType instanceof AssertThatAllType
			&& substr($methodReflection->getName(), 0, 3) === 'not'
		) {
			return AssertHelper::handleAllNot(
				$this->typeSpecifier,
				$scope,
				$methodReflection->getName(),
				$args
			);
		}

		$types = AssertHelper::specifyTypes(
			$this->typeSpecifier,
			$scope,
			$methodReflection->getName(),
			$args,
			$calledOnType instanceof AssertThatNullOrType
		);

		if ($calledOnType instanceof AssertThatAllType) {
			return AssertHelper::handleAll($this->typeSpecifier, $scope, $types);
		}

		return $types;
	}

}
