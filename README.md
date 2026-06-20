# Query Params for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/victormgomes/query-params.svg?style=flat-square)](https://packagist.org/packages/victormgomes/query-params)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/victormgomes/query-params/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/victormgomes/query-params/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/victormgomes/query-params/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/victormgomes/query-params/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/victormgomes/query-params.svg?style=flat-square)](https://packagist.org/packages/victormgomes/query-params)
[![License](https://img.shields.io/packagist/l/victormgomes/query-params.svg?style=flat-square)](https://packagist.org/packages/victormgomes/query-params)

A powerful, schema-aware API filtering engine for Laravel. It handles complex query parameters (filtering, sorting, field selection, relationship loading, and pagination) using native Laravel validation and highly optimized, database-agnostic Eloquent queries.

## Why Use It?

When building modern APIs, developers often face a difficult choice: write dozens of custom endpoints for every specific search context (manually validating and mapping queries for each one), or abandon REST entirely for heavy data-graph abstractions like GraphQL. 

This package gives you the power of dynamic querying while keeping your application native and RESTful. By empowering your standard Laravel `index` endpoints, you can:
- **Avoid Endpoint Sprawl:** You no longer need to write custom search endpoints (e.g., `/users/active`, `/users/recent`). A single endpoint safely handles infinitely complex combinations of filters, sorts, and includes.
- **Stay Strictly RESTful:** Keep your architecture native to Laravel without introducing the massive overhead, caching complexities, and learning curves associated with GraphQL.
- **Zero-Boilerplate Security:** It acts as a **Schema-Aware Translation Layer**. It automatically inspects your Eloquent model schema to whitelist columns, generate strict validation rules, and securely cast URL values into native PHP types before they ever hit the database.

## Features

- **Schema-Aware Validation:** Validation rules and type casting are automatically generated from your Eloquent model schema.
- **High Performance Caching:** Automatically caches generated rules to prevent schema introspection overhead in production.
- **Global Security Layer:** Granularly enable or disable specific URL features (like filtering, sorting, or includes) globally via the configuration file.
- **AI Agent Ready:** Ships with a built-in Laravel Boost Skill to instantly teach AI coding assistants how to use the package in your project.
- **API Documentation Ready:** Exposes methods to retrieve deduplicated filter schemas, making it incredibly easy to auto-generate OpenAPI/Swagger specs or dynamic frontend UIs.
- **Database Agnostic:** Natively supports MySQL, PostgreSQL, SQLite, and SQL Server out of the box via standard Eloquent methods.
- **Two Syntax Formats:** Support for both standard Laravel Arrays and raw JSON strings in the URL.
- **Strict Visibility:** Fully respects `$visible` and `$hidden` arrays on your models to prevent data exposure.
- **Local Scopes & Aggregations:** Securely expose eloquent scopes and aggregate functions (`_count`, `_sum`) via the URL using the strict `#[QueryOptions]` attribute.
- **Virtual Fields (Accessors):** Automatically discovers and allows selecting Accessors and `$appends` just like real columns.
- **Advanced Modifiers:** Built-in Universal Full-Text Search (`fts`) and Date component matching (`year`, `month`, `day`).
- **IDE Autocompletion:** Plug-and-play autocomplete for all macros out of the box.

---

## Installation

1. Install the package via Composer:

```bash
composer require victormgomes/query-params
```

2. Publish the configuration file:

```bash
php artisan vendor:publish --tag="query-params-config"
```

---

## Quick Start

### Step 1: Auto-generate validation rules

Annotate your FormRequest with `#[MapQueryParams(Model::class)]`.

```php
// app/Http/Requests/IndexUserRequest.php
use Illuminate\Foundation\Http\FormRequest;
use Victormgomes\QueryParams\Attributes\MapQueryParams;
use App\Models\User;

#[MapQueryParams(User::class)]
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

The package automatically adds powerful new methods directly to all your Eloquent models. You can call these anywhere in your application (such as a Controller or a Service class) by simply passing the validated request:

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

This returns a `LengthAwarePaginator` with all valid filters, sorts, includes, and pagination automatically applied.

---

## Available Model Methods

All Eloquent models are automatically equipped with the following methods out of the box:

```php
// Full pipeline: returns a paginated result
User::paginateQuery(?Request $request = null): LengthAwarePaginator

// Full pipeline: returns a cursor-paginated result (for massive datasets)
User::cursorPaginateQuery(?Request $request = null): CursorPaginator

// Raw builder: chain additional constraints before pagination
User::buildQuery(?Request $request = null): Eloquent\Builder

// Retrieve the auto-generated validation rules
User::getQueryRules(): array

// Retrieve a deduplicated schema representing allowed filters and includes for frontends
User::getFilterSchema(): array
```

---

## Documentation

For a deep dive into the features, please read the official documentation:

- [URL Syntax & Supported Filters](docs/1-url-syntax-and-filters.md)
- [Advanced Usage & Frontend Integration](docs/2-advanced-usage.md)
- [Configuration, Caching & Security](docs/3-configuration-and-security.md)

---

## Testing

```bash
composer run test
```

## Credits

- [Victor M. Gomes](https://github.com/victormgomes)
- [All Contributors](../../contributors)

## Support Us

If you find this package useful in your day-to-day development, please consider [sponsoring my work](https://github.com/sponsors/VictorMGomes) or leaving a ⭐ on the repository. Your support directly helps keep this project actively maintained and free!

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

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
