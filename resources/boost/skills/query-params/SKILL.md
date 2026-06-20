# query-params Skill

## Overview
A powerful, schema-aware API filtering engine for Laravel. It handles complex query parameters (filtering, sorting, field selection, relationship loading, and pagination) using native Laravel validation and highly optimized, database-agnostic Eloquent queries. Natively supports MySQL, PostgreSQL, SQLite, and SQL Server.

## Recommended Usage (2 steps)

### Step 1 — Annotate your FormRequest
```php
// app/Http/Requests/IndexUserRequest.php
use Illuminate\Foundation\Http\FormRequest;
use Victormgomes\QueryParams\Attributes\MapQueryParams;
use App\Models\User;

#[MapQueryParams(User::class)]
class IndexUserRequest extends FormRequest
{
    public function authorize(): bool { return true; }
}
```

It generates the validation rules based on the model schema.

### Step 2 — Pass the request to the generated method anywhere in your app
```php
// app/Http/Controllers/UserController.php
use Illuminate\Pagination\LengthAwarePaginator;

public function index(IndexUserRequest $request): LengthAwarePaginator
{
    // Pass the request to apply all URL parameters inside Controllers, Services, etc.
    return User::paginateQuery($request);
}
```

## Available Model Methods

| Method | Returns | Use case |
|---|---|---|
| `Model::paginateQuery($r)` | `LengthAwarePaginator` | Full pipeline — filters, sorts, includes, pagination |
| `Model::cursorPaginateQuery($r)` | `CursorPaginator` | Full pipeline — cursor pagination (massive datasets) |
| `Model::buildQuery($r)` | `Eloquent\Builder` | Query before pagination — chain custom constraints |
| `Model::getQueryRules()` | `array` | Validation rules for the model |
| `Model::getFilterSchema()` | `array` | Deduplicated frontend schema |

*(Omitting `$request` uses the current request natively. IDE Autocompletion works out of the box).*

## URL Syntax Examples

The package supports two URL formats.

**1. JSON Syntax:**
`?filters={"name":{"like":"John"},"status":"active"}`
`?sorts={"created_at":"desc"}`
`?includes={"posts":{"fields":["id","title"]}}`

**2. Array Syntax:**
`?filters[name][like]=John`
`?sorts[created_at]=desc`
`?includes[posts][fields]=id,title`

## Advanced Usage & Documentation

If you need to implement complex features (such as Local Scopes, Accessors, Aggregations via `#[QueryOptions]`, specific Date/FTS operators, or rule interception), please consult the official package documentation and source code to ensure accurate implementation.

You can view the full documentation and code in the vendor directory:
- **Docs:** `vendor/victormgomes/query-params/docs/`
- **Source code:** `vendor/victormgomes/query-params/src/`
