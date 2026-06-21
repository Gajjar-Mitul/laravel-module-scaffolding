<?php

namespace LaravelScaffolding\Scaffolding\Generators;

use LaravelScaffolding\Scaffolding\Support\ModuleConfig;
use LaravelScaffolding\Scaffolding\Support\StubRenderer;

final class QueryGenerator
{
    public function __construct(
        private readonly StubRenderer $renderer,
    ) {}

    /**
     * @return string[]
     */
    public function generate(ModuleConfig $config): array
    {
        $queryClass = $config->queryClassName();
        $modelClass = $config->modelClassName();

        $content = $this->renderer->render('queries.stub', [
            'queryNamespace'  => $config->queryNamespace(),
            'modelFullClass'  => $config->modelFullClass(),
            'queryClass'      => $queryClass,
            'modelClass'      => $modelClass,
            'defaultColumns'  => $this->buildDefaultColumns($config),
            'dataTableColumns' => $this->buildDataTableColumns($config),
        ]);

        $path = base_path($config->queryRelativePath());
        $this->renderer->write($path, $content, $config->force);

        return [$path];
    }

    /**
     * Build the explicit column list used by getAllForDataTable().
     * Always includes id plus all non-text, non-json user-defined fields.
     */
    private function buildDataTableColumns(ModuleConfig $config): string
    {
        $columns = ['id'];

        foreach ($config->fields as $field) {
            if (!in_array($field->type, ['text', 'longText', 'json'], true)) {
                $columns[] = $field->name;
            }
        }

        if ($config->auditColumns) {
            $columns[] = 'created_by';
            $columns[] = 'updated_by';
        }

        $quoted = array_map(static fn(string $c) => "'{$c}'", $columns);
        return '[' . implode(', ', $quoted) . ']';
    }

    /**
     * Build explicit default columns for generic listing/detail query methods.
     */
    private function buildDefaultColumns(ModuleConfig $config): string
    {
        if (!$config->enforceExplicitSelect) {
            return "['*']";
        }

        $columns = ['id'];

        foreach ($config->fields as $field) {
            $columns[] = $field->name;
        }

        if ($config->auditColumns) {
            $columns[] = 'created_by';
            $columns[] = 'updated_by';
        }

        $columns[] = 'created_at';
        $columns[] = 'updated_at';

        $columns = array_values(array_unique($columns));
        $quoted = array_map(static fn(string $c) => "'{$c}'", $columns);

        return '[' . implode(', ', $quoted) . ']';
    }

}
