<?php declare(strict_types = 1);

namespace PHPStan\Type\BeberleiAssert;

use Assert\{Assert, Assertion};

class Foo
{

	public function doFoo($a, $b, array $c, iterable $d, $e, $f, $g, $h, $i, $j, $k, $l, $m, string $n, $p, $r, $s, ?int $t, ?int $u, $x, $aa, array $ab)
	{
		$a;

		Assertion::integer($a);
		$a;

		Assertion::nullOrInteger($b);
		$b;

		Assertion::allInteger($c);
		$c;

		Assertion::allInteger($d);
		$d;

		Assertion::string($e);
		$e;

		Assertion::float($f);
		$f;

		Assertion::numeric($g);
		$g;

		Assertion::boolean($h);
		$h;

		Assertion::scalar($i);
		$i;

		Assertion::objectOrClass($j);
		$j;

		Assertion::isResource($k);
		$k;

		Assertion::isCallable($l);
		$l;

		Assertion::isArray($m);
		$m;

		Assertion::objectOrClass($n);
		$n;

		Assertion::isInstanceOf($p, self::class);
		$p;

		/** @var Foo|Bar $q */
		$q = doFoo();
		Assertion::notIsInstanceOf($q, Bar::class);
		$q;

		Assertion::true($r);
		$r;

		Assertion::false($s);
		$s;

		Assertion::null($t);
		$t;

		Assertion::notNull($u);
		$u;

		/** @var (Foo|Bar)[] $v */
		$v = doFoo();
		Assertion::allNotIsInstanceOf($v, Bar::class);
		$v;

		/** @var (int|null)[] $w */
		$w = doFoo();
		Assertion::allNotNull($w);
		$w;

		Assertion::same($x, 1);
		$x;

		if (doFoo()) {
			$y = 1;
		} else {
			$y = -1;
		}

		$y;
		Assertion::notSame($y, 1);
		$y;

		$z = [1, 1, 1];
		if (doFoo()) {
			$z = [-1, -1, -1];
		}
		Assertion::allNotSame($z, -1);
		$z;

		Assertion::subclassOf($aa, self::class);
		$aa;

		Assertion::allSubclassOf($ab, self::class);
		$ab;
	}

	public function doBar(?int $a, $b, $c, array $d, iterable $e, $g)
	{
		$that = Assert::that($a);
		$that;

		$thatOrNull = $that->notNull();
		$thatOrNull;
		$a;

		$assertNullOr = Assert::that($b)->nullOr();
		$assertNullOr;
		$assertNullOr->string();
		$b;

		$assertNullOr2 = Assert::thatNullOr($c);
		$assertNullOr2;
		$assertNullOr2->string();
		$c;

		$assertAll = Assert::that($d)->all();
		$assertAll;
		$assertAll->string();
		$d;

		$assertAll2 = Assert::thatAll($e);
		$assertAll2;
		$assertAll2->string();
		$e;

		/** @var (string|null)[] $f */
		$f = doFoo();
		Assert::thatAll($f)->notNull();
		$f;

		$assertThatFunction = \Assert\that($g);
		$assertThatFunction;

		$assertThatNullOrFunction = \Assert\thatNullOr($g);
		$assertThatNullOrFunction;

		$assertThatAllFunction = \Assert\thatAll($g);
		$assertThatAllFunction;
	}

}

class Bar
{

}
