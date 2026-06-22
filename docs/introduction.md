# Introduction

Stop writing repetitive boilerplate for every index endpoint. `laravel-query-engine` acts as a seamless bridge between your HTTP requests and Eloquent.

It empowers a single RESTful controller to handle dynamic, infinitely complex API queries. It automatically handles all the heavy lifting—validation, type casting, and query construction—while respecting your model's native configuration.

You get the extreme flexibility of GraphQL, but with the simplicity and performance of standard Laravel REST APIs.

## Features

- **Automated Validation:** Generates strict validation rules directly from your database schema.
- **Dynamic Query Building:** Translates validated URL parameters directly into native Eloquent builder actions.
- **Strict Type Casting:** Inspects your schema to accurately cast URL strings into their correct PHP types (integers, booleans, dates).
- **Deep Security:** Natively respects your model's existing visibility configurations to prevent unmapped column exposure.
- **Advanced Querying:** Out-of-the-box support for full-text search, complex date filters, and nested logical groupings.
- **Model-Level Configuration:** Use native PHP attributes directly on your models to securely expose Local Scopes and query aggregations.
- **Exportable Schemas:** Easily export deduplicated filter schemas to generate dynamic frontend UIs or OpenAPI documentation.
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
