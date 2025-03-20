<?php declare(strict_types = 1);

namespace PHPStan\Type\BeberleiAssert;

use Closure;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\BinaryOp\BooleanAnd;
use PhpParser\Node\Expr\BinaryOp\BooleanOr;
use PhpParser\Node\Expr\BinaryOp\Identical;
use PhpParser\Node\Expr\BinaryOp\NotIdentical;
use PhpParser\Node\Expr\BooleanNot;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Instanceof_;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\IterableType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use ReflectionObject;
use function array_key_exists;
use function count;
use function is_array;
use function key;
use function reset;

class AssertHelper
{

	/** @var Closure[]|null */
	private static ?array $resolvers = null;

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
		$resolverReflection = new ReflectionObject(Closure::fromCallable($resolver));

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
		[$expression, $rootExpr] = self::createExpression($scope, $assertName, $args);
		if ($expression === null) {
			return new SpecifiedTypes([], []);
		}

		if ($nullOr) {
			$expression = new BooleanOr(
				$expression,
				new Identical(
					$args[0]->value,
					new ConstFetch(new Name('null')),
				),
			);
		}

		$specifiedTypes = $typeSpecifier->specifyTypesInCondition(
			$scope,
			$expression,
			TypeSpecifierContext::createTruthy(),
		)->setRootExpr($rootExpr ?? $expression);

