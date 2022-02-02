<?php declare(strict_types = 1);

namespace PHPStan\Type\BeberleiAssert;

use PhpParser\Node\Expr;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

class AssertThatType extends ObjectType
{

	/** @var Expr */
	private $valueExpr;

	/** @var Type */
	private $valueType;

	public function __construct(
		Expr $valueExpr,
		Type $valueType
	)
	{
		parent::__construct('Assert\AssertionChain');
		$this->valueExpr = $valueExpr;
		$this->valueType = $valueType;
	}

	public function getValueExpr(): Expr
	{
		return $this->valueExpr;
	}

	public function getValueType(): Type
	{
		return $this->valueType;
	}

	public function describe(VerbosityLevel $level): string
	{
		return sprintf('%s<%s>', parent::describe($level), $this->valueType->describe($level));
	}

	public function toAll(): AssertThatAllType
	{
		return new AssertThatAllType($this->valueExpr, $this->valueType);
	}

	public function toNullOr(): AssertThatNullOrType
	{
		return new AssertThatNullOrType($this->valueExpr, $this->valueType);
	}

}
