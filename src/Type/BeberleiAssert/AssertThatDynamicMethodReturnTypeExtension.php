<?php declare(strict_types = 1);

namespace PHPStan\Type\BeberleiAssert;

use PHPStan\Reflection\ParametersAcceptorSelector;

class AssertThatDynamicMethodReturnTypeExtension implements \PHPStan\Type\DynamicStaticMethodReturnTypeExtension
{

	public function getClass(): string
	{
		return 'Assert\Assert';
	}

	public function isStaticMethodSupported(\PHPStan\Reflection\MethodReflection $methodReflection): bool
	{
		return in_array($methodReflection->getName(), [
			'that',
			'thatNullOr',
			'thatAll',
		], true);
	}

	public function getTypeFromStaticMethodCall(\PHPStan\Reflection\MethodReflection $methodReflection, \PhpParser\Node\Expr\StaticCall $methodCall, \PHPStan\Analyser\Scope $scope): \PHPStan\Type\Type
	{
		if (count($methodCall->args) === 0) {
			return ParametersAcceptorSelector::selectSingle(
				$methodReflection->getVariants()
			)->getReturnType();
		}

		$valueExpr = $methodCall->args[0]->value;
		$type = new AssertThatType($valueExpr, $scope->getType($valueExpr));
		if ($methodReflection->getName() === 'thatNullOr') {
			return $type->toNullOr();
		}

		if ($methodReflection->getName() === 'thatAll') {
			return $type->toAll();
		}

		return $type;
	}

}
