<?php

namespace LaravelScaffolding\Scaffolding\Support;

final class BlueprintConfig
{
    /**
     * @param  array<string, mixed> $config
     */
    public function __construct(
        private readonly array $config,
    ) {}

    public static function fromRuntimeConfig(): self
    {
        $raw = config('scaffolding', []);

        return new self(is_array($raw) ? $raw : []);
    }

    public function namespace(): string
    {
        return (string) $this->get('module.namespace', 'App\\Domains');
    }

    public function baseController(): string
    {
        return (string) $this->get('module.base_controller', 'App\\Http\\Controllers\\Controller');
    }

    public function validationDriver(): string
    {
        return (string) $this->get('module.validation', 'formrequest');
    }

    public function cssFramework(): string
    {
        return (string) $this->get('module.css_framework', 'bootstrap');
    }

    public function dataTableDefault(): mixed
    {
        return $this->get('module.datatable', 'auto');
    }

    public function layoutDefault(): string
    {
        return (string) $this->get('module.layout', 'auto');
    }

    public function routesFile(): string
    {
        return (string) $this->get('routing.file', 'routes/web.php');
    }

    public function routePrefix(): string
    {
        return (string) $this->get('routing.prefix', '');
    }

    /**
     * @return string[]
     */
    public function middleware(): array
    {
        $value = $this->get('routing.middleware', ['web', 'auth']);

        return is_array($value) ? array_values(array_map('strval', $value)) : ['web', 'auth'];
    }

    public function auditColumns(): bool
    {
        return (bool) $this->get('database.audit_columns', true);
    }

    public function softDeletes(): bool
    {
        return (bool) $this->get('database.soft_deletes', false);
    }

    public function enforceExplicitSelect(): bool
    {
        return (bool) $this->get('query.enforce_explicit_select', true);
    }

    public function viewsBasePath(): string
    {
        return (string) $this->get('module.paths.views_base', 'resources/views/modules');
    }

    public function jsBasePath(): string
    {
        return (string) $this->get('module.paths.js_base', 'resources/js/project');
    }

    public function schemaPath(): string
    {
        return (string) $this->get('module.paths.schema', 'scaffolding');
    }

    public function traitsPath(): string
    {
        return (string) $this->get('module.paths.traits', 'app/Shared/Traits');
    }

    public function isArtifactEnabled(string $artifact): bool
    {
        return (bool) $this->get("artifacts.{$artifact}.enabled", true);
    }

    public function queueStrategy(): string
    {
        $strategy = (string) $this->get('artifacts.queue.strategy', 'job_only');
        $allowed = ['job_only', 'infrastructure', 'both'];

        if (!in_array($strategy, $allowed, true)) {
            throw new \InvalidArgumentException(
                "Invalid queue strategy [{$strategy}]. Allowed values: " . implode(', ', $allowed)
            );
        }

        return $strategy;
    }

    public function queueGeneratesJobs(): bool
    {
        $strategy = $this->queueStrategy();

        return in_array($strategy, ['job_only', 'both'], true);
    }

    public function queueGeneratesInfrastructure(): bool
    {
        $strategy = $this->queueStrategy();

        return in_array($strategy, ['infrastructure', 'both'], true);
    }

    private function get(string $path, mixed $default = null): mixed
    {
        $segments = explode('.', $path);
        $value = $this->config;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }

            $value = $value[$segment];
        }

        return $value;
    }
}
