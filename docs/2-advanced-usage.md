# Advanced Usage

## Intercepting and Modifying Rules

When your FormRequest requires custom validation alongside the package's
generated rules, use the `$this->queryParamRules()` mixin method to merge them
seamlessly.

```php
use Victormgomes\LaravelQueryEngine\Traits\HasQueryEngineRules;

#[MapQueryEngine(User::class)]
class IndexUserRequest extends FormRequest
{
    // Rename the trait method so we can call it inside our custom rules()
    use HasQueryEngineRules {
        rules as queryEngineRules;
    }

    public function rules(): array
    {
        return array_merge(
            $this->queryEngineRules(),
            [
                'custom_header' => 'required|string',
            ]
        );
    }
}
```

## Global Registry (Alternative Resolution)

If you cannot modify the FormRequest class directly (e.g., in a third-party
package), register the request-model mapping globally:

```php
use Victormgomes\LaravelQueryEngine\Support\ModelRegistry;

ModelRegistry::register(IndexUserRequest::class, User::class);
```

## Automatic Type Casting

Values are automatically cast into native PHP types based on your schema before
hitting Eloquent.

| Database Type       | Cast              | Example           |
| :------------------ | :---------------- | :---------------- |
| `integer`, `bigint` | `(int)`           | `100`             |
| `float`, `decimal`  | `(float)`         | `9.99`            |
| `boolean`           | `filter_var()`    | `true`            |
| `date`, `datetime`  | `Carbon::parse()` | `Carbon` instance |

## Frontend Dynamic Filters (Metadata)

The package provides methods to export the schema for frontend applications
building dynamic filter UIs.

```php
use Victormgomes\LaravelQueryEngine\Support\Resource;

// Deduplicated schema (Recommended for Frontend integration)
return Resource::getFilterSchema(User::class);

// Full exhaustive schema with all aliases and syntax mapping
return Resource::getQueryGuide(User::class);
```

## The `#[QueryOptions]` Attribute

The core configuration for what is exposed over the API happens via the
`#[QueryOptions]` attribute directly on your Eloquent Model. It acts as a
gatekeeper, leveraging a "Whitelist vs Blacklist" architecture.

```php
use Victormgomes\LaravelQueryEngine\Attributes\QueryOptions;

#[QueryOptions(
    // Features can be disabled entirely
    filters: true,

    // Blacklisting (Auto-discovery enabled, these are disabled)
    disableFilters: ['password', 'secret_token'],
    disableFields: ['internal_id'],

    // Whitelisting (Auto-discovery disabled, STRICTLY these are allowed)
    // allowedFilters: ['id', 'name'],

    // Security Opt-Ins (Disabled by default)
    allowedScopes: ['active', 'ofRole'],
    allowedAggregations: ['posts_count', 'comments_avg_rating'],
)]
class User extends Model
{
    // ...
}
```

### 1. Filtering by Local Scopes

Scopes must be explicitly registered in `allowedScopes`. Once registered, they
act as virtual boolean/string filters.

```php
// In Model: public function scopeActive($query) { ... }
// URL: ?filters[active]=true
```

If the scope accepts parameters (e.g. `scopeOfRole($query, $role)`):

```php
// URL: ?filters[ofRole][eq]=admin
```

### 2. Selecting Accessors (Appends)

Accessors (`getFirstNameAttribute` or `firstName(): Attribute`) and properties
in the `$appends` array are automatically discovered and can be selected via the
`fields` parameter. You can block them using `disableFields` or strictly control
them via `allowedFields`.

```php
// URL: ?fields[]=id&fields[]=name&fields[]=first_name
```

### 3. Requesting Aggregations (Counts & Sums)

Aggregations can cause performance issues if exposed globally. Therefore, you
must explicitly opt-in via `allowedAggregations`. The package supports `_count`,
`_exists`, `_sum_column`, `_avg_column`, `_min_column`, and `_max_column`.

```php
// QueryOptions(allowedAggregations: ['posts_count', 'orders_sum_total'])
// URL: ?fields[]=id&fields[]=name&fields[]=posts_count&fields[]=orders_sum_total
```

### 4. Overriding Global Operators

Sometimes you want to globally allow a certain operator in
`config/query-engine.php` (like `like` or `fts`), but restrict it on a specific
model for performance reasons. You can use `disableOperators` or
`allowedOperators`.

```php
#[QueryOptions(
    // Disable expensive operators just for this model
    disableOperators: ['fts', 'like'],
)]
class LogEntry extends Model
{
    // ...
}
```
