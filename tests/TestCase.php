<?php

namespace LaravelScaffolding\Scaffolding\Tests;

use Illuminate\Support\Facades\File;
use LaravelScaffolding\Scaffolding\ScaffoldingServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            ScaffoldingServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        File::ensureDirectoryExists(base_path('routes'));
        if (!File::exists(base_path('routes/web.php'))) {
            File::put(base_path('routes/web.php'), "<?php\n\n");
        }
    }
}
