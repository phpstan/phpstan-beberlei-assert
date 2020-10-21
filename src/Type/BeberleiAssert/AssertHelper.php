<?php declare(strict_types = 1);

namespace PHPStan\Type\BeberleiAssert;

use PhpParser\Node\Arg;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\IterableType;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;

class AssertHelper
{

	/** @var \Closure[] */
	private static $resolvers;

	/**
	 * @param string $assertName
	 * @param \PhpParser\Node\Arg[] $args
	 * @return bool
	 */
	public static function isSupported(
		string $assertName,
		array $args
	): bool
	{
		$resolvers = self::getExpressionResolvers();

		if (!array_key_exists($assertName, $resolvers)) {
			return false;
		}

		$resolver = $resolvers[$assertName];
		$resolverReflection = new \ReflectionObject($resolver);

		return count($args) >= (count($resolverReflection->getMethod('__invoke')->getParameters()) - 1);
	}

	/**
	 * @param TypeSpecifier $typeSpecifier
	 * @param Scope $scope
	 * @param string $assertName
	 * @param \PhpParser\Node\Arg[] $args
	 * @param bool $nullOr
	 * @return SpecifiedTypes
	 */
	public static function specifyTypes(
		TypeSpecifier $typeSpecifier,
		Scope $scope,
		string $assertName,
		array $args,
		bool $nullOr
	): SpecifiedTypes
	{
		$expression = self::createExpression($scope, $assertName, $args);
		if ($expression === null) {
			return new SpecifiedTypes([], []);
		}

		if ($nullOr) {
			$expression = new \PhpParser\Node\Expr\BinaryOp\BooleanOr(
				$expression,
				new \PhpParser\Node\Expr\BinaryOp\Identical(
					$args[0]->value,
					new \PhpParser\Node\Expr\ConstFetch(new \PhpParser\Node\Name('null'))
				)
			);
		}

		return $typeSpecifier->specifyTypesInCondition(
			$scope,
			$expression,
			TypeSpecifierContext::createTruthy()
		);
	}

	public static function handleAll(
		TypeSpecifier $typeSpecifier,
		Scope $scope,
		SpecifiedTypes $specifiedTypes
	): SpecifiedTypes
	{
		if (count($specifiedTypes->getSureTypes()) > 0) {
			$sureTypes = $specifiedTypes->getSureTypes();
			reset($sureTypes);
			$exprString = key($sureTypes);
			$sureType = $sureTypes[$exprString];
			return self::arrayOrIterable($typeSpecifier, $scope, $sureType[0], function () use ($sureType): Type {
				return $sureType[1];
			});
		}
		if (count($specifiedTypes->getSureNotTypes()) > 0) {
			throw new \PHPStan\ShouldNotHappenException();
		}

		return $specifiedTypes;
	}

	/**
	 * @param TypeSpecifier $typeSpecifier
	 * @param Scope $scope
	 * @param string $assertName
	 * @param \PhpParser\Node\Arg[] $args
	 * @return SpecifiedTypes
	 */
	public static function handleAllNot(
		TypeSpecifier $typeSpecifier,
		Scope $scope,
		string $assertName,
		array $args
	): SpecifiedTypes
	{
		if ($assertName === 'notNull') {
			return self::arrayOrIterable(
				$typeSpecifier,
				$scope,
				$args[0]->value,
				function (Type $type): Type {
					return TypeCombinator::removeNull($type);
				}
			);
		}

		if ($assertName === 'notIsInstanceOf') {
			$classType = $scope->getType($args[1]->value);
			if (!$classType instanceof ConstantStringType) {
				return new SpecifiedTypes([], []);
			}

			$objectType = new ObjectType($classType->getValue());
			return self::arrayOrIterable(
				$typeSpecifier,
				$scope,
				$args[0]->value,
				function (Type $type) use ($objectType): Type {
					return TypeCombinator::remove($type, $objectType);
				}
			);
		}

		if ($assertName === 'notSame') {
			$valueType = $scope->getType($args[1]->value);
			return self::arrayOrIterable(
				$typeSpecifier,
				$scope,
				$args[0]->value,
				function (Type $type) use ($valueType): Type {
					return TypeCombinator::remove($type, $valueType);
				}
			);
		}

		throw new \PHPStan\ShouldNotHappenException();
	}

