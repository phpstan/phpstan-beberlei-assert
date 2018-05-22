<?php declare(strict_types = 1);

namespace PHPStan\Type\BeberleiAssert;

use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;

class AssertThatNullOrType extends ObjectType
{

	/** @var \PhpParser\Node\Expr */
	private $valueExpr;

	/** @var Type */
	private $valueType;

	public function __construct(
		\PhpParser\Node\Expr $valueExpr,
		Type $valueType
	)
	{
		parent::__construct('Assert\AssertionChain');
		$this->valueExpr = $valueExpr;
		$this->valueType = $valueType;
	}

	public function getValueExpr(): \PhpParser\Node\Expr
	{
		return $this->valueExpr;
	}

	public function getValueType(): Type
	{
		return $this->valueType;
	}

	public function describe(VerbosityLevel $level): string
	{
		return sprintf('%s<%s>-nullOr', parent::describe($level), $this->valueType->describe($level));
	}

}
