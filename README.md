# Laravel Query Engine

Automatically generates dynamic API parameters, strict validation, and optimized
queries based on Eloquent Models.

## Package Status

[![Latest Version on Packagist](https://img.shields.io/packagist/v/victormgomes/laravel-query-engine.svg?style=flat-square)](https://packagist.org/packages/victormgomes/laravel-query-engine)
[![Total Downloads](https://img.shields.io/packagist/dt/victormgomes/laravel-query-engine.svg?style=flat-square)](https://packagist.org/packages/victormgomes/laravel-query-engine)
[![License](https://img.shields.io/packagist/l/victormgomes/laravel-query-engine.svg?style=flat-square)](https://packagist.org/packages/victormgomes/laravel-query-engine)

[![PHP Versions](https://img.shields.io/badge/PHP-8.3_|_8.4_|_8.5-777BB4.svg?style=flat-square&logo=php)](https://php.net/)
[![Laravel Versions](https://img.shields.io/badge/Laravel-12.x_|_13.x-22C55E.svg?style=flat-square&logo=laravel)](https://laravel.com/)

[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/victormgomes/laravel-query-engine/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/victormgomes/laravel-query-engine/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/victormgomes/laravel-query-engine/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/victormgomes/laravel-query-engine/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![GitHub Code Quality Action Status](https://img.shields.io/github/actions/workflow/status/victormgomes/laravel-query-engine/code-quality.yml?branch=main&label=PHPStan%20%26%20Insights&style=flat-square)](https://github.com/victormgomes/laravel-query-engine/actions?query=workflow%3A"Code+Quality+%26+Static+Analysis"+branch%3Amain)

## Why Use It?

This package saves your time and tokens!

Stop writing repetitive boilerplate for every index endpoint. `laravel-query-engine`
acts as a seamless bridge between your HTTP requests and Eloquent.

It empowers a single RESTful controller to handle dynamic, infinitely complex
API queries. It automatically handles all the heavy lifting—validation, type
casting, and query construction—while respecting your model's native
configuration.

You get the extreme flexibility of GraphQL, but with the simplicity and
performance of standard Laravel REST APIs.

## Features

- **Automated Validation:** Generates strict validation rules directly from your
  database schema.
- **Dynamic Query Building:** Translates validated URL parameters directly into
  native Eloquent builder actions.
- **Strict Type Casting:** Inspects your schema to accurately cast URL strings
  into their correct PHP types (integers, booleans, dates).
- **Deep Security:** Natively respects your model's existing visibility
  configurations to prevent unmapped column exposure.
- **Advanced Querying:** Out-of-the-box support for full-text search, complex
  date filters, and nested logical groupings.
- **Model-Level Configuration:** Use native PHP attributes directly on your
  models to securely expose Local Scopes and query aggregations.
- **Exportable Schemas:** Easily export deduplicated filter schemas to generate
  dynamic frontend UIs or OpenAPI documentation.
- **AI Agent Ready:** Includes an official [Laravel Boost](https://github.com/laravel/boost) skill to automatically teach AI agents (like Claude Code and Cursor) how to use this package in your project.

## How It Works

Pass dynamic query parameters via the URL using standard arrays or JSON.

**The Request:**

```text
GET /api/users?filters={"name":{"like":"John"},"status":"active"}&sorts={"created_at":"desc"}&includes={"posts":{}}
```

**What the package executes under the hood:**

```php
User::where('name', 'LIKE', '%John%')
    ->where('status', 'active')
    ->orderBy('created_at', 'desc')
    ->with('posts')
    ->paginate();
```

---

## Installation

1. Install the package via Composer:

```bash
composer require victormgomes/laravel-query-engine
```

1. Publish the configuration file:

```bash
php artisan vendor:publish --tag="laravel-query-engine-config"
```

---

## Quick Start

### Step 1: Auto-generate validation rules

Annotate your FormRequest with `#[MapQueryEngine(Model::class)]`.

```php
// app/Http/Requests/IndexUserRequest.php
use Illuminate\Foundation\Http\FormRequest;
use Victormgomes\LaravelQueryEngine\Attributes\MapQueryEngine;
use App\Models\User;

#[MapQueryEngine(User::class)]
class IndexUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
}
```

It generates the validation rules based on the model schema.

### Step 2: Build the query

The package automatically adds powerful new methods directly to all your
Eloquent models. You can call these anywhere in your application (such as a
Controller or a Service class) by simply passing the validated request:

```php
// app/Http/Controllers/UserController.php
use App\Models\User;
use App\Http\Requests\IndexUserRequest;
use Illuminate\Pagination\LengthAwarePaginator;

public function index(IndexUserRequest $request): LengthAwarePaginator
{
    // Pass the request to automatically apply all URL parameters
    return User::paginateQuery($request);
}
```

This returns a `LengthAwarePaginator` with all valid filters, sorts, includes,
and pagination automatically applied.

---

## Documentation

For a deep dive into the features, please read the
[Official Documentation](docs/index.md).

---

## Credits

- [Victor M. Gomes](https://github.com/victormgomes)
- [All Contributors](../../contributors)

## Support Us

If you find this package useful in your day-to-day development, please consider
[sponsoring my work](https://github.com/sponsors/VictorMGomes) or leaving a ⭐
on the repository. Your support directly helps keep this project actively
maintained and free!

---

## Community & Guidelines

- [Upgrading Guide](UPGRADING.md)
- [Changelog](CHANGELOG.md)
- [Contributing](CONTRIBUTING.md)
- [Code of Conduct](CODE_OF_CONDUCT.md)
- [Security Policy](SECURITY.md)
- [Support & Help](SUPPORT.md)

---

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more
information.
