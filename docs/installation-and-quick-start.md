# Installation & Quick Start

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
use Victormgomes\LaravelQueryEngine\Traits\HasQueryEngineRules;
use App\Models\User;

#[MapQueryEngine(User::class)]
class IndexUserRequest extends FormRequest
{
    use HasQueryEngineRules;

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