	private static function arrayOrIterable(
		TypeSpecifier $typeSpecifier,
		Scope $scope,
		\PhpParser\Node\Expr $expr,
		\Closure $typeCallback
	): SpecifiedTypes
	{
		$currentType = $scope->getType($expr);
		$arrayTypes = TypeUtils::getArrays($currentType);
		if (count($arrayTypes) > 0) {
			$newArrayTypes = [];
			foreach ($arrayTypes as $arrayType) {
				if ($arrayType instanceof ConstantArrayType) {
					$builder = ConstantArrayTypeBuilder::createEmpty();
					foreach ($arrayType->getKeyTypes() as $i => $keyType) {
						$valueType = $arrayType->getValueTypes()[$i];
						$builder->setOffsetValueType($keyType, $typeCallback($valueType));
					}
					$newArrayTypes[] = $builder->getArray();
				} else {
					$newArrayTypes[] = new ArrayType($arrayType->getKeyType(), $typeCallback($arrayType->getItemType()));
				}
			}

			$specifiedType = TypeCombinator::union(...$newArrayTypes);
		} elseif ((new IterableType(new MixedType(), new MixedType()))->isSuperTypeOf($currentType)->yes()) {
			$specifiedType = new IterableType($currentType->getIterableKeyType(), $typeCallback($currentType->getIterableValueType()));
		} else {
			return new SpecifiedTypes([], []);
		}

		return $typeSpecifier->create(
			$expr,
			$specifiedType,
			TypeSpecifierContext::createTruthy()
		);
	}

	/**
	 * @param Scope $scope
	 * @param string $assertName
	 * @param \PhpParser\Node\Arg[] $args
	 * @return \PhpParser\Node\Expr|null
	 */
	private static function createExpression(
		Scope $scope,
		string $assertName,
		array $args
	): ?\PhpParser\Node\Expr
	{
		$resolvers = self::getExpressionResolvers();
		$resolver = $resolvers[$assertName];

		return $resolver($scope, ...$args);
	}

