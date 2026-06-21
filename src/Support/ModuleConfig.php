<?php

namespace LaravelScaffolding\Scaffolding\Support;

use Illuminate\Support\Str;

/**
 * Immutable configuration object for a scaffolding run.
 * Built once by the command and passed to every generator.
 */
final class ModuleConfig
{
    /**
     * @param string            $name             Studly-case module name e.g. "Post"
     * @param string            $namespace        Root domain namespace e.g. "App\Domains"
     * @param string            $routePrefix      Optional URL prefix e.g. "admin"
     * @param string[]          $middleware       Middleware applied to generated routes
     * @param string            $routesFile       Relative path to routes file e.g. "routes/web.php"
     * @param string            $validationDriver "formrequest" | "spatie"
     * @param string            $cssFramework     "bootstrap" | "tailwind"
     * @param bool              $useDataTable     Whether to scaffold DataTable index
     * @param bool              $auditColumns     Whether to include created_by / updated_by
     * @param bool              $softDeletes      Whether to include deleted_at
     * @param string            $layout           Blade layout to extend
     * @param FieldDefinition[] $fields           Resolved field list
     * @param bool              $tableExists      Whether the DB table already exists
     * @param bool              $force            Whether to overwrite existing files
     * @param string            $baseController   FQN of base controller class
     * @param string            $viewsBasePath    Base folder for views
     * @param string            $jsBasePath       Base folder for JS files
    * @param string            $traitsBasePath   Base folder for generated shared traits
    * @param bool              $enforceExplicitSelect Whether queries should avoid wildcard selects
     */
    public function __construct(
        public readonly string $name,
        public readonly string $namespace,
        public readonly string $routePrefix,
        public readonly array  $middleware,
        public readonly string $routesFile,
        public readonly string $validationDriver,
        public readonly string $cssFramework,
        public readonly bool   $useDataTable,
        public readonly bool   $auditColumns,
        public readonly bool   $softDeletes,
        public readonly string $layout,
        public readonly array  $fields = [],
        public readonly bool   $tableExists = false,
        public readonly bool   $force = false,
        public readonly string $baseController = 'App\\Http\\Controllers\\Controller',
        public readonly string $viewsBasePath = 'resources/views/modules',
        public readonly string $jsBasePath = 'resources/js/project',
        public readonly string $traitsBasePath = 'app/Shared/Traits',
        public readonly bool   $enforceExplicitSelect = true,
    ) {}

    // ── Naming helpers ────────────────────────────────────────────────────────

    /** "Post" */
    public function className(): string
    {
        return $this->name;
    }

    public function modelClassName(): string
    {
        return $this->resolveTemplate('artifacts.model.class_template', '{Module}');
    }

    public function controllerClassName(): string
    {
        return $this->resolveTemplate('artifacts.controller.class_template', '{Module}Controller');
    }

    public function queryClassName(): string
    {
        return $this->resolveTemplate('artifacts.query.class_template', '{Module}Queries');
    }

    public function serviceClassName(): string
    {
        return $this->resolveTemplate('artifacts.service.class_template', '{Module}Service');
    }

    public function enumClassName(string $fieldName): string
    {
        return $this->resolveTemplate(
            'artifacts.enum.class_template',
            '{Module}{Field}Enum',
            ['Field' => Str::studly($fieldName)]
        );
    }

    public function factoryClassName(): string
    {
        return $this->resolveTemplate('artifacts.factory.class_template', '{Module}Factory');
    }

    public function eventClassName(): string
    {
        return $this->resolveTemplate('artifacts.event.class_template', '{Module}Created');
    }

    public function jobClassName(): string
    {
        return $this->resolveTemplate('artifacts.job.class_template', 'Process{Module}');
    }

    public function moduleCommandClassName(): string
    {
        return $this->resolveTemplate('artifacts.command.class_template', '{Module}SyncCommand');
    }

    public function storeValidationClassName(): string
    {
        if ($this->validationDriver === 'spatie') {
            return $this->resolveTemplate('artifacts.validation.spatie_data.store_class_template', 'Store{Module}Data');
        }

        return $this->resolveTemplate('artifacts.validation.form_request.store_class_template', 'Store{Module}Request');
    }

    public function updateValidationClassName(): string
    {
        if ($this->validationDriver === 'spatie') {
            return $this->resolveTemplate('artifacts.validation.spatie_data.update_class_template', 'Update{Module}Data');
        }

        return $this->resolveTemplate('artifacts.validation.form_request.update_class_template', 'Update{Module}Request');
    }

