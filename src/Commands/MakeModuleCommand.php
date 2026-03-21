<?php

namespace LaravelScaffolding\Scaffolding\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use LaravelScaffolding\Scaffolding\Detectors\DataTableDetector;
use LaravelScaffolding\Scaffolding\Detectors\LayoutDetector;
use LaravelScaffolding\Scaffolding\Detectors\SchemaDetector;
use LaravelScaffolding\Scaffolding\Detectors\ValidationDetector;
use LaravelScaffolding\Scaffolding\Generators\ControllerGenerator;
use LaravelScaffolding\Scaffolding\Generators\EnumGenerator;
use LaravelScaffolding\Scaffolding\Generators\JavaScriptGenerator;
use LaravelScaffolding\Scaffolding\Generators\MigrationGenerator;
use LaravelScaffolding\Scaffolding\Generators\ModelGenerator;
use LaravelScaffolding\Scaffolding\Generators\QueryGenerator;
use LaravelScaffolding\Scaffolding\Generators\RequestGenerator;
use LaravelScaffolding\Scaffolding\Generators\RouteGenerator;
use LaravelScaffolding\Scaffolding\Generators\ServiceGenerator;
use LaravelScaffolding\Scaffolding\Generators\ViewGenerator;
use LaravelScaffolding\Scaffolding\Resolvers\FieldResolver;
use LaravelScaffolding\Scaffolding\Support\ModuleConfig;

class MakeModuleCommand extends Command
{
    protected $signature = 'make:module
        {name             : Module name in StudlyCase, e.g. Post or UserProfile}
        {--force          : Overwrite files that already exist}
        {--no-migration   : Skip migration generation}
        {--no-views       : Skip view generation}
        {--no-js          : Skip JavaScript file generation}
        {--no-routes      : Skip appending routes to the routes file}
        {--validation=    : Override validation driver: formrequest|spatie}
        {--css=           : Override CSS framework: bootstrap|tailwind}
        {--datatable      : Force-enable DataTable for the index view}
        {--no-datatable   : Force-disable DataTable for the index view}';

    protected $description = 'Scaffold a complete CRUD module: migration, model, queries, controller, validation, views, JS, and routes.';

