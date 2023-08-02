<?php declare(strict_types = 1);

namespace PHPStan\Type\BeberleiAssert;

use Assert\{Assert, Assertion};

class Foo
{

	public function doFoo($a, $b, array $c, iterable $d, $e, $f, $g, $h, $i, $j, $k, $l, $m, string $n, $p, $r, $s, ?int $t, ?int $u, $x, $aa, array $ab, ?string $ac, ?string $ae, ?bool $af, array $ag)
	{
		\PHPStan\Testing\assertType('mixed', $a);

		Assertion::integer($a);
		\PHPStan\Testing\assertType('int', $a);

		Assertion::nullOrInteger($b);
		\PHPStan\Testing\assertType('int|null', $b);

		Assertion::allInteger($c);
		\PHPStan\Testing\assertType('array<int>', $c);

		Assertion::allInteger($d);
		\PHPStan\Testing\assertType('iterable<int>', $d);

		Assertion::string($e);
		\PHPStan\Testing\assertType('string', $e);

		Assertion::float($f);
		\PHPStan\Testing\assertType('float', $f);

		Assertion::numeric($g);
		\PHPStan\Testing\assertType('float|int|numeric-string', $g);

		Assertion::boolean($h);
		\PHPStan\Testing\assertType('bool', $h);

		Assertion::scalar($i);
		\PHPStan\Testing\assertType('bool|float|int|string', $i);

		Assertion::objectOrClass($j);
		\PHPStan\Testing\assertType('object', $j);

		Assertion::isResource($k);
		\PHPStan\Testing\assertType('resource', $k);

		Assertion::isCallable($l);
		\PHPStan\Testing\assertType('callable(): mixed', $l);

		Assertion::isArray($m);
		\PHPStan\Testing\assertType('array', $m);

		Assertion::objectOrClass($n);
		\PHPStan\Testing\assertType('string', $n);

		Assertion::isInstanceOf($p, self::class);
		\PHPStan\Testing\assertType(self::class, $p);

		/** @var Foo|Bar $q */
		$q = doFoo();
		Assertion::notIsInstanceOf($q, Bar::class);
		\PHPStan\Testing\assertType(Foo::class, $q);

		Assertion::true($r);
		\PHPStan\Testing\assertType('true', $r);

		Assertion::false($s);
		\PHPStan\Testing\assertType('false', $s);

		Assertion::null($t);
		\PHPStan\Testing\assertType('null', $t);

		Assertion::notNull($u);
		\PHPStan\Testing\assertType('int', $u);

		/** @var (Foo|Bar)[] $v */
		$v = doFoo();
		Assertion::allNotIsInstanceOf($v, Bar::class);
		\PHPStan\Testing\assertType('array<PHPStan\Type\BeberleiAssert\Foo>', $v);

		/** @var (int|null)[] $w */
		$w = doFoo();
		Assertion::allNotNull($w);
		\PHPStan\Testing\assertType('array<int>', $w);

		/** @var null[] $w */
		$w = doFoo();
		Assertion::allNotNull($w);
		\PHPStan\Testing\assertType('*NEVER*', $w);

		/** @var iterable<null> $w */
		$w = doFoo();
		Assertion::allNotNull($w);
		\PHPStan\Testing\assertType('*NEVER*', $w);

		/** @var array{baz: float|null}|array{foo?: string|null, bar: int|null} $w */
		$w = doFoo();
		Assertion::allNotNull($w);
		\PHPStan\Testing\assertType('array{baz: float}|array{foo?: string, bar: int}', $w);

		Assertion::same($x, 1);
		\PHPStan\Testing\assertType('1', $x);

		if (doFoo()) {
			$y = 1;
		} else {
			$y = -1;
		}

		\PHPStan\Testing\assertType('-1|1', $y);
		Assertion::notSame($y, 1);
		\PHPStan\Testing\assertType('-1', $y);

		$z = [1, 2, 3];
		if (doFoo()) {
			$z = [-1, -2, -3];
		}
		Assertion::allNotSame($z, -1);
		\PHPStan\Testing\assertType('array{1, 2, 3}', $z);

		$z = [-1, -2, -3];
		Assertion::allNotSame($z, -1);
		\PHPStan\Testing\assertType('*NEVER*', $z);

		Assertion::subclassOf($aa, self::class);
		\PHPStan\Testing\assertType('class-string<PHPStan\Type\BeberleiAssert\Foo>|PHPStan\Type\BeberleiAssert\Foo', $aa);

		Assertion::allSubclassOf($ab, self::class);
		\PHPStan\Testing\assertType('array<class-string<PHPStan\Type\BeberleiAssert\Foo>|PHPStan\Type\BeberleiAssert\Foo>', $ab);

		Assertion::isJsonString($ac);
		\PHPStan\Testing\assertType('string', $ac);

		/** @var array{a?: string, b?: int} $ad */
		$ad = doFoo();
		Assertion::keyExists($ad, 'a');
		\PHPStan\Testing\assertType("array{a: string, b?: int}", $ad);

		Assertion::keyNotExists($ad, 'b');
		\PHPStan\Testing\assertType("array{a: string}", $ad);

		Assertion::notBlank($ae);
		\PHPStan\Testing\assertType('non-empty-string', $ae);

		Assertion::notBlank($af);
		\PHPStan\Testing\assertType('true', $af);

		Assertion::notBlank($ag);
		\PHPStan\Testing\assertType('non-empty-array', $ag);
	}

