<?php

namespace LaravelScaffolding\Scaffolding;

use Illuminate\Support\ServiceProvider;
use LaravelScaffolding\Scaffolding\Commands\MakeModuleCommand;
use LaravelScaffolding\Scaffolding\Support\StubRenderer;

class ScaffoldingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/scaffolding.php', 'scaffolding');

        // Bind StubRenderer with the configured custom stubs path
        $this->app->bind(StubRenderer::class, static function ($app): StubRenderer {
            return new StubRenderer(
                $app['config']->get('scaffolding.stubs_path')
            );
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            // Register the artisan command
            $this->commands([
                MakeModuleCommand::class,
            ]);

            // Publish config
            $this->publishes([
                __DIR__ . '/../config/scaffolding.php' => config_path('scaffolding.php'),
            ], 'scaffolding-config');

            // Publish stubs (so the user can customise them)
            $this->publishes([
                __DIR__ . '/../stubs' => base_path('stubs/scaffolding'),
            ], 'scaffolding-stubs');
        }
    }
}
