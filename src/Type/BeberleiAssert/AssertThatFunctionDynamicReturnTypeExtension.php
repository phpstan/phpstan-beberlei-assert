<?php declare(strict_types = 1);

namespace PHPStan\Type\BeberleiAssert;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\Type;
use function count;
use function in_array;

class AssertThatFunctionDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
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
		FuncCall $functionCall,
		Scope $scope
	): Type
	{
		if (count($functionCall->getArgs()) === 0) {
			return ParametersAcceptorSelector::selectSingle(
				$functionReflection->getVariants()
			)->getReturnType();
		}

		$valueExpr = $functionCall->getArgs()[0]->value;
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
