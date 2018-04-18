# PHPStan beberlei/assert extension

[![Build Status](https://travis-ci.org/phpstan/phpstan-beberlei-assert.svg)](https://travis-ci.org/phpstan/phpstan-beberlei-assert)
[![Latest Stable Version](https://poser.pugx.org/phpstan/phpstan-beberlei-assert/v/stable)](https://packagist.org/packages/phpstan/phpstan-beberlei-assert)
[![License](https://poser.pugx.org/phpstan/phpstan-beberlei-assert/license)](https://packagist.org/packages/phpstan/phpstan-beberlei-assert)

* [PHPStan](https://github.com/phpstan/phpstan)
* [beberlei/assert](https://github.com/beberlei/assert)

This extension specifies types of values passed to:

* `Assertion::integer`
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
* `nullOr*` and `all*` variants of the above methods

## Usage

To use this extension, require it in [Composer](https://getcomposer.org/):

```bash
composer require --dev phpstan/phpstan-beberlei-assert
```

And include extension.neon in your project's PHPStan config:

```
includes:
	- vendor/phpstan/phpstan-beberlei-assert/extension.neon
```
