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

## URL Syntax

The package seamlessly supports two distinct formats for URL parameters. You can use whichever format best fits your frontend application.

### 1. JSON Syntax
You can pass raw JSON strings directly into the URL parameter. This is ideal for modern JavaScript frontends that easily serialize objects.

| Operation | Example URL |
| :-------- | :------ |
| **Filters** | `?filters={"name":{"like":"John"}}` |
| **Sorts** | `?sorts={"created_at":"desc"}` |
| **Fields** | `?fields=["id","name"]` |
| **Includes** | `?includes={"posts":{"fields":["id","title"]}}` |
| **Pagination**| `?page={"number":2,"limit":50}` |

### 2. Structured Array Syntax
The standard PHP/Laravel nested array syntax. This is ideal for traditional HTML forms or programmatic URL generation.

| Operation | Example URL |
| :-------- | :------ |
| **Filters** | `?filters[name][like]=John` |
| **Sorts** | `?sorts[created_at]=desc` |
| **Fields** | `?fields[]=id&fields[]=name` |
| **Includes** | `?includes[posts][fields]=id,title` |
| **Pagination**| `?page[number]=2&page[limit]=50` |

---

## Supported Filter Operators

| Operator | Description | URL Example (Array Syntax) | DB Support |
| :------- | :---------- | :---------- | :--------- |
| `eq`, `ne` | Equal / Not Equal | `?filters[status][eq]=active` | Universal |
| `like`, `notlike` | Pattern matching | `?filters[name][like]=John` | Universal |
| `ilike`, `notilike` | Case-insensitive matching | `?filters[email][ilike]=HOTMAIL` | Universal (Graceful fallback) |
| `gt`, `gte` | Greater than (or equal) | `?filters[price][gt]=100` | Universal |
| `lt`, `lte` | Less than (or equal) | `?filters[age][lte]=18` | Universal |
| `in`, `nin` | In list / Not in list | `?filters[id][in]=1,2,3` | Universal |
| `null`, `notnull` | Null checks | `?filters[deleted_at][null]=true` | Universal |
| `between`, `nbetween` | Range queries | `?filters[price][between]=10,50` | Universal |
| `contains` | JSON/Array contains | `?filters[tags][contains]=urgent` | Universal |
| `exists`, `notexists` | Relationship existence | `?filters[posts][exists]=true` | Universal |
| `containedby` | JSON array contained by | `?filters[tags][containedby]=["urgent"]` | PostgreSQL only |
| `overlap` | JSON array overlap | `?filters[tags][overlap]=["urgent"]` | PostgreSQL only |
| `fts` | Full-text search | `?filters[content][fts]=laravel` | PostgreSQL only |

*(Note: PostgreSQL-specific operators securely abort with an `InvalidArgumentException` if executed on non-PostgreSQL engines to prevent raw SQL syntax errors).*

---

## Advanced Usage

### Intercepting and Modifying Rules

When your FormRequest requires custom validation alongside the package's generated rules, use the `$this->queryParamRules()` mixin method to merge them seamlessly.

```php
#[MapQueryParams(User::class)]
class IndexUserRequest extends FormRequest
{
    public function rules(): array
    {
        return array_merge(
            $this->queryParamRules(),
            [
                'custom_header' => 'required|string',
            ]
        );
    }
}
```

### Global Registry (Alternative Resolution)

If you cannot modify the FormRequest class directly (e.g., in a third-party package), register the request-model mapping globally:

```php
use Victormgomes\QueryParams\Support\ModelRegistry;

ModelRegistry::register(IndexUserRequest::class, User::class);
```

### Automatic Type Casting

Values are automatically cast into native PHP types based on your schema before hitting Eloquent.

| Database Type | Cast | Example |
| :------------ | :--- | :------ |
| `integer`, `bigint` | `(int)` | `100` |
| `float`, `decimal` | `(float)` | `9.99` |
| `boolean` | `filter_var()` | `true` |
| `date`, `datetime` | `Carbon::parse()` | `Carbon` instance |

---

## Frontend Dynamic Filters (Metadata)

The package provides methods to export the schema for frontend applications building dynamic filter UIs.

```php
use Victormgomes\QueryParams\Support\Resource;

// Deduplicated schema (Recommended for Frontend integration)
return Resource::getFilterSchema(User::class);

// Full exhaustive schema with all aliases and syntax mapping
return Resource::getQueryGuide(User::class);
```

---

## Console Commands

To prevent database introspection overhead on every request in production, cache the generated rules.

```bash
php artisan query-params:cache                   # Scan models and cache all rules
php artisan query-params:clear                   # Clear all cached rules
php artisan query-params:clear App\Models\User   # Clear rules for a specific model
```

---

## Configuration

After publishing the config file (`config/query-params.php`), the following options are available:

| Key | Env Variable | Default | Description |
| :-- | :----------- | :------ | :---------- |
| `metadata_connection` | `QUERY_PARAMS_METADATA_CONNECTION` | `null` | Custom DB connection for schema inspection |
| `caching.enabled` | `QUERY_PARAMS_CACHE_ENABLED` | `true` | Enable/disable the rules caching layer |
| `caching.ttl` | `QUERY_PARAMS_CACHE_TTL` | `3600` | Cache time-to-live in seconds |
| `force_cache` | `QUERY_PARAMS_FORCE_CACHE` | `false` | Force cache usage outside `production` |
| `debug` | `QUERY_PARAMS_DEBUG` | `false` | Log all generated rules to the Laravel log |
| `pagination.max_limit`| `QUERY_PARAMS_MAX_LIMIT` | `100` | Strict upper limit for items per page |
| `features` | Multiple | `true` | Globally enable/disable filters, sorts, includes |
| `allowed_operators` | — | All operators | Global whitelist of allowed filter operators |
| `drivers` | — | `[]` | Pluggable field resolvers |

---

## Security & Visibility

The package is strictly bounded by your model's visibility configuration to prevent data leaks.

1. **`$visible` (Allow-list):** If defined, only these columns can be filtered, sorted, or selected.
2. **`$hidden` (Deny-list):** Columns in this array are strictly forbidden from all query operations.

---

## Pluggable Drivers

Extend the package to handle custom database behaviors by defining a Resolver.

```php
// config/query-params.php
'drivers' => [
    'translatable' => \App\Support\QueryDrivers\TranslationDriver::class,
],
```

Your driver must implement the `Victormgomes\QueryParams\Contracts\FieldResolver` interface.

---

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