    /** "Posts" */
    public function classNamePlural(): string
    {
        return Str::plural($this->name);
    }

    /** "$post" */
    public function variableName(): string
    {
        $raw = $this->resolveTemplate('routing.parameter_template', '{module}');
        $normalized = preg_replace('/[^a-zA-Z0-9_]/', '_', $raw) ?? $raw;

        return ltrim(strtolower($normalized), '_');
    }

    /** "$posts" */
    public function variableNamePlural(): string
    {
        return Str::plural($this->variableName());
    }

    /** "posts" */
    public function tableName(): string
    {
        return Str::snake(Str::plural($this->name));
    }

    /** "posts" (used as route resource name) */
    public function routeName(): string
    {
        return $this->resolveTemplate('routing.name_template', '{modules}');
    }

    /** "posts" (used as route URI segment) */
    public function routeUriSegment(): string
    {
        return $this->resolveTemplate('routing.uri_template', '{modules}');
    }

    /** "post" (used as route parameter placeholder) */
    public function routeParameter(): string
    {
        return $this->resolveTemplate('routing.parameter_template', '{module}');
    }

    /** "modules.post" */
    public function viewPath(): string
    {
        $resolved = $this->resolveTemplate('artifacts.views.path_template', $this->viewsBasePath . '/{module}');
        $normalized = trim(str_replace('\\', '/', $resolved), '/');
        $normalized = preg_replace('#^resources/views/?#', '', $normalized) ?? $normalized;
        $normalized = trim($normalized, '/');

        return str_replace('/', '.', $normalized);
    }

    /** "resources/js/project/posts/index.js" */
    public function jsFilePath(): string
    {
        return $this->resolveTemplate('artifacts.javascript.path_template', $this->jsBasePath . '/{modules}/index.js');
    }

    /** Element ID used by DataTable JS: "posts-table-config" */
    public function configElementId(): string
    {
        return Str::slug(Str::plural($this->name)) . '-table-config';
    }

    /** Element ID for the <table>: "posts-listing" */
    public function tableElementId(): string
    {
        return Str::slug(Str::plural($this->name)) . '-listing';
    }

    // ── Namespace helpers ─────────────────────────────────────────────────────

    /** "App\Domains\Post" */
    public function domainNamespace(): string
    {
        return $this->namespace . '\\' . $this->className();
    }

    /** "App\Domains\Post\Controllers" */
    public function controllerNamespace(): string
    {
        return $this->resolveTemplate('artifacts.controller.namespace_template', '{namespace}\\{Module}\\Controllers');
    }

    /** "App\Domains\Post\Models" */
    public function modelNamespace(): string
    {
        return $this->resolveTemplate('artifacts.model.namespace_template', '{namespace}\\{Module}\\Models');
    }

    /** "App\Domains\Post\Queries" */
    public function queryNamespace(): string
    {
        return $this->resolveTemplate('artifacts.query.namespace_template', '{namespace}\\{Module}\\Queries');
    }

    /** "App\Domains\Post\Services" */
    public function serviceNamespace(): string
    {
        return $this->resolveTemplate('artifacts.service.namespace_template', '{namespace}\\{Module}\\Services');
    }

    /** "App\Domains\Post\Requests" */
    public function requestNamespace(): string
    {
        return $this->resolveTemplate('artifacts.validation.form_request.namespace_template', '{namespace}\\{Module}\\Requests');
    }

    /** "App\Domains\Post\DTOs" */
    public function dtoNamespace(): string
    {
        return $this->resolveTemplate('artifacts.validation.spatie_data.namespace_template', '{namespace}\\{Module}\\DTOs');
    }

    /** "App\Domains\Post\Enums" */
    public function enumNamespace(): string
    {
        return $this->resolveTemplate('artifacts.enum.namespace_template', '{namespace}\\{Module}\\Enums');
    }

    public function factoryNamespace(): string
    {
        return $this->resolveTemplate('artifacts.factory.namespace_template', 'Database\\Factories');
    }

    public function eventNamespace(): string
    {
        return $this->resolveTemplate('artifacts.event.namespace_template', 'App\\Events');
    }

    public function jobNamespace(): string
    {
        return $this->resolveTemplate('artifacts.job.namespace_template', 'App\\Jobs');
    }

    public function moduleCommandNamespace(): string
    {
        return $this->resolveTemplate('artifacts.command.namespace_template', 'App\\Console\\Commands');
    }

    /** "App\Domains\Post\Models\Post" */
    public function modelFullClass(): string
    {
        return $this->modelNamespace() . '\\' . $this->modelClassName();
    }