    public function handle(
        LayoutDetector     $layoutDetector,
        DataTableDetector  $dataTableDetector,
        SchemaDetector     $schemaDetector,
        FieldResolver      $fieldResolver,
        EnumGenerator      $enumGenerator,
        MigrationGenerator $migrationGenerator,
        ModelGenerator     $modelGenerator,
        QueryGenerator     $queryGenerator,
        ServiceGenerator   $serviceGenerator,
        ControllerGenerator $controllerGenerator,
        RequestGenerator   $requestGenerator,
        ViewGenerator      $viewGenerator,
        JavaScriptGenerator $jsGenerator,
        RouteGenerator     $routeGenerator,
    ): int {
        $name = Str::studly($this->argument('name'));

        $this->line('');
        $this->info("  Scaffolding module: <comment>{$name}</comment>");
        $this->line('');

        // ── 1. Resolve settings ───────────────────────────────────────────────
        $validation  = $this->option('validation') ?? config('scaffolding.validation', 'formrequest');
        $css         = $this->option('css')        ?? config('scaffolding.css_framework', 'bootstrap');
        $layout      = $this->resolveLayout($layoutDetector);
        $useDataTable = $this->resolveDataTable($dataTableDetector);

        // ── 2. Resolve fields ─────────────────────────────────────────────────
        $tableName  = Str::snake(Str::plural($name));
        $fields     = $fieldResolver->resolve($tableName, $name, $this);
        $tableExists = $schemaDetector->tableExists($tableName);

        $this->line('');

        // ── 3. Build configuration ────────────────────────────────────────────
        $config = new ModuleConfig(
            name:             $name,
            namespace:        config('scaffolding.namespace', 'App\\Domains'),
            routePrefix:      config('scaffolding.routes.prefix', ''),
            middleware:       config('scaffolding.routes.middleware', ['web', 'auth']),
            routesFile:       config('scaffolding.routes.file', 'routes/web.php'),
            validationDriver: $validation,
            cssFramework:     $css,
            useDataTable:     $useDataTable,
            auditColumns:     config('scaffolding.database.audit_columns', true),
            softDeletes:      config('scaffolding.database.soft_deletes', false),
            layout:           $layout,
            fields:           $fields,
            tableExists:      $tableExists,
            force:            (bool) $this->option('force'),
            baseController:   config('scaffolding.base_controller', 'App\\Http\\Controllers\\Controller'),
            viewsBasePath:    config('scaffolding.paths.views', 'resources/views/modules'),
            jsBasePath:       config('scaffolding.paths.js', 'resources/js/project'),
            enforceExplicitSelect: (bool) config('scaffolding.query.enforce_explicit_select', true),
        );

        // ── 4. Run generators ─────────────────────────────────────────────────
        $this->line('  <fg=yellow>Generating files...</>');
        $this->line('');

        $generated = [];
        $skipped   = [];

        $generators = [
            'Enum classes'  => fn() => $enumGenerator->generate($config),
            'Migration'     => fn() => !$this->option('no-migration')
                                        ? $migrationGenerator->generate($config)
                                        : [],
            'Model'         => fn() => $modelGenerator->generate($config),
            'Queries'       => fn() => $queryGenerator->generate($config),
            'Services'      => fn() => $serviceGenerator->generate($config),
            'Controller'    => fn() => $controllerGenerator->generate($config),
            'Validation'    => fn() => $requestGenerator->generate($config),
            'Views'         => fn() => !$this->option('no-views')
                                        ? $viewGenerator->generate($config)
                                        : [],
            'JavaScript'    => fn() => !$this->option('no-js')
                                        ? $jsGenerator->generate($config)
                                        : [],
            'Routes'        => fn() => !$this->option('no-routes')
                                        ? $routeGenerator->generate($config)
                                        : [],
        ];

        foreach ($generators as $label => $generator) {
            try {
                $paths = $generator();
                foreach ($paths as $path) {
                    if ($path) {
                        $generated[] = $path;
                        $relative    = ltrim(str_replace(base_path(), '', $path), '/');
                        $this->line("  <fg=green>✓</> <comment>{$label}:</comment> {$relative}");
                    }
                }
                if (empty($paths)) {
                    $skipped[] = $label;
                }
            } catch (\RuntimeException $e) {
                $this->warn("  <fg=yellow>⚠</> {$label}: " . $e->getMessage());
            }
        }

        // ── 5. Summary ────────────────────────────────────────────────────────
        $this->line('');
        $this->info("  ✅  Module <comment>{$name}</comment> scaffolded — {$this->filesLabel(count($generated))} created.");

        if (!empty($skipped)) {
            $this->line('  <fg=gray>Skipped: ' . implode(', ', $skipped) . '</>');
        }

        $this->line('');
        $this->printNextSteps($config);

        return Command::SUCCESS;
    }

    // ── Layout detection ──────────────────────────────────────────────────────

    private function resolveLayout(LayoutDetector $detector): string
    {
        $configured = config('scaffolding.layout', 'auto');

        if ($configured !== 'auto') {
            return $configured;
        }

        $detected = $detector->detect();
        $this->line("  <fg=cyan>→</> Layout detected: <comment>{$detected}</comment>");

        return $detected;
    }

    // ── DataTable detection ───────────────────────────────────────────────────

    private function resolveDataTable(DataTableDetector $detector): bool
    {
        if ($this->option('datatable')) {
            return true;
        }

        if ($this->option('no-datatable')) {
            return false;
        }

        $configured = config('scaffolding.datatable', 'auto');

        if ($configured === 'auto') {
            $installed = $detector->isInstalled();
            $this->line('  <fg=cyan>→</> DataTable: <comment>' . ($installed ? 'detected' : 'not found, using plain table') . '</comment>');
            return $installed;
        }

        return (bool) $configured;
    }

    // ── Next steps hint ───────────────────────────────────────────────────────

    private function printNextSteps(ModuleConfig $config): void
    {
        $route = $config->routeName();

        $this->line('  <fg=yellow>Next steps:</>');

        if (!$config->tableExists) {
            $this->line("  • Run <comment>php artisan migrate</comment> to create the table");
        }

        $this->line("  • Register the JS entry in <comment>vite.config.js</comment> if using Vite");
        $this->line("  • Add the <comment>\$pageJs</comment> output block to your base layout if not already present:");
        $this->line('');
        $this->line("      @php");
        $this->line("          \$page_js_entries = isset(\$pageJs) && is_array(\$pageJs) ? \$pageJs : [];");
        $this->line("      @endphp");
        $this->line("      @if (!empty(\$page_js_entries))");
        $this->line("          @vite(\$page_js_entries)");
        $this->line("      @endif");
        $this->line('');
        $this->line("  • Visit <comment>/" . $route . "</comment> to see your module");
        $this->line('');
    }

    private function filesLabel(int $count): string
    {
        return $count === 1 ? '1 file' : "{$count} files";
    }
}
