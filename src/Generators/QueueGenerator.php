<?php

namespace LaravelScaffolding\Scaffolding\Generators;

use Illuminate\Support\Facades\File;
use LaravelScaffolding\Scaffolding\Support\ModuleConfig;
use LaravelScaffolding\Scaffolding\Support\StubRenderer;
use LaravelScaffolding\Scaffolding\Support\TemplateResolver;

final class QueueGenerator
{
    public function __construct(
        private readonly StubRenderer $renderer,
    ) {}

    /**
     * @return string[]
     */
    public function generate(ModuleConfig $config): array
    {
        $jobsEnabled = (bool) config('scaffolding.artifacts.queue.infrastructure.jobs_table_migration.enabled', true);
        $failedEnabled = (bool) config('scaffolding.artifacts.queue.infrastructure.failed_jobs_table_migration.enabled', true);

        $paths = [];

        if ($jobsEnabled) {
            $jobsName = TemplateResolver::resolve(
                (string) config('scaffolding.artifacts.queue.infrastructure.jobs_table_migration.name_template', 'create_jobs_table'),
                'Queue'
            );
            $path = $this->migrationPath(
                (string) config('scaffolding.artifacts.queue.infrastructure.jobs_table_migration.path_template', 'database/migrations'),
                $jobsName
            );

            if ($config->force || !$this->migrationExists($jobsName)) {
                $content = $this->renderer->render(
                    (string) config('scaffolding.artifacts.queue.infrastructure.jobs_table_migration.stub', 'queue/jobs-table.stub'),
                    [
                        'jobsTable' => $this->infraTableName('scaffolding.artifacts.queue.infrastructure.jobs_table_migration.table_name', 'jobs'),
                    ]
                );
                $this->renderer->write($path, $content, $config->force);
                $paths[] = $path;
            }
        }

        if ($failedEnabled) {
            $failedName = TemplateResolver::resolve(
                (string) config('scaffolding.artifacts.queue.infrastructure.failed_jobs_table_migration.name_template', 'create_failed_jobs_table'),
                'Queue'
            );
            $path = $this->migrationPath(
                (string) config('scaffolding.artifacts.queue.infrastructure.failed_jobs_table_migration.path_template', 'database/migrations'),
                $failedName,
                1
            );

            if ($config->force || !$this->migrationExists($failedName)) {
                $content = $this->renderer->render(
                    (string) config('scaffolding.artifacts.queue.infrastructure.failed_jobs_table_migration.stub', 'queue/failed-jobs-table.stub'),
                    [
                        'failedJobsTable' => $this->infraTableName('scaffolding.artifacts.queue.infrastructure.failed_jobs_table_migration.table_name', 'failed_jobs'),
                    ]
                );
                $this->renderer->write($path, $content, $config->force);
                $paths[] = $path;
            }
        }

        return $paths;
    }

    private function migrationPath(string $baseTemplate, string $nameTemplate, int $secondsOffset = 0): string
    {
        $baseDir = TemplateResolver::resolve($baseTemplate, 'Queue');
        $name = TemplateResolver::resolve($nameTemplate, 'Queue');

        $timestamp = now()->copy()->addSeconds($secondsOffset)->format('Y_m_d_His');

        return base_path(trim($baseDir, '/') . '/' . $timestamp . '_' . $name . '.php');
    }

    private function migrationExists(string $suffix): bool
    {
        $files = glob(database_path('migrations/*_' . $suffix . '.php'));

        return is_array($files) && !empty($files);
    }

    private function infraTableName(string $configKey, string $default): string
    {
        $table = (string) config($configKey, $default);

        return trim($table) !== '' ? $table : $default;
    }
}
