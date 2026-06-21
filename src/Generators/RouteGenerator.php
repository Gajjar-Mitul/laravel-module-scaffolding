<?php

namespace LaravelScaffolding\Scaffolding\Generators;

use Illuminate\Support\Facades\File;
use LaravelScaffolding\Scaffolding\Support\ModuleConfig;

final class RouteGenerator
{
    /**
     * Append the route block to the configured routes file.
     *
     * @return string[]  The routes file path (if modified)
     */
    public function generate(ModuleConfig $config): array
    {
        $routesFile = base_path($config->routesFile);

        if (!File::exists($routesFile)) {
            return [];
        }

        $existing = File::get($routesFile);
        $routeName = $config->routeName();
        $uri = $config->routeUriSegment();
        $variable = $config->routeParameter();
        $controller = $config->controllerNamespace() . '\\' . $config->controllerClassName();

        // Guard: skip if this route group already exists
        if (str_contains($existing, "->name('{$routeName}.index')")) {
            return [];
        }

        $routeBlock = $this->buildRouteBlock($config, $controller, $routeName, $uri, $variable);

        File::append($routesFile, $routeBlock);

        return [$routesFile];
    }

    private function buildRouteBlock(ModuleConfig $config, string $controller, string $routeName, string $uri, string $variable): string
    {
        $middlewareList = array_map(
            static fn(string $m) => "'{$m}'",
            $config->middleware
        );
        $middlewareStr = implode(', ', $middlewareList);

        $prefix     = $config->routePrefix ? "'{$config->routePrefix}'" : null;
        $prefixLine = $prefix ? "    ->prefix({$prefix})" : '';

        $dataRoute = '';
        if ($config->useDataTable) {
            $dataRoute = "\n        Route::get('{$uri}/data', [\\{$controller}::class, 'data'])->name('{$routeName}.data');";
        }

        $block = <<<PHP


// ── {$config->classNamePlural()} ──────────────────────────────────────────────────────────────
Route::middleware([{$middlewareStr}])
{$prefixLine}    ->group(static function (): void {
    Route::get('{$uri}', [\\{$controller}::class, 'index'])->name('{$routeName}.index');
    Route::get('{$uri}/create', [\\{$controller}::class, 'create'])->name('{$routeName}.create');
    Route::post('{$uri}', [\\{$controller}::class, 'store'])->name('{$routeName}.store');
    Route::get('{$uri}/{{$variable}}', [\\{$controller}::class, 'show'])->name('{$routeName}.show');
    Route::get('{$uri}/{{$variable}}/edit', [\\{$controller}::class, 'edit'])->name('{$routeName}.edit');
    Route::put('{$uri}/{{$variable}}', [\\{$controller}::class, 'update'])->name('{$routeName}.update');
    Route::patch('{$uri}/{{$variable}}', [\\{$controller}::class, 'update']);
    Route::delete('{$uri}/{{$variable}}', [\\{$controller}::class, 'destroy'])->name('{$routeName}.destroy');{$dataRoute}
    });
PHP;

        // Clean up double empty line if no prefix
        return str_replace("\n\n    ->group", "\n    ->group", $block);
    }
}
