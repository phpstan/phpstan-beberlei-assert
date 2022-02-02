<?php declare(strict_types = 1);

namespace PHPStan\Type\BeberleiAssert;

use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\Type;
use function count;
use function in_array;

class AssertThatDynamicMethodReturnTypeExtension implements DynamicStaticMethodReturnTypeExtension
{

	public function getClass(): string
	{
		return 'Assert\Assert';
	}

	public function isStaticMethodSupported(MethodReflection $methodReflection): bool
	{
		return in_array($methodReflection->getName(), [
			'that',
			'thatNullOr',
			'thatAll',
		], true);
	}

	public function getTypeFromStaticMethodCall(MethodReflection $methodReflection, StaticCall $methodCall, Scope $scope): Type
	{
		if (count($methodCall->getArgs()) === 0) {
			return ParametersAcceptorSelector::selectSingle(
				$methodReflection->getVariants()
			)->getReturnType();
		}

		$valueExpr = $methodCall->getArgs()[0]->value;
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
