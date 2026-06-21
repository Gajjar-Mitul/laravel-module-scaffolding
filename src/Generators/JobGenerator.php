<?php

namespace LaravelScaffolding\Scaffolding\Generators;

use Illuminate\Support\Str;
use LaravelScaffolding\Scaffolding\Support\ModuleConfig;
use LaravelScaffolding\Scaffolding\Support\StubRenderer;
use LaravelScaffolding\Scaffolding\Support\TemplateResolver;

final class JobGenerator
{
    public function __construct(
        private readonly StubRenderer $renderer,
    ) {}

    /**
     * @return string[]
     */
    public function generate(ModuleConfig $config): array
    {
        $queueEnabled = (bool) config('scaffolding.artifacts.queue.enabled', false);
        $queueStrategy = (string) config('scaffolding.artifacts.queue.strategy', 'job_only');
        $queueUsesJobs = in_array($queueStrategy, ['job_only', 'both'], true);

        $actions = config('scaffolding.artifacts.queue.job.actions', []);

        if ($queueEnabled && $queueUsesJobs && is_array($actions) && !empty($actions)) {
            return $this->generateActionJobs($config, $actions);
        }

        $content = $this->renderer->render('job.stub', [
            'namespace' => $config->jobNamespace(),
            'jobClass' => $config->jobClassName(),
            'variable' => $config->variableName(),
            'action' => 'default',
            'actionLabel' => 'Default',
        ]);

        $path = base_path($config->jobRelativePath());
        $this->renderer->write($path, $content, $config->force);

        return [$path];
    }

    /**
     * @param array<int, string> $actions
     * @return string[]
     */
    private function generateActionJobs(ModuleConfig $config, array $actions): array
    {
        $paths = [];

        $classTemplate = (string) config('scaffolding.artifacts.queue.job.class_template', 'Process{Action}{Module}');
        $pathTemplate = (string) config('scaffolding.artifacts.queue.job.path_template', 'app/Jobs/{Class}.php');
        $namespaceTemplate = (string) config('scaffolding.artifacts.queue.job.namespace_template', 'App\\Jobs');
        $stub = (string) config('scaffolding.artifacts.queue.job.stub', 'job.stub');

        foreach ($actions as $actionRaw) {
            $actionRaw = (string) $actionRaw;
            $action = Str::studly($actionRaw);
            if ($action === '') {
                continue;
            }

            $class = TemplateResolver::resolve($classTemplate, $config->name, [
                'Action' => $action,
                'action' => Str::snake($action),
            ]);

            $namespace = TemplateResolver::resolve($namespaceTemplate, $config->name, [
                'Action' => $action,
                'action' => Str::snake($action),
            ]);

            $relativePath = TemplateResolver::resolve($pathTemplate, $config->name, [
                'Action' => $action,
                'action' => Str::snake($action),
                'Class' => $class,
            ]);

            $content = $this->renderer->render($stub, [
                'namespace' => $namespace,
                'jobClass' => $class,
                'variable' => $config->variableName(),
                'action' => Str::snake($action),
                'actionLabel' => $action,
            ]);

            $path = base_path($relativePath);
            $this->renderer->write($path, $content, $config->force);
            $paths[] = $path;
        }

        return $paths;
    }
}
