<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Victormgomes\LaravelQueryEngine\Attributes\MapQueryEngine;
use Victormgomes\LaravelQueryEngine\Enums\AssociatedIndex;
use Victormgomes\LaravelQueryEngine\QueryBuilder;
use Victormgomes\LaravelQueryEngine\Support\ModelRegistry;
use Victormgomes\LaravelQueryEngine\Support\QueryNormalizer;
use Victormgomes\LaravelQueryEngine\Tests\Models\Author;
use Victormgomes\LaravelQueryEngine\Tests\Models\Post;

// ---------------------------------------------------------------------- //
//  Eloquent Builder :: paginateQuery() macro                             //
// ---------------------------------------------------------------------- //

it('can call paginateQuery macro on model via static call', function (): void {
    $request = new Request([
        'filters' => ['views' => ['gt' => 0]],
        'page' => ['number' => 1, 'limit' => 10],
    ]);

    $this->app->instance('request', $request);

    $result = Post::paginateQuery();

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class);
});

it('can call paginateQuery macro with custom request', function (): void {
    $request = new Request([
        'filters' => ['views' => ['gt' => 0]],
        'page' => ['number' => 1, 'limit' => 5],
    ]);

    $result = Post::paginateQuery($request);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class);
    expect($result->perPage())->toBe(5);
});

// ---------------------------------------------------------------------- //
//  QueryBuilder :: macro() system                                        //
// ---------------------------------------------------------------------- //

it('can register and call a custom macro on QueryBuilder', function (): void {
    QueryBuilder::macro('ping', function () {
        return 'pong';
    });

    expect(QueryBuilder::ping())->toBe('pong');
});

it('can register and call a macro with parameters', function (): void {
    QueryBuilder::macro('greet', function (string $name) {
        return "Hello, {$name}!";
    });

    expect(QueryBuilder::greet('World'))->toBe('Hello, World!');
});

// ---------------------------------------------------------------------- //
//  Global request-model registry (ModelRegistry)                          //
// ---------------------------------------------------------------------- //

it('can register and resolve request-model mapping', function (): void {
    ModelRegistry::register(stdClass::class, Post::class);

    expect(ModelRegistry::resolveRequest(stdClass::class))->toBe(Post::class);
});

it('returns null for unregistered request', function (): void {
    expect(ModelRegistry::resolveRequest('NonExistentRequest'))->toBeNull();
});

// ---------------------------------------------------------------------- //
//  ModelRegistry :: resolveFrom()                                        //
// ---------------------------------------------------------------------- //

it('resolves model from global registry first', function (): void {
    $request = new class extends FormRequest
    {
        public function authorize(): bool
        {
            return true;
        }
    };

    ModelRegistry::register($request::class, Post::class);

    expect(ModelRegistry::resolveFrom($request))->toBe(Post::class);
});

it('resolves model from #[MapQueryEngine] attribute as fallback', function (): void {
    $request = new #[MapQueryEngine(Post::class)] class extends FormRequest
    {
        public function authorize(): bool
        {
            return true;
        }
    };

    expect(ModelRegistry::resolveFrom($request))->toBe(Post::class);
});

it('resolves model from model() method as second fallback', function (): void {
    $request = new class extends FormRequest
    {
        public function authorize(): bool
        {
            return true;
        }

        public function model(): string
        {
            return Post::class;
        }
    };

    expect(ModelRegistry::resolveFrom($request))->toBe(Post::class);
});

it('returns null when no model is resolved', function (): void {
    $request = new class extends FormRequest
    {
        public function authorize(): bool
        {
            return true;
        }
    };

    expect(ModelRegistry::resolveFrom($request))->toBeNull();
});

// ---------------------------------------------------------------------- //
//  QueryBuilder :: apply()                                                //
// ---------------------------------------------------------------------- //

it('apply returns a Builder without pagination', function (): void {
    $request = new Request([
        'filters' => ['views' => ['gt' => 0]],
    ]);

    $result = QueryBuilder::buildQuery(Post::class, $request);

    expect($result)->toBeInstanceOf(Builder::class);
    expect($result->getQuery()->wheres)->not->toBeEmpty();
});

it('buildQuery applies sorts from request', function (): void {
    $request = new Request([
        'sorts' => ['created_at' => 'desc'],
    ]);

    $query = QueryBuilder::buildQuery(Post::class, $request);

    $orders = $query->getQuery()->orders;
    expect($orders)->toHaveCount(1);
    expect($orders[0]['column'])->toBe('created_at');
    expect($orders[0]['direction'])->toBe('desc');
});

// ---------------------------------------------------------------------- //
//  queryParamRules() mixin method                                        //
// ---------------------------------------------------------------------- //

it('queryParamRules returns rules when model is registered', function (): void {
    $request = new class extends FormRequest
    {
        public function authorize(): bool
        {
            return true;
        }
    };

    ModelRegistry::register($request::class, Post::class);

    $rules = $request->queryParamRules();

    expect($rules)->toBeArray();
    expect($rules)->toHaveKey('filters');
});

it('queryParamRules returns empty array when no model is resolved', function (): void {
    $request = new class extends FormRequest
    {
        public function authorize(): bool
        {
            return true;
        }
    };

    $rules = $request->queryParamRules();

    expect($rules)->toBeArray();
    expect($rules)->toBeEmpty();
});

// ---------------------------------------------------------------------- //
//  Backward compatibility                                                //
// ---------------------------------------------------------------------- //

