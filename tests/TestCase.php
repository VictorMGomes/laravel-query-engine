<?php

declare(strict_types=1);

namespace Victormgomes\QueryParams\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;
use Victormgomes\QueryParams\QueryParamsServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Victormgomes\\QueryParams\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            QueryParamsServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');

        $schema = $app['db']->connection()->getSchemaBuilder();

        $schema->create('authors', function ($table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        $schema->create('posts', function ($table) {
            $table->id();
            $table->foreignId('author_id');
            $table->string('title');
            $table->integer('views')->default(0);
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->json('tags')->nullable();
            $table->timestamps();
        });
    }
}
