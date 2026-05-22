<?php

declare(strict_types=1);

use Victormgomes\QueryParams\Enums\Operators;
use Victormgomes\QueryParams\Support\Builder\Operations\Filter;
use Victormgomes\QueryParams\Tests\Models\Post;

it('applies all basic comparison operators', function (string $operator, string $expectedSql) {
    $query = Post::query();
    Filter::build($query, 'views', $operator, 10);

    expect($query->toSql())->toContain($expectedSql);
})->with([
    [Operators::EQ, 'where "views" = ?'],
    [Operators::NE, 'where "views" != ?'],
    [Operators::GT, 'where "views" > ?'],
    [Operators::GTE, 'where "views" >= ?'],
    [Operators::LT, 'where "views" < ?'],
    [Operators::LTE, 'where "views" <= ?'],
]);

it('applies null and not null operators', function (string $operator, string $expectedSql) {
    $query = Post::query();
    Filter::build($query, 'published_at', $operator, null);

    expect($query->toSql())->toContain($expectedSql);
})->with([
    [Operators::NULL, 'where "published_at" is null'],
    [Operators::NOTNULL, 'where "published_at" is not null'],
]);

it('applies in and not in operators', function (string $operator, string $expectedSql) {
    $query = Post::query();
    Filter::build($query, 'id', $operator, [1, 2, 3]);

    expect($query->toSql())->toContain($expectedSql);
})->with([
    [Operators::IN, 'where "id" in (?, ?, ?)'],
    [Operators::NIN, 'where "id" not in (?, ?, ?)'],
]);

it('applies between and not between operators', function (string $operator, string $expectedSql) {
    $query = Post::query();
    Filter::build($query, 'views', $operator, [10, 20]);

    expect($query->toSql())->toContain($expectedSql);
})->with([
    [Operators::BETWEEN, 'where "views" between ? and ?'],
    [Operators::NBETWEEN, 'where "views" not between ? and ?'],
]);

it('applies like and ilike operators', function (string $operator, string $expectedSubSql) {
    $query = Post::query();
    Filter::build($query, 'title', $operator, 'test');

    expect($query->toSql())->toContain($expectedSubSql);
})->with([
    [Operators::LIKE, 'where "title" like ?'],
    [Operators::NOTLIKE, 'where "title" not like ?'],
    [Operators::ILIKE, 'LOWER(title) LIKE LOWER(?)'],
    [Operators::NOTILIKE, 'LOWER(title) NOT LIKE LOWER(?)'],
]);

it('applies json operators', function (string $operator, string $expectedSubSql) {
    $query = Post::query();
    Filter::build($query, 'tags', $operator, 'laravel');

    expect($query->toSql())->toContain($expectedSubSql);
})->with([
    [Operators::CONTAINS, 'json_each("tags")'],
    [Operators::CONTAINEDBY, '? <@ tags'],
]);

it('applies full-text search operator', function () {
    $query = Post::query();
    Filter::build($query, 'title', Operators::FTS, 'search term');

    expect($query->toSql())->toContain('to_tsvector(title) @@ plainto_tsquery(?)');
});
