<?php declare(strict_types = 1);

namespace PHPStan\Type\BeberleiAssert;

use Closure;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\BooleanOr;
use PhpParser\Node\Expr\BinaryOp\Identical;
use PhpParser\Node\Expr\BinaryOp\NotIdentical;
use PhpParser\Node\Expr\BooleanNot;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Instanceof_;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\IterableType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use ReflectionObject;
use function array_key_exists;
use function count;
use function key;
use function reset;

class AssertHelper
{

	/** @var Closure[] */
	private static $resolvers;

	/**
	 * @param Arg[] $args
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
		$resolverReflection = new ReflectionObject($resolver);

		return count($args) >= count($resolverReflection->getMethod('__invoke')->getParameters()) - 1;
	}

	/**
	 * @param Arg[] $args
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
			$expression = new BooleanOr(
				$expression,
				new Identical(
					$args[0]->value,
					new ConstFetch(new Name('null'))
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
			return self::allArrayOrIterable($typeSpecifier, $scope, $sureType[0], static function () use ($sureType): Type {
				return $sureType[1];
			});
		}
		if (count($specifiedTypes->getSureNotTypes()) > 0) {
			throw new ShouldNotHappenException();
		}

		return $specifiedTypes;
	}

	/**
	 * @param Arg[] $args
	 */
	public static function handleAllNot(
		TypeSpecifier $typeSpecifier,
		Scope $scope,
		string $assertName,
		array $args
	): SpecifiedTypes
	{
		if ($assertName === 'notNull') {
			return self::allArrayOrIterable(
				$typeSpecifier,
				$scope,
				$args[0]->value,
				static function (Type $type): Type {
					return TypeCombinator::removeNull($type);
				}
			);
		}

		if ($assertName === 'notIsInstanceOf') {
			$classType = $scope->getType($args[1]->value);
			$constantStrings = $classType->getConstantStrings();
			if (count($constantStrings) !== 1) {
				return new SpecifiedTypes([], []);
			}

			$objectType = new ObjectType($constantStrings[0]->getValue());
			return self::allArrayOrIterable(
				$typeSpecifier,
				$scope,
				$args[0]->value,
				static function (Type $type) use ($objectType): Type {
					return TypeCombinator::remove($type, $objectType);
				}
			);
		}

		if ($assertName === 'notSame') {
			$valueType = $scope->getType($args[1]->value);
			return self::allArrayOrIterable(
				$typeSpecifier,
				$scope,
				$args[0]->value,
				static function (Type $type) use ($valueType): Type {
					return TypeCombinator::remove($type, $valueType);
				}
			);
		}

		throw new ShouldNotHappenException();
	}

