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
        public readonly bool   $enforceExplicitSelect = true,
    ) {}

    // ── Naming helpers ────────────────────────────────────────────────────────

    /** "Post" */
    public function className(): string
    {
        return $this->name;
    }

    /** "Posts" */
    public function classNamePlural(): string
    {
        return Str::plural($this->name);
    }

    /** "$post" */
    public function variableName(): string
    {
        return lcfirst($this->name);
    }

    /** "$posts" */
    public function variableNamePlural(): string
    {
        return lcfirst(Str::plural($this->name));
    }

    /** "posts" */
    public function tableName(): string
    {
        return Str::snake(Str::plural($this->name));
    }

    /** "posts" (used as route resource name) */
    public function routeName(): string
    {
        return Str::snake(Str::plural($this->name));
    }

    /** "modules.post" */
    public function viewPath(): string
    {
        return 'modules.' . Str::snake($this->name);
    }

    /** "resources/js/project/posts/index.js" */
    public function jsFilePath(): string
    {
        $plural = Str::plural(Str::snake($this->name));
        return "{$this->jsBasePath}/{$plural}/index.js";
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
        return $this->domainNamespace() . '\\Controllers';
    }

    /** "App\Domains\Post\Models" */
    public function modelNamespace(): string
    {
        return $this->domainNamespace() . '\\Models';
    }

    /** "App\Domains\Post\Queries" */
    public function queryNamespace(): string
    {
        return $this->domainNamespace() . '\\Queries';
    }

    /** "App\Domains\Post\Services" */
    public function serviceNamespace(): string
    {
        return $this->domainNamespace() . '\\Services';
    }

    /** "App\Domains\Post\Requests" */
    public function requestNamespace(): string
    {
        return $this->domainNamespace() . '\\Requests';
    }

    /** "App\Domains\Post\DTOs" */
    public function dtoNamespace(): string
    {
        return $this->domainNamespace() . '\\DTOs';
    }

    /** "App\Domains\Post\Enums" */
    public function enumNamespace(): string
    {
        return $this->domainNamespace() . '\\Enums';
    }

    /** "App\Domains\Post\Models\Post" */
    public function modelFullClass(): string
    {
        return $this->modelNamespace() . '\\' . $this->className();
    }

    /** "App\Domains\Post\Queries\PostQueries" */
    public function queryFullClass(): string
    {
        return $this->queryNamespace() . '\\' . $this->className() . 'Queries';
    }

    /** "App\Domains\Post\Services\PostService" */
    public function serviceFullClass(): string
    {
        return $this->serviceNamespace() . '\\' . $this->className() . 'Service';
    }

    // ── Path helpers (use base_path() in actual code, not here) ──────────────

    /** Relative path: "app/Domains/Post/Controllers/PostController.php" */
    public function controllerRelativePath(): string
    {
        return $this->namespaceToPath($this->controllerNamespace()) . '/' . $this->className() . 'Controller.php';
    }

    /** Relative path: "app/Domains/Post/Models/Post.php" */
    public function modelRelativePath(): string
    {
        return $this->namespaceToPath($this->modelNamespace()) . '/' . $this->className() . '.php';
    }

    /** Relative path: "app/Domains/Post/Queries/PostQueries.php" */
    public function queryRelativePath(): string
    {
        return $this->namespaceToPath($this->queryNamespace()) . '/' . $this->className() . 'Queries.php';
    }

    /** Relative path: "app/Domains/Post/Services/PostService.php" */
    public function serviceRelativePath(): string
    {
        return $this->namespaceToPath($this->serviceNamespace()) . '/' . $this->className() . 'Service.php';
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