	public function doBar(?int $a, $b, $c, array $d, iterable $e, $g)
	{
		$that = Assert::that($a);
		\PHPStan\Testing\assertType('Assert\AssertionChain<int|null>', $that);

		$thatOrNull = $that->notNull();
		\PHPStan\Testing\assertType('Assert\AssertionChain<int|null>', $thatOrNull);
		\PHPStan\Testing\assertType('int', $a);

		$assertNullOr = Assert::that($b)->nullOr();
		\PHPStan\Testing\assertType('Assert\AssertionChain<mixed>-nullOr', $assertNullOr);
		$assertNullOr->string();
		\PHPStan\Testing\assertType('string|null', $b);

		$assertNullOr2 = Assert::thatNullOr($c);
		\PHPStan\Testing\assertType('Assert\AssertionChain<mixed>-nullOr', $assertNullOr2);
		$assertNullOr2->string();
		\PHPStan\Testing\assertType('string|null', $c);

		$assertAll = Assert::that($d)->all();
		\PHPStan\Testing\assertType('Assert\AssertionChain<array>-all', $assertAll);
		$assertAll->string();
		\PHPStan\Testing\assertType('array<string>', $d);

		$assertAll2 = Assert::thatAll($e);
		\PHPStan\Testing\assertType('Assert\AssertionChain<array>-all', $assertAll);
		$assertAll2->string();
		\PHPStan\Testing\assertType('iterable<string>', $e);

		/** @var (string|null)[] $f */
		$f = doFoo();
		Assert::thatAll($f)->notNull();
		\PHPStan\Testing\assertType('array<string>', $f);

		$assertThatFunction = \Assert\that($g);
		\PHPStan\Testing\assertType('Assert\AssertionChain<mixed>', $assertThatFunction);

		$assertThatNullOrFunction = \Assert\thatNullOr($g);
		\PHPStan\Testing\assertType('Assert\AssertionChain<mixed>-nullOr', $assertThatNullOrFunction);

		$assertThatAllFunction = \Assert\thatAll($g);
		\PHPStan\Testing\assertType('Assert\AssertionChain<mixed>-all', $assertThatAllFunction);
	}

	public function doBaz(array $a): void
	{
		if (rand(0, 1)) {
			$a = false;
		}

		Assertion::allIsInstanceOf($a, \stdClass::class);
		\PHPStan\Testing\assertType('array<stdClass>', $a);
	}

	public function doFooBar($a): void
	{
		Assertion::integerish($a);
		\PHPStan\Testing\assertType('float|int|numeric-string', $a);
	}
}

class Bar
{

}