    /** "App\Domains\Post\Queries\PostQueries" */
    public function queryFullClass(): string
    {
        return $this->queryNamespace() . '\\' . $this->queryClassName();
    }

    /** "App\Domains\Post\Services\PostService" */
    public function serviceFullClass(): string
    {
        return $this->serviceNamespace() . '\\' . $this->serviceClassName();
    }

    // ── Path helpers (use base_path() in actual code, not here) ──────────────

    /** Relative path: "app/Domains/Post/Controllers/PostController.php" */
    public function controllerRelativePath(): string
    {
        return $this->resolveTemplate(
            'artifacts.controller.path_template',
            $this->namespaceToPath($this->controllerNamespace()) . '/{Class}.php',
            ['Class' => $this->controllerClassName()]
        );
    }

    /** Relative path: "app/Domains/Post/Models/Post.php" */
    public function modelRelativePath(): string
    {
        return $this->resolveTemplate(
            'artifacts.model.path_template',
            $this->namespaceToPath($this->modelNamespace()) . '/{Class}.php',
            ['Class' => $this->modelClassName()]
        );
    }

    /** Relative path: "app/Domains/Post/Queries/PostQueries.php" */
    public function queryRelativePath(): string
    {
        return $this->resolveTemplate(
            'artifacts.query.path_template',
            $this->namespaceToPath($this->queryNamespace()) . '/{Class}.php',
            ['Class' => $this->queryClassName()]
        );
    }

    /** Relative path: "app/Domains/Post/Services/PostService.php" */
    public function serviceRelativePath(): string
    {
        return $this->resolveTemplate(
            'artifacts.service.path_template',
            $this->namespaceToPath($this->serviceNamespace()) . '/{Class}.php',
            ['Class' => $this->serviceClassName()]
        );
    }

    public function formRequestRelativePath(string $class): string
    {
        return $this->resolveTemplate(
            'artifacts.validation.form_request.path_template',
            $this->namespaceToPath($this->requestNamespace()) . '/{Class}.php',
            ['Class' => $class]
        );
    }

    public function spatieDataRelativePath(string $class): string
    {
        return $this->resolveTemplate(
            'artifacts.validation.spatie_data.path_template',
            $this->namespaceToPath($this->dtoNamespace()) . '/{Class}.php',
            ['Class' => $class]
        );
    }

    public function enumRelativePath(string $class): string
    {
        return $this->resolveTemplate(
            'artifacts.enum.path_template',
            $this->namespaceToPath($this->enumNamespace()) . '/{Class}.php',
            ['Class' => $class]
        );
    }

    public function viewsDirectoryPath(): string
    {
        return $this->resolveTemplate('artifacts.views.path_template', $this->viewsBasePath . '/{module}');
    }

    public function factoryRelativePath(): string
    {
        return $this->resolveTemplate(
            'artifacts.factory.path_template',
            $this->namespaceToPath($this->factoryNamespace()) . '/{Class}.php',
            ['Class' => $this->factoryClassName()]
        );
    }

    public function eventRelativePath(): string
    {
        return $this->resolveTemplate(
            'artifacts.event.path_template',
            $this->namespaceToPath($this->eventNamespace()) . '/{Class}.php',
            ['Class' => $this->eventClassName()]
        );
    }

    public function jobRelativePath(): string
    {
        return $this->resolveTemplate(
            'artifacts.job.path_template',
            $this->namespaceToPath($this->jobNamespace()) . '/{Class}.php',
            ['Class' => $this->jobClassName()]
        );
    }

    public function moduleCommandRelativePath(): string
    {
        return $this->resolveTemplate(
            'artifacts.command.path_template',
            $this->namespaceToPath($this->moduleCommandNamespace()) . '/{Class}.php',
            ['Class' => $this->moduleCommandClassName()]
        );
    }

    private function resolveTemplate(string $configPath, string $defaultTemplate, array $extraTokens = []): string
    {
        $template = config('scaffolding.' . $configPath, $defaultTemplate);

        return TemplateResolver::resolve((string) $template, $this->name, array_merge([
            'namespace' => $this->namespace,
        ], $extraTokens));
    }

    private function namespaceToPath(string $namespace): string
    {
        // Convert "App\Domains\Post\Controllers" → "app/Domains/Post/Controllers"
        $path = str_replace('\\', '/', $namespace);
        // Lowercase only the first segment (App → app)
        $segments = explode('/', $path);
        $segments[0] = strtolower($segments[0]);
        return implode('/', $segments);
    }
}
