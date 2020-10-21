<?php declare(strict_types = 1);

namespace PHPStan\Type\BeberleiAssert;

use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;

class AssertThatFunctionDynamicReturnTypeExtension implements \PHPStan\Type\DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(
		FunctionReflection $functionReflection
	): bool
	{
		return in_array($functionReflection->getName(), [
			'Assert\\that',
			'Assert\\thatNullOr',
			'Assert\\thatAll',
		], true);
	}

	public function getTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		\PhpParser\Node\Expr\FuncCall $functionCall,
		Scope $scope
	): \PHPStan\Type\Type
	{
		if (count($functionCall->args) === 0) {
			return ParametersAcceptorSelector::selectSingle(
				$functionReflection->getVariants()
			)->getReturnType();
		}

		$valueExpr = $functionCall->args[0]->value;
		$type = new AssertThatType($valueExpr, $scope->getType($valueExpr));
		if ($functionReflection->getName() === 'Assert\\thatNullOr') {
			return $type->toNullOr();
		}

		if ($functionReflection->getName() === 'Assert\\thatAll') {
			return $type->toAll();
		}

		return $type;
	}

}