	/**
	 * @return \Closure[]
	 */
	private static function getExpressionResolvers(): array
	{
		if (self::$resolvers === null) {
			self::$resolvers = [
				'integer' => function (Scope $scope, Arg $value): \PhpParser\Node\Expr {
					return new \PhpParser\Node\Expr\FuncCall(
						new \PhpParser\Node\Name('is_int'),
						[$value]
					);
				},
				'string' => function (Scope $scope, Arg $value): \PhpParser\Node\Expr {
					return new \PhpParser\Node\Expr\FuncCall(
						new \PhpParser\Node\Name('is_string'),
						[$value]
					);
				},
				'float' => function (Scope $scope, Arg $value): \PhpParser\Node\Expr {
					return new \PhpParser\Node\Expr\FuncCall(
						new \PhpParser\Node\Name('is_float'),
						[$value]
					);
				},
				'numeric' => function (Scope $scope, Arg $value): \PhpParser\Node\Expr {
					return new \PhpParser\Node\Expr\FuncCall(
						new \PhpParser\Node\Name('is_numeric'),
						[$value]
					);
				},
				'boolean' => function (Scope $scope, Arg $value): \PhpParser\Node\Expr {
					return new \PhpParser\Node\Expr\FuncCall(
						new \PhpParser\Node\Name('is_bool'),
						[$value]
					);
				},
				'scalar' => function (Scope $scope, Arg $value): \PhpParser\Node\Expr {
					return new \PhpParser\Node\Expr\FuncCall(
						new \PhpParser\Node\Name('is_scalar'),
						[$value]
					);
				},
				'objectOrClass' => function (Scope $scope, Arg $value): ?\PhpParser\Node\Expr {
					$valueType = $scope->getType($value->value);
					if ((new \PHPStan\Type\StringType())->isSuperTypeOf($valueType)->yes()) {
						return null;
					}

					return new \PhpParser\Node\Expr\FuncCall(
						new \PhpParser\Node\Name('is_object'),
						[$value]
					);
				},
				'isResource' => function (Scope $scope, Arg $value): \PhpParser\Node\Expr {
					return new \PhpParser\Node\Expr\FuncCall(
						new \PhpParser\Node\Name('is_resource'),
						[$value]
					);
				},
				'isCallable' => function (Scope $scope, Arg $value): \PhpParser\Node\Expr {
					return new \PhpParser\Node\Expr\FuncCall(
						new \PhpParser\Node\Name('is_callable'),
						[$value]
					);
				},
				'isArray' => function (Scope $scope, Arg $value): \PhpParser\Node\Expr {
					return new \PhpParser\Node\Expr\FuncCall(
						new \PhpParser\Node\Name('is_array'),
						[$value]
					);
				},
				'isInstanceOf' => function (Scope $scope, Arg $expr, Arg $class): ?\PhpParser\Node\Expr {
					$classType = $scope->getType($class->value);
					if (!$classType instanceof ConstantStringType) {
						return null;
					}

					return new \PhpParser\Node\Expr\Instanceof_(
						$expr->value,
						new \PhpParser\Node\Name($classType->getValue())
					);
				},
				'notIsInstanceOf' => function (Scope $scope, Arg $expr, Arg $class): ?\PhpParser\Node\Expr {
					$classType = $scope->getType($class->value);
					if (!$classType instanceof ConstantStringType) {
						return null;
					}

					return new \PhpParser\Node\Expr\BooleanNot(
						new \PhpParser\Node\Expr\Instanceof_(
							$expr->value,
							new \PhpParser\Node\Name($classType->getValue())
						)
					);
				},
				'true' => function (Scope $scope, Arg $expr): \PhpParser\Node\Expr {
					return new \PhpParser\Node\Expr\BinaryOp\Identical(
						$expr->value,
						new \PhpParser\Node\Expr\ConstFetch(new \PhpParser\Node\Name('true'))
					);
				},
				'false' => function (Scope $scope, Arg $expr): \PhpParser\Node\Expr {
					return new \PhpParser\Node\Expr\BinaryOp\Identical(
						$expr->value,
						new \PhpParser\Node\Expr\ConstFetch(new \PhpParser\Node\Name('false'))
					);
				},
				'null' => function (Scope $scope, Arg $expr): \PhpParser\Node\Expr {
					return new \PhpParser\Node\Expr\BinaryOp\Identical(
						$expr->value,
						new \PhpParser\Node\Expr\ConstFetch(new \PhpParser\Node\Name('null'))
					);
				},
				'notNull' => function (Scope $scope, Arg $expr): \PhpParser\Node\Expr {
					return new \PhpParser\Node\Expr\BinaryOp\NotIdentical(
						$expr->value,
						new \PhpParser\Node\Expr\ConstFetch(new \PhpParser\Node\Name('null'))
					);
				},
				'same' => function (Scope $scope, Arg $value1, Arg $value2): \PhpParser\Node\Expr {
					return new \PhpParser\Node\Expr\BinaryOp\Identical(
						$value1->value,
						$value2->value
					);
				},
				'notSame' => function (Scope $scope, Arg $value1, Arg $value2): \PhpParser\Node\Expr {
					return new \PhpParser\Node\Expr\BinaryOp\NotIdentical(
						$value1->value,
						$value2->value
					);
				},
				'subclassOf' => function (Scope $scope, Arg $expr, Arg $class): \PhpParser\Node\Expr {
					return new \PhpParser\Node\Expr\FuncCall(
						new \PhpParser\Node\Name('is_subclass_of'),
						[
							new Arg($expr->value),
							$class,
						]
					);
				},
				'isJsonString' => function (Scope $scope, Arg $value): \PhpParser\Node\Expr {
					return new \PhpParser\Node\Expr\FuncCall(
						new \PhpParser\Node\Name('is_string'),
						[$value]
					);
				},
			];
		}

		return self::$resolvers;
	}

}
