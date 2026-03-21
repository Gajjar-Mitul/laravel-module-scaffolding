<?php

namespace LaravelScaffolding\Scaffolding\Generators;

use Illuminate\Support\Str;
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
        $variable = Str::snake($config->className());
        $controller = $config->controllerNamespace() . '\\' . $config->className() . 'Controller';

        // Guard: skip if this route group already exists
        if (str_contains($existing, "->name('{$routeName}.index')")) {
            return [];
        }

        $routeBlock = $this->buildRouteBlock($config, $controller, $routeName, $variable);

        File::append($routesFile, $routeBlock);

        return [$routesFile];
    }

    private function buildRouteBlock(ModuleConfig $config, string $controller, string $routeName, string $variable): string
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
            $dataRoute = "\n        Route::get('{$routeName}/data', [\\{$controller}::class, 'data'])->name('{$routeName}.data');";
        }

        $block = <<<PHP


// ── {$config->classNamePlural()} ──────────────────────────────────────────────────────────────
Route::middleware([{$middlewareStr}])
{$prefixLine}    ->group(static function (): void {
    Route::get('{$routeName}', [\\{$controller}::class, 'index'])->name('{$routeName}.index');
    Route::get('{$routeName}/create', [\\{$controller}::class, 'create'])->name('{$routeName}.create');
    Route::post('{$routeName}', [\\{$controller}::class, 'store'])->name('{$routeName}.store');
    Route::get('{$routeName}/{{$variable}}', [\\{$controller}::class, 'show'])->name('{$routeName}.show');
    Route::get('{$routeName}/{{$variable}}/edit', [\\{$controller}::class, 'edit'])->name('{$routeName}.edit');
    Route::put('{$routeName}/{{$variable}}', [\\{$controller}::class, 'update'])->name('{$routeName}.update');
    Route::patch('{$routeName}/{{$variable}}', [\\{$controller}::class, 'update']);
    Route::delete('{$routeName}/{{$variable}}', [\\{$controller}::class, 'destroy'])->name('{$routeName}.destroy');{$dataRoute}
    });
PHP;

        // Clean up double empty line if no prefix
        return str_replace("\n\n    ->group", "\n    ->group", $block);
    }
}