		return self::specifyRootExprIfSet($rootExpr, $scope, $specifiedTypes, $typeSpecifier);
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
			return self::allArrayOrIterable($typeSpecifier, $scope, $sureType[0], static fn (): Type => $sureType[1]);
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
				static fn (Type $type): Type => TypeCombinator::removeNull($type),
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
				static fn (Type $type): Type => TypeCombinator::remove($type, $objectType),
			);
		}

		if ($assertName === 'notSame') {
			$valueType = $scope->getType($args[1]->value);
			return self::allArrayOrIterable(
				$typeSpecifier,
				$scope,
				$args[0]->value,
				static fn (Type $type): Type => TypeCombinator::remove($type, $valueType),
			);
		}

		if ($assertName === 'notBlank') {
			return self::allArrayOrIterable(
				$typeSpecifier,
				$scope,
				$args[0]->value,
				static fn (Type $type): Type => TypeCombinator::remove(
					$type,
					new UnionType([
						new NullType(),
						new ConstantBooleanType(false),
						new ConstantStringType(''),
						new ConstantArrayType([], []),
					]),
				),
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
			TypeSpecifierContext::createTruthy(),
			$scope,
		);
	}

	/**
	 * @param Arg[] $args
	 * @return array{?Expr, ?Expr}
	 */
	private static function createExpression(
		Scope $scope,
		string $assertName,
		array $args
	): array
	{
		$resolvers = self::getExpressionResolvers();
		$resolver = $resolvers[$assertName];

		$resolverResult = $resolver($scope, ...$args);
		if (is_array($resolverResult)) {
			[$expr, $rootExpr] = $resolverResult;
		} else {
			$expr = $resolverResult;
			$rootExpr = null;
		}

		if ($expr === null) {
			return [null, null];
		}

		return [$expr, $rootExpr];
	}

	/**
	 * @return array<string, callable(Scope, Arg...): (Expr|array{?Expr, ?Expr}|null)>
	 */
	private static function getExpressionResolvers(): array
	{
		if (self::$resolvers === null) {
			self::$resolvers = [
				'integer' => static fn (Scope $scope, Arg $value): Expr => new FuncCall(
					new Name('is_int'),
					[$value],
				),
				'string' => static fn (Scope $scope, Arg $value): Expr => new FuncCall(
					new Name('is_string'),
					[$value],
				),
				'float' => static fn (Scope $scope, Arg $value): Expr => new FuncCall(
					new Name('is_float'),
					[$value],
				),
				'numeric' => static fn (Scope $scope, Arg $value): Expr => new FuncCall(
					new Name('is_numeric'),
					[$value],
				),
				'boolean' => static fn (Scope $scope, Arg $value): Expr => new FuncCall(
					new Name('is_bool'),
					[$value],
				),
				'scalar' => static fn (Scope $scope, Arg $value): Expr => new FuncCall(
					new Name('is_scalar'),
					[$value],
				),
				'objectOrClass' => static function (Scope $scope, Arg $value): ?Expr {
					$valueType = $scope->getType($value->value);
					if ((new StringType())->isSuperTypeOf($valueType)->yes()) {
						return null;
					}

					return new FuncCall(
						new Name('is_object'),
						[$value],
					);
				},
				'isResource' => static fn (Scope $scope, Arg $value): Expr => new FuncCall(
					new Name('is_resource'),
					[$value],
				),
				'isCallable' => static fn (Scope $scope, Arg $value): Expr => new FuncCall(
					new Name('is_callable'),
					[$value],
				),
				'isArray' => static fn (Scope $scope, Arg $value): Expr => new FuncCall(
					new Name('is_array'),
					[$value],
				),
				'isInstanceOf' => static function (Scope $scope, Arg $expr, Arg $class): ?Expr {
					$classType = $scope->getType($class->value);
					$constantStrings = $classType->getConstantStrings();
					if (count($constantStrings) !== 1) {
						return null;
					}

					return new Instanceof_(
						$expr->value,
						new Name($constantStrings[0]->getValue()),
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
							new Name($constantStrings[0]->getValue()),
						),
					);
				},
				'true' => static fn (Scope $scope, Arg $expr): Expr => new Identical(
					$expr->value,
					new ConstFetch(new Name('true')),
				),
				'false' => static fn (Scope $scope, Arg $expr): Expr => new Identical(
					$expr->value,
					new ConstFetch(new Name('false')),
				),
				'null' => static fn (Scope $scope, Arg $expr): Expr => new Identical(
					$expr->value,
					new ConstFetch(new Name('null')),
				),
				'notNull' => static fn (Scope $scope, Arg $expr): Expr => new NotIdentical(
					$expr->value,
					new ConstFetch(new Name('null')),
				),
				'same' => static fn (Scope $scope, Arg $value1, Arg $value2): Expr => new Identical(
					$value1->value,
					$value2->value,
				),
				'notSame' => static fn (Scope $scope, Arg $value1, Arg $value2): Expr => new NotIdentical(
					$value1->value,
					$value2->value,
				),
				'subclassOf' => static fn (Scope $scope, Arg $expr, Arg $class): Expr => new FuncCall(
					new Name('is_subclass_of'),
					[
						new Arg($expr->value),
						$class,
					],
				),
				'integerish' => static fn (Scope $scope, Arg $value): Expr => new FuncCall(
					new Name('is_numeric'),
					[$value],
				),
				'keyExists' => static fn (Scope $scope, Arg $array, Arg $key): Expr => new FuncCall(
					new Name('array_key_exists'),
					[$key, $array],
				),
				'keyNotExists' => static fn (Scope $scope, Arg $array, Arg $key): Expr => new BooleanNot(
					new FuncCall(
						new Name('array_key_exists'),
						[$key, $array],
					),
				),
				'notBlank' => static fn (Scope $scope, Arg $value): Expr => new BooleanAnd(
					new BooleanAnd(
						new NotIdentical(
							$value->value,
							new ConstFetch(new Name('null')),
						),
						new NotIdentical(
							$value->value,
							new ConstFetch(new Name('false')),
						),
					),
					new BooleanAnd(
						new NotIdentical(
							$value->value,
							new String_(''),
						),
						new NotIdentical(
							$value->value,
							new Array_(),
						),
					),
				),
			];
		}

		$assertionsResultingAtLeastInNonEmptyString = [
			'isJsonString',
		];
		foreach ($assertionsResultingAtLeastInNonEmptyString as $name) {
			self::$resolvers[$name] = static fn (Scope $scope, Arg $value): array => self::createIsNonEmptyStringAndSomethingExprPair($name, [$value]);
		}

		return self::$resolvers;
	}

	/**
	 * @param Arg[] $args
	 * @return array{Expr, Expr}
	 */
	private static function createIsNonEmptyStringAndSomethingExprPair(string $name, array $args): array
	{
		$expr = new BooleanAnd(
			new FuncCall(
				new Name('is_string'),
				[$args[0]],
			),
			new NotIdentical(
				$args[0]->value,
				new String_(''),
			),
		);

		$rootExpr = new BooleanAnd(
			$expr,
			new FuncCall(new Name('FAUX_FUNCTION_ ' . $name), $args),
		);

		return [$expr, $rootExpr];
	}

	private static function specifyRootExprIfSet(?Expr $rootExpr, Scope $scope, SpecifiedTypes $specifiedTypes, TypeSpecifier $typeSpecifier): SpecifiedTypes
	{
		if ($rootExpr === null) {
			return $specifiedTypes;
		}

		// Makes consecutive calls with a rootExpr adding unknown info via FAUX_FUNCTION evaluate to true
		return $specifiedTypes->unionWith(
			$typeSpecifier->create($rootExpr, new ConstantBooleanType(true), TypeSpecifierContext::createTruthy(), $scope),
		);
	}

}
