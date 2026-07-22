# Laravel DDD Toolkit

Laravel DDD Toolkit is a Composer package for building large, modular Laravel applications with vertical modules, hexagonal architecture by default, and pragmatic tactical DDD patterns.

**Website:** [samuelnunesdev.github.io/laravel-ddd-toolkit-site](https://samuelnunesdev.github.io/laravel-ddd-toolkit-site/)

It organizes the application vertically by business capability, such as `Order`, `Payment`, `Customer`, or `Billing`, while keeping the Laravel experience familiar: Artisan commands, service providers, routes, Eloquent, jobs, listeners, requests, and controllers still work as expected.

This package is intentionally pragmatic. It is not an academic DDD framework, it does not replace Laravel, and it does not force CQRS, Event Sourcing, repositories, or a rigid architecture on every project.

## Summary

- [Why This Exists](#why-this-exists)
- [Compatibility](#compatibility)
- [Architecture](#architecture)
- [Installation](#installation)
- [Quick Start](#quick-start)
- [Core Commands](#core-commands)
- [Generated Structure](#generated-structure)
- [Generated Code Example](#generated-code-example)
- [Presets](#presets)
- [Discovery Cache](#discovery-cache)
- [Architecture Checks](#architecture-checks)
- [Configuration](#configuration)
- [Repositories And Eloquent](#repositories-and-eloquent)
- [Custom Stubs](#custom-stubs)
- [Contributing](#contributing)
- [Development](#development)
- [License](#license)

## Why This Exists

Laravel is productive and expressive, but large applications often become hard to navigate when code is grouped mainly by technical layer:

- controllers grow too large;
- services become generic dumping grounds;
- business rules spread across models, requests, jobs, and listeners;
- related files live far apart from each other;
- module boundaries become hard to see;
- teams struggle to change one business area without touching another.

Laravel DDD Toolkit addresses this by encouraging a modular monolith organized by business capability:

```text
app/
  Modules/
    Order/
    Payment/
    Customer/
  Shared/
```

Each module is a feature, business capability, subdomain, or bounded context depending on the size of the application.

## Compatibility

- PHP: `^8.2`
- Laravel components: `^11.0`, `^12.0`, or `^13.0`

## Architecture

The toolkit combines two complementary ideas:

```text
Vertical architecture organizes the project by module or business capability.

Hexagonal architecture organizes dependencies inside each module through Ports and Adapters.
```

The default positioning is:

```text
Vertical modules
+ Hexagonal architecture inside each module
+ Pragmatic tactical DDD
```

Dependency direction is separate from execution flow.

Allowed dependency direction:

```text
Infrastructure -> Application -> Domain
Infrastructure -> Application Ports
Domain does not depend on Infrastructure
Application does not depend on Controllers, Requests, or persistence Models
```

Typical execution flow:

```text
HTTP
  -> Controller
  -> Application Use Case
  -> Domain
  -> Port
  -> Adapter
```

Ports live in `Application/Ports/In` and `Application/Ports/Out`. Adapters live in `Infrastructure`.

An external integration can act as an anti-corruption layer when it protects the domain from external APIs, payloads, SDK models, or vendor-specific concepts. ACL is treated as a role an integration can play, not as the primary command name.

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
AGENTS.md
```

On Laravel 11 and newer, it also registers `App\Providers\ModulesServiceProvider` in:

```text
bootstrap/providers.php
```

If legacy folders such as `app/Models`, `app/Services`, or `app/Repositories` exist, the command may ask if you want to review them. It never removes those folders automatically.

The installer never overwrites an existing `AGENTS.md` unless `--force-agents` is explicitly used.

Installer options:

```text
--no-agents
--merge-agents
--force-agents
```

## AI-friendly projects

Laravel DDD Toolkit can publish an `AGENTS.md` file to help AI coding agents understand the architecture of your Laravel project.

```bash
php artisan ddd:install
```

If your project already has an `AGENTS.md`, it will not be overwritten.

To merge Laravel DDD Toolkit instructions into an existing file:

```bash
php artisan ddd:install --merge-agents
```

To skip publishing:

```bash
php artisan ddd:install --no-agents
```

To overwrite:

```bash
php artisan ddd:install --force-agents
```

The file guides agents to preserve vertical modules, hexagonal architecture by default, ports in `Application`, adapters in `Infrastructure`, a Domain layer without Laravel dependencies, `make:module` instead of `make:domain`, and `ddd:check` before finishing architectural changes.

## Quick Start

Create an `Order` module, define an outbound persistence port, bind it to an Eloquent adapter, and create a use case:

```bash
php artisan make:module Order
php artisan make:port Order OrderRepository --type=out
php artisan make:adapter Order EloquentOrderRepository --port=OrderRepository --type=persistence
php artisan make:usecase Order CancelOrder
```

Then run the architecture checker:

```bash
php artisan ddd:check --module=Order
```

## Module AI docs

When creating a module, Laravel DDD Toolkit can generate module-level documentation for humans and AI agents.

```bash
php artisan make:module Billing
```

You can provide context directly:

```bash
php artisan make:module Billing --context="Handles invoice issuing, cancellation and invoice queries."
```

Or from a file:

```bash
php artisan make:module Billing --context-file=docs/billing-context.md
```

To skip AI docs:

```bash
php artisan make:module Billing --no-ai-docs
```

Generated files:

```text
app/Modules/Billing/README.md
app/Modules/Billing/AGENTS.md
```

These files do not use AI. They are generated from templates using the context provided by the developer.

## Core Commands

Create a module:

```bash
php artisan make:module Order
```

Create tactical domain and application classes:

```bash
php artisan make:entity Order Order
php artisan make:value-object Customer Email
php artisan make:event Order OrderCancelled
php artisan make:usecase Order CancelOrder
```

Create Ports and Adapters:

```bash
php artisan make:port Order OrderRepository --type=out
php artisan make:port Order CancelOrderUseCase --type=in
php artisan make:adapter Order EloquentOrderRepository --port=OrderRepository --type=persistence
```

Create an external integration:

```bash
php artisan make:integration Payment Stripe
```

Run architecture checks and discovery cache commands:

```bash
php artisan ddd:check
php artisan ddd:cache
php artisan ddd:clear
```

Existing files are not overwritten unless you pass `--force`.

## Generated Structure

By default, `make:module` creates a vertical module with hexagonal structure:

```text
app/Modules/Order/
├── Domain/
│   ├── Entities/
│   ├── ValueObjects/
│   ├── Events/
│   └── Exceptions/
├── Application/
│   ├── UseCases/
│   ├── DTO/
│   └── Ports/
│       ├── In/
│       └── Out/
└── Infrastructure/
    ├── Http/
    │   ├── Controllers/
    │   ├── Requests/
    │   └── routes.php
    ├── Persistence/
    │   ├── Models/
    │   └── Adapters/
    ├── Integrations/
    └── Providers/
```

`Domain/Contracts` is not created by the default preset. Use explicit ports in `Application/Ports/In` and `Application/Ports/Out` instead.

## Generated Code Example

An outbound Port is generated as a PHP interface in the application layer:

```php
<?php

namespace App\Modules\Order\Application\Ports\Out;

interface OrderRepository
{
}
```

A persistence Adapter can implement that Port from the infrastructure layer:

```php
<?php

namespace App\Modules\Order\Infrastructure\Persistence\Adapters;

use App\Modules\Order\Application\Ports\Out\OrderRepository;

final class EloquentOrderRepository implements OrderRepository
{
}
```

When possible, the adapter command also registers the binding in the module provider:

```php
$this->app->bind(
    \App\Modules\Order\Application\Ports\Out\OrderRepository::class,
    \App\Modules\Order\Infrastructure\Persistence\Adapters\EloquentOrderRepository::class,
);
```

## Presets

Hexagonal is the default:

```bash
php artisan make:module Order
```

This is equivalent to:

```bash
php artisan make:module Order --preset=hexagonal
```

Alternative presets are available when a project needs less or more structure:

- `minimal`: creates only `Domain`, `Application`, and `Infrastructure`.
- `tactical`: creates tactical DDD directories without explicit Ports and Adapters.
- `full`: includes aggregates, ports, adapters, repositories, jobs, listeners, policies, integrations, and providers.

## Discovery Cache

The generated `ModulesServiceProvider` discovers module routes and providers automatically.

It loads route files from:

```text
app/Modules/{Module}/Infrastructure/Http/routes.php
```

It registers module service providers from:

```text
app/Modules/{Module}/Infrastructure/Providers/*ServiceProvider.php
```

For production, cache discovery into a manifest:

```bash
php artisan ddd:cache
```

The manifest is written to:

```text
bootstrap/cache/ddd-modules.php
```

Clear it with:

```bash
php artisan ddd:clear
```

When the cache exists, discovery uses the manifest. When it does not exist, filesystem discovery is used.

## Architecture Checks

Run:

```bash
php artisan ddd:check
```

By default, this validates basic hexagonal rules:

- `Domain` must not import Laravel, HTTP foundation, Guzzle, or module Infrastructure classes.
- `Application` must not import controllers, HTTP requests, or persistence models.
- `Infrastructure` may depend on `Application`, `Application/Ports`, and `Domain`.

Validate a single module:

```bash
php artisan ddd:check --module=Order
```

The command does not alter files and returns a non-zero exit code when violations are found, so it can be used in CI.

## Configuration

The published config lives at:

```text
config/ddd.php
```

Default options:

```php
return [
    'default_preset' => 'hexagonal',
    'modules_path' => 'app/Modules',
    'shared_path' => 'app/Shared',
    'create_repositories' => false,
    'create_policies' => false,
    'create_jobs' => true,
    'create_events' => true,
];
```

## Repositories And Eloquent

Eloquent is supported. Active Record is part of Laravel's productivity story and can coexist with domain-oriented code.

Repositories are disabled by default:

```php
'create_repositories' => false,
```

Create repositories or outbound ports only when they solve a real problem, such as hiding complex persistence, integrating external storage, or protecting application/domain code from query details.

Force a repository generation:

```bash
php artisan make:repository Order OrderRepository --force
```

For hexagonal persistence boundaries, prefer an outbound port plus an infrastructure adapter:

```bash
php artisan make:port Order OrderRepository --type=out
php artisan make:adapter Order EloquentOrderRepository --port=OrderRepository --type=persistence
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

## Contributing

Contributions are welcome. Before opening a pull request, run:

```bash
composer check
```

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

Run static analysis:

```bash
composer analyse
```

Run the full local check:

```bash
composer check
```

When PHP or Composer are not available on the host machine, the same checks can be run with the Psalm Docker image:

```bash
docker run --rm --user 1000:1000 -v "$PWD:/app" -w /app --entrypoint /composer/vendor/bin/psalm ghcr.io/danog/psalm:latest --no-cache --show-info=false
docker run --rm --user 1000:1000 -v "$PWD:/app" -w /app --entrypoint /composer/vendor/bin/psalm ghcr.io/danog/psalm:latest --no-cache --show-info=false --taint-analysis
```

## License

This project is open-sourced software licensed under the [MIT license](LICENSE).
