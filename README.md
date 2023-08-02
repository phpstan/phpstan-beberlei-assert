# PHPStan beberlei/assert extension

[![Build](https://github.com/phpstan/phpstan-beberlei-assert/workflows/Build/badge.svg)](https://github.com/phpstan/phpstan-beberlei-assert/actions)
[![Latest Stable Version](https://poser.pugx.org/phpstan/phpstan-beberlei-assert/v/stable)](https://packagist.org/packages/phpstan/phpstan-beberlei-assert)
[![License](https://poser.pugx.org/phpstan/phpstan-beberlei-assert/license)](https://packagist.org/packages/phpstan/phpstan-beberlei-assert)

* [PHPStan](https://phpstan.org/)
* [beberlei/assert](https://github.com/beberlei/assert)

## Description

The main scope of this extension is to help phpstan to detect the type of object after the `Assert\Assertion` validation.

```php
<?php declare(strict_types = 1);
use Assert\Assertion;

function demo(?int $a) {
	// ...

	Assertion::integer($a);
	// phpstan is now aware that $a can no longer be `null` at this point

	return ($a === 10);
}
```

This extension specifies types of values passed to:

* `Assertion::integer`
* `Assertion::integerish`
* `Assertion::string`
* `Assertion::float`
* `Assertion::numeric`
* `Assertion::boolean`
* `Assertion::scalar`
* `Assertion::objectOrClass`
* `Assertion::isResource`
* `Assertion::isCallable`
* `Assertion::isArray`
* `Assertion::isInstanceOf`
* `Assertion::notIsInstanceOf`
* `Assertion::subclassOf`
* `Assertion::true`
* `Assertion::false`
* `Assertion::null`
* `Assertion::notNull`
* `Assertion::same`
* `Assertion::notSame`
* `Assertion::isJsonString`
* `Assertion::keyExists`
* `Assertion::keyNotExists`
* `Assertion::notBlank`
* `nullOr*` and `all*` variants of the above methods

`Assert::that`, `Assert::thatNullOr` and `Assert::thatAll` chaining methods are also supported.

`Assert\that`, `Assert\thatNullOr` and `Assert\thatAll` functions are supported too.


## Installation

To use this extension, require it in [Composer](https://getcomposer.org/):

```
composer require --dev phpstan/phpstan-beberlei-assert
```

If you also install [phpstan/extension-installer](https://github.com/phpstan/extension-installer) then you're all set!

<details>
  <summary>Manual installation</summary>

If you don't want to use `phpstan/extension-installer`, include extension.neon in your project's PHPStan config:

```
includes:
    - vendor/phpstan/phpstan-beberlei-assert/extension.neon
```
</details>
