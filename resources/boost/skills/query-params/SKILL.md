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

## URL Syntax Reference

The package natively decodes two URL formats. Use whichever fits the client application best.

### 1. JSON Syntax
```
?filters={"name":{"like":"John"},"status":"active"}
?sorts={"created_at":"desc"}
?fields=["id","name","email"]
?includes={"posts":{"fields":["id","title"]}}
?page={"number":2,"limit":50}
```

### 2. Structured Array Syntax
```
?filters[name][like]=John
?sorts[created_at]=desc
?fields[]=id&fields[]=name
?includes[posts][fields]=id,title
?page[number]=2&page[limit]=50
```

### Filter Operators Map
- **Universal DB Support:** `eq`, `ne`, `gt`, `gte`, `lt`, `lte`, `in`, `nin`, `null`, `notnull`, `between`, `nbetween`, `like`, `notlike`, `ilike` (graceful fallback), `notilike` (graceful fallback), `contains`, `exists`, `notexists`.
- **PostgreSQL Exclusive:** `containedby`, `overlap`, `fts`. (Securely aborts on non-pgsql).

## Advanced Usage

### Intercepting and Modifying Rules
When a FormRequest needs custom validation alongside the generated rules:
```php
#[MapQueryParams(User::class)]
class IndexUserRequest extends FormRequest
{
    public function rules(): array
    {
        return array_merge($this->queryParamRules(), [
            'custom_field' => 'required|string',
        ]);
    }
}
```

### Alternative entry points
```php
QueryBuilder::buildQuery(User::class, $request);         // Eloquent\Builder
QueryBuilder::paginateQuery(User::class, $request);      // LengthAwarePaginator
QueryBuilder::cursorPaginateQuery(User::class, $request); // CursorPaginator

// Via facade
QueryParams::paginateQuery(User::class, $request);
QueryBuilder::paginateQuery(User::class, $request);
```

### Global Registry (no attribute)
Use when you cannot modify the FormRequest class:
```php
ModelRegistry::register(IndexUserRequest::class, User::class);
```

### Frontend schema
```php
Resource::getFilterSchema(User::class);   // deduplicated, recommended
Resource::getQueryGuide(User::class);     // with syntax hints
```



### Console commands
Cache validation rules in production to prevent schema introspection overhead.
```bash
php artisan query-params:cache                 # pre-cache all model rules
php artisan query-params:clear App\Models\User  # clear specific cache
```

### Type Casting Map
| DB Column | PHP Type |
|---|---|
| `integer`, `bigint`, `smallint` | `(int)` |
| `float`, `decimal`, `numeric` | `(float)` |
| `boolean` | `filter_var($val, FILTER_VALIDATE_BOOLEAN)` |
| `date`, `datetime`, `timestamp` | `Carbon::parse()` |

## Key Architectural Principles
- **Schema-Aware Security:** Unknown URL parameters and unauthorized columns throw `ValidationException`.
- **Global Security Layer:** Granularly enable or disable features (like filtering or sorting) globally.
- **Visibility Enforced:** `$visible` / `$hidden` properties on models are strictly respected.
- **High Performance:** Built-in caching prevents schema introspection overhead in production environments.
- **Strategy Pattern Routing:** Operations are cleanly delegated to focused Handler classes for maximum performance and isolation.
- **Database Agnosticism:** Operations translate to universal Eloquent methods, preserving full cross-database compatibility without raw SQL crashes.
