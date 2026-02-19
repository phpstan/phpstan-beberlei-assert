# CLAUDE.md

This file provides guidance for AI agents working on the phpstan-beberlei-assert repository.

## Project Overview

This is a PHPStan extension that provides type narrowing support for the [beberlei/assert](https://github.com/beberlei/assert) library. When users call assertion methods like `Assertion::integer($a)`, PHPStan normally doesn't understand that `$a` is guaranteed to be an integer after that call. This extension teaches PHPStan to narrow types after beberlei/assert assertions, improving static analysis accuracy.

## PHP Version Support

This repository supports **PHP 7.4+**. The `composer.json` has `"php": "^7.4 || ^8.0"` and the platform is pinned to `7.4.6`. Do not use PHP 8.0+ syntax (named arguments, match expressions, union types in signatures, etc.).

## Build Commands

All commands are defined in the `Makefile`:

- `make check` — runs all checks (lint, cs, tests, phpstan)
- `make tests` — runs PHPUnit tests (`vendor/bin/phpunit`)
- `make lint` — runs parallel-lint on `src/` and `tests/`
- `make cs` — runs coding standard checks (requires `make cs-install` first to clone `phpstan/build-cs`)
- `make cs-fix` — auto-fixes coding standard violations
- `make phpstan` — runs PHPStan at level 8 on `src/` and `tests/`
- `make phpstan-generate-baseline` — regenerates the PHPStan baseline file

### Running Checks Locally

```bash
composer install
make tests    # fast feedback loop
make phpstan  # static analysis
make check    # full CI-equivalent check (requires cs-install first)
```

## Repository Structure

```
src/Type/BeberleiAssert/       — Extension source code
tests/Type/BeberleiAssert/     — Tests
tests/Type/BeberleiAssert/data/ — PHP test fixture files
extension.neon                  — PHPStan service definitions (entry point for users)
phpstan.neon                    — PHPStan configuration for analysing this project itself
phpstan-baseline.neon           — PHPStan baseline for known issues
```

## Architecture

The extension registers five services in `extension.neon`:

1. **AssertTypeSpecifyingExtension** — Handles static method calls on `Assert\Assertion` (e.g., `Assertion::integer()`, `Assertion::nullOrString()`, `Assertion::allNotNull()`). Implements `StaticMethodTypeSpecifyingExtension`.

2. **AssertionChainTypeSpecifyingExtension** — Handles fluent assertion chain method calls on `Assert\AssertionChain` (e.g., `Assert::that($x)->string()`). Implements `MethodTypeSpecifyingExtension`.

3. **AssertionChainDynamicReturnTypeExtension** — Returns custom types (`AssertThatType`, `AssertThatAllType`, `AssertThatNullOrType`) from chain method calls like `->all()` and `->nullOr()`. Implements `DynamicMethodReturnTypeExtension`.

4. **AssertThatDynamicMethodReturnTypeExtension** — Handles `Assert::that()`, `Assert::thatNullOr()`, `Assert::thatAll()` static calls. Implements `DynamicStaticMethodReturnTypeExtension`.

5. **AssertThatFunctionDynamicReturnTypeExtension** — Handles `Assert\that()`, `Assert\thatNullOr()`, `Assert\thatAll()` function calls. Implements `DynamicFunctionReturnTypeExtension`.

**AssertHelper** is the core class that maps assertion method names (e.g., `integer`, `string`, `notNull`, `isInstanceOf`) to equivalent PHP expressions that PHPStan's type specifier can understand. For example, `Assertion::integer($a)` is translated to the expression `is_int($a)`.

The three custom type classes (`AssertThatType`, `AssertThatAllType`, `AssertThatNullOrType`) extend `ObjectType` and carry the original expression being asserted, enabling the chain-style API to narrow types.

## Testing Patterns

Tests use PHPStan's built-in testing infrastructure:

- **Type inference tests** (`AssertTypeSpecifyingExtensionTest`): Extend `TypeInferenceTestCase`. Test data files in `tests/Type/BeberleiAssert/data/` use `\PHPStan\Testing\assertType()` calls to verify that types are narrowed correctly after assertions.

- **Rule tests** (`ImpossibleCheckTypeStaticMethodCallRuleTest`, `ImpossibleCheckTypeMethodCallRuleTest`): Extend `RuleTestCase`. These verify that PHPStan correctly reports impossible/always-true assertion checks when combined with `phpstan-strict-rules`.

Test config files are loaded via `getAdditionalConfigFiles()` which returns the path to `extension.neon`.

## Adding Support for New Assertions

To add support for a new `Assertion::xyz()` method:

1. Add a resolver entry in `AssertHelper::getExpressionResolvers()` that maps the assertion name to an equivalent PHP expression (using PhpParser AST nodes).
2. Add test cases in `tests/Type/BeberleiAssert/data/data.php` using `assertType()` to verify the type narrowing.
3. Run `make tests` and `make phpstan` to verify.

## CI

GitHub Actions workflow (`.github/workflows/build.yml`) runs on PRs and pushes to `2.0.x`:

- **Lint**: PHP 7.4–8.4
- **Coding Standard**: PHP 8.2 with `phpstan/build-cs` (branch `2.x`)
- **Tests**: PHP 7.4–8.4, both lowest and highest dependency versions
- **PHPStan**: PHP 7.4–8.4, both lowest and highest dependency versions

## Dependencies

- `phpstan/phpstan: ^2.0` — The static analyser this extension plugs into
- `beberlei/assert: ^3.3.0` — The assertion library (dev dependency, needed for tests)
- `phpstan/phpstan-strict-rules: ^2.0` — Used for testing impossible check detection
- `phpstan/phpstan-phpunit: ^2.0` — PHPUnit support for PHPStan analysis of tests
- `phpunit/phpunit: ^9.6` — Test framework
