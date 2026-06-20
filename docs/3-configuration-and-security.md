# Configuration & Security

## Console Commands (Caching)

To prevent database introspection overhead on every request in production, cache the generated rules.

```bash
php artisan query-params:cache                   # Scan models and cache all rules
php artisan query-params:clear                   # Clear all cached rules
php artisan query-params:clear App\Models\User   # Clear rules for a specific model
```

## Configuration Options

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

## Security & Visibility

The package is strictly bounded by your model's visibility configuration to prevent data leaks.

1. **`$visible` (Allow-list):** If defined, only these columns can be filtered, sorted, or selected.
2. **`$hidden` (Deny-list):** Columns in this array are strictly forbidden from all query operations.

## Pluggable Drivers

Extend the package to handle custom database behaviors by defining a Resolver.

```php
// config/query-params.php
'drivers' => [
    'default' => \App\Support\QueryDrivers\CustomDriver::class,
],
```

Your driver must implement the `Victormgomes\QueryParams\Contracts\FieldResolver` interface.
