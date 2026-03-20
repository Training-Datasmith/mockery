# Architecture: mockery

## Purpose

A mock object framework for PHP, used in unit tests to create test doubles for classes and interfaces. Supports spies, stubs, expectations, argument matchers, and partial mocks.

## Directory Structure

```
library/
  Mockery.php                    — Global facade: Mockery::mock(), spy(), close(), etc.
  Mockery/
    Container.php                — Manages mock instances for a test; cleaned up in teardown
    Mock.php                     — Core mock object implementation
    Expectation.php              — Defines expected calls, argument constraints, and return values
    Expectation_Director.php     — Dispatches incoming calls to matching Expectation objects
    Context.php                  — Evaluates data-context-aware expectations
    Configuration.php            — Global Mockery configuration
    Generator/
      Mock_Configuration.php     — Immutable config for one mock (target class, interfaces, traits)
      String_Manipulation_Generator.php — Generates mock class source code via string passes
      Caching_Generator.php      — Caches generated class source to avoid re-generation
      Pass/                      — Individual string manipulation passes (class name, methods, etc.)
    Matcher/
      Matcher_Abstract.php       — Base for argument matchers
      Any.php, AnyOf.php, ...    — Built-in matchers
    CountValidator/
      Count_Validator_Abstract.php — Base for call-count validators
      At_Least.php, At_Most.php, Exact.php
    Loader/
      Eval_Loader.php            — Loads generated class via eval()
      Require_Loader.php         — Loads generated class via require (file-based)
    Exception/                   — Domain-specific exceptions
    Adapter/Phpunit/             — PHPUnit integration (test listener, trait)
```

## Key Design Decisions

- **Code generation**: Mocks are PHP classes generated at runtime by string manipulation; this allows mocking final-ish methods and bypassing constructor logic
- **Multi-pass generator**: The `StringManipulationGenerator` runs a series of `Pass` objects over the generated source, each responsible for one concern (method definitions, type hints, interfaces, etc.)
- **Container lifecycle**: Each test owns a `Container`; `Mockery::close()` (called in `tearDown`) verifies all expectations were met and destroys mock instances
- **Fluent expectations API**: `$mock->shouldReceive('method')->once()->with(42)->andReturn('ok')` builds `Expectation` objects that are checked when the test ends

## Extension Points

- Implement `Matcher_Interface` to create custom argument matchers
- Use `Mockery::mock('Alias:ClassName')` to mock static methods via class aliasing
- Use `Mockery::mock('Overload:ClassName')` to mock `new` expressions

## Dependency Flow

```
Test calls Mockery::mock(TargetClass::class)
  → Container::mock()
  → MockConfigurationBuilder → MockConfiguration
  → CachingGenerator → StringManipulationGenerator
      → [Pass1, Pass2, ...PassN] applied to template
  → EvalLoader or RequireLoader  (defines the class)
  → new GeneratedMockClass()     (returned to test)
```