	private static function allArrayOrIterable(
		TypeSpecifier $typeSpecifier,
		Scope $scope,
		Expr $expr,
		Closure $typeCallback
	): SpecifiedTypes
	{
		$currentType = TypeCombinator::intersect($scope->getType($expr), new IterableType(new MixedType(), new MixedType()));
		$arrayTypes = $currentType->getArrays();
		if (count($arrayTypes) > 0) {
			$newArrayTypes = [];
			foreach ($arrayTypes as $arrayType) {
				$constantArrays = $arrayType->getConstantArrays();
				if (count($constantArrays) === 1) {
					$builder = ConstantArrayTypeBuilder::createEmpty();
					foreach ($constantArrays[0]->getKeyTypes() as $i => $keyType) {
						$valueType = $typeCallback($constantArrays[0]->getValueTypes()[$i]);
						if ($valueType instanceof NeverType) {
							continue 2;
						}
						$builder->setOffsetValueType($keyType, $valueType, $constantArrays[0]->isOptionalKey($i));
					}
					$newArrayTypes[] = $builder->getArray();
				} else {
					$itemType = $typeCallback($arrayType->getItemType());
					if ($itemType instanceof NeverType) {
						continue;
					}
					$newArrayTypes[] = new ArrayType($arrayType->getKeyType(), $itemType);
				}
			}

			$specifiedType = TypeCombinator::union(...$newArrayTypes);
		} elseif ((new IterableType(new MixedType(), new MixedType()))->isSuperTypeOf($currentType)->yes()) {
			$itemType = $typeCallback($currentType->getIterableValueType());
			if ($itemType instanceof NeverType) {
				$specifiedType = $itemType;
			} else {
				$specifiedType = new IterableType($currentType->getIterableKeyType(), $itemType);
			}
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
	 * @param Arg[] $args
	 */
	private static function createExpression(
		Scope $scope,
		string $assertName,
		array $args
	): ?Expr
	{
		$resolvers = self::getExpressionResolvers();
		$resolver = $resolvers[$assertName];

		return $resolver($scope, ...$args);
	}

	/**
	 * @return Closure[]
	 */
	private static function getExpressionResolvers(): array
	{
		if (self::$resolvers === null) {
			self::$resolvers = [
				'integer' => static function (Scope $scope, Arg $value): Expr {
					return new FuncCall(
						new Name('is_int'),
						[$value]
					);
				},
				'string' => static function (Scope $scope, Arg $value): Expr {
					return new FuncCall(
						new Name('is_string'),
						[$value]
					);
				},
				'float' => static function (Scope $scope, Arg $value): Expr {
					return new FuncCall(
						new Name('is_float'),
						[$value]
					);
				},
				'numeric' => static function (Scope $scope, Arg $value): Expr {
					return new FuncCall(
						new Name('is_numeric'),
						[$value]
					);
				},
				'boolean' => static function (Scope $scope, Arg $value): Expr {
					return new FuncCall(
						new Name('is_bool'),
						[$value]
					);
				},
				'scalar' => static function (Scope $scope, Arg $value): Expr {
					return new FuncCall(
						new Name('is_scalar'),
						[$value]
					);
				},
				'objectOrClass' => static function (Scope $scope, Arg $value): ?Expr {
					$valueType = $scope->getType($value->value);
					if ((new StringType())->isSuperTypeOf($valueType)->yes()) {
						return null;
					}

					return new FuncCall(
						new Name('is_object'),
						[$value]
					);
				},
				'isResource' => static function (Scope $scope, Arg $value): Expr {
					return new FuncCall(
						new Name('is_resource'),
						[$value]
					);
				},
				'isCallable' => static function (Scope $scope, Arg $value): Expr {
					return new FuncCall(
						new Name('is_callable'),
						[$value]
					);
				},
				'isArray' => static function (Scope $scope, Arg $value): Expr {
					return new FuncCall(
						new Name('is_array'),
						[$value]
					);
				},
				'isInstanceOf' => static function (Scope $scope, Arg $expr, Arg $class): ?Expr {
					$classType = $scope->getType($class->value);
					$constantStrings = $classType->getConstantStrings();
					if (count($constantStrings) !== 1) {
						return null;
					}

					return new Instanceof_(
						$expr->value,
						new Name($constantStrings[0]->getValue())
					);
				},
				'notIsInstanceOf' => static function (Scope $scope, Arg $expr, Arg $class): ?Expr {
					$classType = $scope->getType($class->value);
					$constantStrings = $classType->getConstantStrings();
					if (count($constantStrings) !== 1) {
						return null;
					}

					return new BooleanNot(
						new Instanceof_(
							$expr->value,
							new Name($constantStrings[0]->getValue())
						)
					);
				},
				'true' => static function (Scope $scope, Arg $expr): Expr {
					return new Identical(
						$expr->value,
						new ConstFetch(new Name('true'))
					);
				},
				'false' => static function (Scope $scope, Arg $expr): Expr {
					return new Identical(
						$expr->value,
						new ConstFetch(new Name('false'))
					);
				},
				'null' => static function (Scope $scope, Arg $expr): Expr {
					return new Identical(
						$expr->value,
						new ConstFetch(new Name('null'))
					);
				},
				'notNull' => static function (Scope $scope, Arg $expr): Expr {
					return new NotIdentical(
						$expr->value,
						new ConstFetch(new Name('null'))
					);
				},
				'same' => static function (Scope $scope, Arg $value1, Arg $value2): Expr {
					return new Identical(
						$value1->value,
						$value2->value
					);
				},
				'notSame' => static function (Scope $scope, Arg $value1, Arg $value2): Expr {
					return new NotIdentical(
						$value1->value,
						$value2->value
					);
				},
				'subclassOf' => static function (Scope $scope, Arg $expr, Arg $class): Expr {
					return new FuncCall(
						new Name('is_subclass_of'),
						[
							new Arg($expr->value),
							$class,
						]
					);
				},
				'isJsonString' => static function (Scope $scope, Arg $value): Expr {
					return new FuncCall(
						new Name('is_string'),
						[$value]
					);
				},
				'integerish' => static function (Scope $scope, Arg $value): Expr {
					return new FuncCall(
						new Name('is_numeric'),
						[$value]
					);
				},
				'keyExists' => static function (Scope $scope, Arg $array, Arg $key): Expr {
					return new FuncCall(
						new Name('array_key_exists'),
						[$key, $array]
					);
				},
				'keyNotExists' => static function (Scope $scope, Arg $array, Arg $key): Expr {
					return new BooleanNot(
						new FuncCall(
							new Name('array_key_exists'),
							[$key, $array]
						)
					);
				},
			];
		}

		return self::$resolvers;
	}

}
