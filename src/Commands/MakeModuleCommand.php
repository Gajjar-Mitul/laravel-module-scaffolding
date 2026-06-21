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
use LaravelScaffolding\Scaffolding\Generators\EventGenerator;
use LaravelScaffolding\Scaffolding\Generators\FactoryGenerator;
use LaravelScaffolding\Scaffolding\Generators\JavaScriptGenerator;
use LaravelScaffolding\Scaffolding\Generators\JobGenerator;
use LaravelScaffolding\Scaffolding\Generators\MigrationGenerator;
use LaravelScaffolding\Scaffolding\Generators\ModelGenerator;
use LaravelScaffolding\Scaffolding\Generators\ModuleCommandGenerator;
use LaravelScaffolding\Scaffolding\Generators\QueryGenerator;
use LaravelScaffolding\Scaffolding\Generators\QueueGenerator;
use LaravelScaffolding\Scaffolding\Generators\RequestGenerator;
use LaravelScaffolding\Scaffolding\Generators\RouteGenerator;
use LaravelScaffolding\Scaffolding\Generators\ServiceGenerator;
use LaravelScaffolding\Scaffolding\Generators\ViewGenerator;
use LaravelScaffolding\Scaffolding\Support\BlueprintConfig;
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
        FactoryGenerator   $factoryGenerator,
        QueryGenerator     $queryGenerator,
        ServiceGenerator   $serviceGenerator,
        ControllerGenerator $controllerGenerator,
        RequestGenerator   $requestGenerator,
        ViewGenerator      $viewGenerator,
        JavaScriptGenerator $jsGenerator,
        RouteGenerator     $routeGenerator,
        EventGenerator     $eventGenerator,
        JobGenerator       $jobGenerator,
        QueueGenerator     $queueGenerator,
        ModuleCommandGenerator $moduleCommandGenerator,
    ): int {
        $blueprint = BlueprintConfig::fromRuntimeConfig();
        $name = Str::studly($this->argument('name'));

        try {
            if ($blueprint->isArtifactEnabled('queue')) {
                $blueprint->queueStrategy();
            }
        } catch (\InvalidArgumentException $e) {
            $this->error('  ' . $e->getMessage());
            return Command::FAILURE;
        }

        $this->line('');
        $this->info("  Scaffolding module: <comment>{$name}</comment>");
        $this->line('');

        // ── 1. Resolve settings ───────────────────────────────────────────────
        $validation  = $this->option('validation') ?? $blueprint->validationDriver();
        $css         = $this->option('css')        ?? $blueprint->cssFramework();
        $layout      = $this->resolveLayout($layoutDetector, $blueprint);
        $useDataTable = $this->resolveDataTable($dataTableDetector, $blueprint);

        // ── 2. Resolve fields ─────────────────────────────────────────────────
        $tableName  = Str::snake(Str::plural($name));
        $fields     = $fieldResolver->resolve($tableName, $name, $this);
        $tableExists = $schemaDetector->tableExists($tableName);

        $this->line('');

        // ── 3. Build configuration ────────────────────────────────────────────
        $config = new ModuleConfig(
            name:             $name,
            namespace:        $blueprint->namespace(),
            routePrefix:      $blueprint->routePrefix(),
            middleware:       $blueprint->middleware(),
            routesFile:       $blueprint->routesFile(),
            validationDriver: $validation,
            cssFramework:     $css,
            useDataTable:     $useDataTable,
            auditColumns:     $blueprint->auditColumns(),
            softDeletes:      $blueprint->softDeletes(),
            layout:           $layout,
            fields:           $fields,
            tableExists:      $tableExists,
            force:            (bool) $this->option('force'),
            baseController:   $blueprint->baseController(),
            viewsBasePath:    $blueprint->viewsBasePath(),
            jsBasePath:       $blueprint->jsBasePath(),
            traitsBasePath:   $blueprint->traitsPath(),
            enforceExplicitSelect: $blueprint->enforceExplicitSelect(),
        );

        // ── 4. Run generators ─────────────────────────────────────────────────
        $this->line('  <fg=yellow>Generating files...</>');
        $this->line('');

        $generated = [];
        $skipped   = [];

        $generators = [
            'Enum classes'  => fn() => $blueprint->isArtifactEnabled('enum')
                                        ? $enumGenerator->generate($config)
                                        : [],
            'Migration'     => fn() => !$this->option('no-migration')
                                        && $blueprint->isArtifactEnabled('migration')
                                        ? $migrationGenerator->generate($config)
                                        : [],
            'Model'         => fn() => $blueprint->isArtifactEnabled('model')
                                        ? $modelGenerator->generate($config)
                                        : [],
            'Factory'       => fn() => $blueprint->isArtifactEnabled('factory')
                                        ? $factoryGenerator->generate($config)
                                        : [],
            'Queries'       => fn() => $blueprint->isArtifactEnabled('query')
                                        ? $queryGenerator->generate($config)
                                        : [],
            'Services'      => fn() => $blueprint->isArtifactEnabled('service')
                                        ? $serviceGenerator->generate($config)
                                        : [],
            'Controller'    => fn() => $blueprint->isArtifactEnabled('controller')
                                        ? $controllerGenerator->generate($config)
                                        : [],
            'Validation'    => fn() => $blueprint->isArtifactEnabled('validation')
                                        ? $requestGenerator->generate($config)
                                        : [],
            'Views'         => fn() => !$this->option('no-views')
                                        && $blueprint->isArtifactEnabled('views')
                                        ? $viewGenerator->generate($config)
                                        : [],
            'JavaScript'    => fn() => !$this->option('no-js')
                                        && $blueprint->isArtifactEnabled('javascript')
                                        ? $jsGenerator->generate($config)
                                        : [],
            'Events'        => fn() => $blueprint->isArtifactEnabled('event')
                                        ? $eventGenerator->generate($config)
                                        : [],
            'Jobs'          => fn() => $blueprint->isArtifactEnabled('job')
                                        || ($blueprint->isArtifactEnabled('queue') && $blueprint->queueGeneratesJobs())
                                        ? $jobGenerator->generate($config)
                                        : [],
            'Queue'         => fn() => $blueprint->isArtifactEnabled('queue')
                                        && $blueprint->queueGeneratesInfrastructure()
                                        ? $queueGenerator->generate($config)
                                        : [],
            'Commands'      => fn() => $blueprint->isArtifactEnabled('command')
                                        ? $moduleCommandGenerator->generate($config)
                                        : [],
            'Routes'        => fn() => !$this->option('no-routes')
                                        && $blueprint->isArtifactEnabled('routes')
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

    private function resolveLayout(LayoutDetector $detector, BlueprintConfig $blueprint): string
    {
        $configured = $blueprint->layoutDefault();

        if ($configured !== 'auto') {
            return $configured;
        }

        $detected = $detector->detect();
        $this->line("  <fg=cyan>→</> Layout detected: <comment>{$detected}</comment>");

        return $detected;
    }

    // ── DataTable detection ───────────────────────────────────────────────────

    private function resolveDataTable(DataTableDetector $detector, BlueprintConfig $blueprint): bool
    {
        if ($this->option('datatable')) {
            return true;
        }

        if ($this->option('no-datatable')) {
            return false;
        }

        $configured = $blueprint->dataTableDefault();

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
        $route = $config->routeUriSegment();

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
