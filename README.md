# Laravel DDD Toolkit

Laravel DDD Toolkit is a Composer package for building modular Laravel applications with tactical DDD patterns, without turning Laravel into a different framework.

It helps organize large Laravel codebases by business capability, using a vertical modular structure such as `app/Modules/Order`, `app/Modules/User`, and `app/Modules/Payment`, while keeping the Laravel experience familiar: Artisan commands, service providers, routes, Eloquent, jobs, listeners, requests, and controllers still work as expected.

This package is intentionally pragmatic. It is not an academic DDD framework, it does not replace Eloquent, and it does not force repositories, CQRS, Event Sourcing, or a rigid architecture on every project.

## Summary

- [Why This Exists](#why-this-exists)
- [Philosophy](#philosophy)
- [Installation](#installation)
- [Core Commands](#core-commands)
- [Generated Structure](#generated-structure)
- [Auto Discovery](#auto-discovery)
- [Configuration](#configuration)
- [Repositories And Eloquent](#repositories-and-eloquent)
- [Custom Stubs](#custom-stubs)
- [Development](#development)

## Why This Exists

Laravel is productive and expressive, but large applications often become hard to navigate when code is grouped mainly by technical layer:

- controllers grow too large;
- services become generic dumping grounds;
- business rules spread across models, requests, jobs, and listeners;
- related files live far apart from each other;
- bounded contexts become hard to see;
- teams struggle to change one business area without touching another.

Laravel DDD Toolkit addresses this by encouraging a modular monolith organized by domain:

```text
app/
  Modules/
    Order/
    Payment/
    User/
  Shared/
```

Each module is a business capability. The goal is not purity. The goal is making long-lived Laravel applications easier to understand, change, and grow.

## Philosophy

This package follows a few strong opinions:

- **Laravel first:** the framework remains the foundation. The toolkit adds structure, not a replacement runtime.
- **Tactical DDD only:** entities, value objects, domain events, use cases, contracts, and module boundaries are encouraged; strategic DDD ceremony is not required.
- **Vertical organization:** code is grouped by feature or domain instead of only by technical layer.
- **Pragmatism over purity:** use the pattern when it helps. Skip it when it adds noise.
- **Eloquent is supported:** Active Record is part of Laravel's productivity story and should coexist with domain-oriented code.
- **Repositories are optional:** create repositories only when they add value, such as hiding complex persistence, integrating external storage, or protecting domain code from query details.
- **Convention over configuration:** defaults should work for most projects, with configuration available when the project needs it.

In short: this package tries to help Laravel projects age well without fighting the framework.

## Installation

```bash
composer require samuel-nunes/laravel-ddd-toolkit
php artisan ddd:install
```

The install command creates:

```text
app/Modules
app/Shared
app/Providers/ModulesServiceProvider.php
config/ddd.php
```

On Laravel 11 and newer, it also registers `App\Providers\ModulesServiceProvider` in:

```text
bootstrap/providers.php
```

If legacy folders such as `app/Models`, `app/Services`, or `app/Repositories` exist, the command may ask if you want to review them. It never removes those folders automatically.

## Core Commands

Create a module:

```bash
php artisan make:domain Order
```

Create domain and application classes:

```bash
php artisan make:entity Order --module=Order
php artisan make:value-object Email --module=User
php artisan make:usecase CancelOrder --module=Order
```

Create integration and infrastructure classes:

```bash
php artisan make:acl Stripe --module=Payment
php artisan make:event OrderCancelled --module=Order
php artisan make:listener RefundPayment --module=Payment
php artisan make:policy OrderPolicy --module=Order
php artisan make:repository OrderRepository --module=Order
php artisan make:aggregate OrderAggregate --module=Order
```

Generators are idempotent by default. Existing files are not overwritten unless you pass `--force`.

## Generated Structure

By default, a module is created like this:

```text
app/Modules/Order/
  Domain/
    Entities/
    ValueObjects/
    Events/
    Exceptions/
    Contracts/
  Application/
    Commands/
    Queries/
    DTO/
    Handlers/
  Infrastructure/
    Http/
      Controllers/
      Requests/
      routes.php
    Persistence/
      Models/
      Repositories/
    Integrations/
    Jobs/
    Listeners/
    Providers/
```

The intended dependency direction is:

```text
HTTP
  -> Controller
  -> Application handler / use case
  -> Domain
  -> Persistence or integration
```

The domain layer should contain business concepts and rules. It should not contain controllers, HTTP requests, jobs, or Laravel-specific infrastructure.

## Auto Discovery

The generated `ModulesServiceProvider` automatically discovers module resources.

It loads route files from:

```text
app/Modules/{Module}/Infrastructure/Http/routes.php
```

It registers module service providers from:

```text
app/Modules/{Module}/Infrastructure/Providers/*ServiceProvider.php
```

This means a module can own its routes and providers without requiring manual edits to `routes/api.php` or the application provider list for every new module.

## Configuration

The published config lives at:

```text
config/ddd.php
```

Default options:

```php
return [
    'modules_path' => 'app/Modules',
    'shared_path' => 'app/Shared',
    'default_domain_structure' => true,
    'create_repositories' => false,
    'create_policies' => false,
    'create_jobs' => true,
    'create_events' => true,
    'preset' => 'default',
];
```

Available presets:

- `minimal`: creates only `Domain`, `Application`, and `Infrastructure`.
- `default`: creates the standard tactical DDD module structure.
- `full`: includes additional directories for aggregates, policies, repositories, jobs, events, and integrations.

## Repositories And Eloquent

Repositories are disabled by default:

```php
'create_repositories' => false,
```

That is intentional. In many Laravel applications, Eloquent models and query builders are enough and more readable than a repository layer.

Enable repositories when they solve a real problem:

```php
'create_repositories' => true,
```

Or force a single repository generation:

```bash
php artisan make:repository OrderRepository --module=Order --force
```

The same principle applies to policies:

```php
'create_policies' => true,
```

## Custom Stubs

Publish the package stubs when you want to customize generated code:

```bash
php artisan vendor:publish --tag=ddd-stubs
```

Published stubs are placed in:

```text
stubs/vendor/laravel-ddd-toolkit
```

When a custom stub exists there, the generator uses it instead of the package default.

## Development

Install dependencies:

```bash
composer install
```

Run tests:

```bash
composer test
```

The test suite uses Orchestra Testbench to validate package behavior against Laravel.

Run static analysis with Psalm:

```bash
vendor/bin/psalm --no-cache --show-info=false
```

Run Psalm with taint analysis enabled:

```bash
vendor/bin/psalm --no-cache --show-info=false --taint-analysis
```

When PHP or Composer are not available on the host machine, the same checks can be run with the Psalm Docker image:

```bash
docker run --rm --user 1000:1000 -v "$PWD:/app" -w /app --entrypoint /composer/vendor/bin/psalm ghcr.io/danog/psalm:latest --no-cache --show-info=false
docker run --rm --user 1000:1000 -v "$PWD:/app" -w /app --entrypoint /composer/vendor/bin/psalm ghcr.io/danog/psalm:latest --no-cache --show-info=false --taint-analysis
```