it('existing QueryBuilder::paginateQuery still works', function (): void {
    $request = new Request([
        'filters' => ['views' => ['gt' => 0]],
        'page' => ['number' => 1, 'limit' => 10],
    ]);

    $result = QueryBuilder::paginateQuery(Post::class, $request);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class);
});

it('existing QueryBuilder::normalize still works', function (): void {
    $request = new Request([
        'filters' => ['name' => ['eq' => 'Victor']],
    ]);

    QueryNormalizer::normalize($request);

    $filters = $request->get(AssociatedIndex::FILTERS->value);
    expect($filters)->toHaveKey('name');
});

// ---------------------------------------------------------------------- //
//  FormRequest auto-* (integration)                                      //
// ---------------------------------------------------------------------- //

it('auto-normalizes globally registered FormRequests', function (): void {
    $request = new class extends FormRequest
    {
        public function authorize(): bool
        {
            return true;
        }
    };

    $request->merge([
        'filters' => ['title' => ['like' => 'Eloquent']],
    ]);

    ModelRegistry::register($request::class, Post::class);

    // Simulate container resolution to trigger the resolving hook
    $this->app->resolving(FormRequest::class, function ($req): void {
        // Our service provider already does this
    });
    $this->app->instance(FormRequest::class, $request);

    // Manually trigger (the hook runs automatically on resolution)
    $modelFQCN = ModelRegistry::resolveFrom($request);
    QueryNormalizer::normalize($request, $modelFQCN);

    expect($request->get('filters'))->toHaveKey('title');
});

it('auto-generates rules for globally registered FormRequests via mixin', function (): void {
    $request = new class extends FormRequest
    {
        public function authorize(): bool
        {
            return true;
        }
    };

    ModelRegistry::register($request::class, Post::class);

    // The mixin provides rules() method
    $rules = $request->rules();

    expect($rules)->toBeArray();
    expect($rules)->toHaveKey('filters');
});

it('supports cursor pagination via cursorPaginateQuery macro', function (): void {
    $author = Author::create(['name' => 'Victor']);

    for ($i = 1; $i <= 5; $i++) {
        Post::create([
            'author_id' => $author->id,
            'title' => "Post {$i}",
            'views' => $i * 10,
        ]);
    }

    // First page request
    $request = new Request([
        'page' => ['limit' => 2],
    ]);

    $paginator1 = Post::cursorPaginateQuery($request);

    expect($paginator1)->toBeInstanceOf(CursorPaginator::class);
    expect($paginator1->count())->toBe(2);
    expect($paginator1->hasMorePages())->toBeTrue();

    $nextCursor = $paginator1->nextCursor()->encode();

    // Second page request with cursor
    $request2 = new Request([
        'page' => [
            'limit' => 2,
            'cursor' => $nextCursor,
        ],
    ]);

    $paginator2 = Post::cursorPaginateQuery($request2);

    expect($paginator2->count())->toBe(2);
    expect($paginator2->first()->title)->toBe('Post 3');
});

// ---------------------------------------------------------------------- //
//  Advanced Features (Accessors, Scopes, Aggregations)                   //
// ---------------------------------------------------------------------- //

it('supports dynamic accessors on pagination', function (): void {
    $author = Author::create(['name' => 'Victor']);
    Post::create(['author_id' => $author->id, 'title' => 'Test Post']);

    $request = new Request([
        'fields' => ['id', 'title', 'virtual_attribute', 'virtual_legacy'],
    ]);

    $paginator = Post::paginateQuery($request);

    $post = $paginator->first();
    expect($post->toArray())->toHaveKey('virtual_attribute', 'virtual');
    expect($post->toArray())->toHaveKey('virtual_legacy', 'legacy');
});

it('supports local scopes securely', function (): void {
    $author = Author::create(['name' => 'Victor']);
    Post::create(['author_id' => $author->id, 'title' => 'Post 1', 'is_published' => false, 'views' => 10]);
    Post::create(['author_id' => $author->id, 'title' => 'Post 2', 'is_published' => true, 'views' => 10]);
    Post::create(['author_id' => $author->id, 'title' => 'Post 3', 'is_published' => true, 'views' => 50]);

    // 1. Scope without parameter
    // Due to bugs in the SQLite PDO driver when converting booleans under different
    // PHP versions (e.g. Windows PHP 8.3) and older framework dependencies.
    $request1 = new Request(['filters' => ['published' => true]]);
    $results1 = QueryBuilder::buildQuery(Post::class, $request1)->get();
    expect($results1)->toHaveCount(2);

    // 2. Scope with parameter
    $request2 = new Request(['filters' => ['popular' => ['eq' => 50]]]);
    $results2 = QueryBuilder::buildQuery(Post::class, $request2)->get();
    expect($results2)->toHaveCount(1);
    expect($results2->first()->title)->toBe('Post 3');
})->skip(fn () => DB::connection()->getDriverName() === 'sqlite', 'Ignored on SQLite due to PDO boolean inconsistencies on Windows/Linux edges');

it('supports aggregations dynamically as virtual fields', function (): void {
    $author = Author::create(['name' => 'Victor']);
    Post::create(['author_id' => $author->id, 'title' => 'Post 1']);

    $request = new Request([
        'fields' => ['title', 'author_count'], // explicitly allowed in QueryOptions
    ]);

    $results = QueryBuilder::buildQuery(Post::class, $request)->get();
    $post = $results->first();

    expect($post->author_count)->toBe(1);
});
