<?php

namespace LaravelScaffolding\Scaffolding\Generators;

use Illuminate\Support\Facades\File;
use LaravelScaffolding\Scaffolding\Support\FieldDefinition;
use LaravelScaffolding\Scaffolding\Support\ModuleConfig;
use LaravelScaffolding\Scaffolding\Support\StubRenderer;

final class MigrationGenerator
{
    public function __construct(
        private readonly StubRenderer $renderer,
    ) {}

    /**
     * @return string[] Absolute paths of created files
     */
    public function generate(ModuleConfig $config): array
    {
        if ($config->tableExists) {
            return []; // Table already exists, skip migration
        }

        $content = $this->renderer->render('migration.stub', [
            'tableName'         => $config->tableName(),
            'migrationColumns'  => $this->buildColumns($config),
            'softDeletesColumn' => $config->softDeletes ? '            $table->softDeletes();' : '',
        ]);

        $timestamp = now()->format('Y_m_d_His');
        $filename  = "{$timestamp}_create_{$config->tableName()}_table.php";
        $path      = database_path("migrations/{$filename}");

        $this->renderer->write($path, $content, $config->force);

        return [$path];
    }

    private function buildColumns(ModuleConfig $config): string
    {
        $lines = [];

        foreach ($config->fields as $field) {
            $lines[] = $this->buildColumn($field);
        }

        if ($config->auditColumns) {
            $lines[] = "            \$table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();";
            $lines[] = "            \$table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();";
        }

        return implode("\n", $lines);
    }

    private function buildColumn(FieldDefinition $field): string
    {
        $line = '            $table->';

        // Column definition
        $line .= match ($field->type) {
            'enum'      => "enum('{$field->name}', [" . implode(', ', array_map(fn($v) => "'{$v}'", $field->enumValues)) . '])',
            'foreignId' => "foreignId('{$field->name}')",
            'decimal'   => "decimal('{$field->name}', 10, 2)",
            'string'    => "string('{$field->name}')",
            default     => "{$field->migrationMethod()}('{$field->name}')",
        };

        // Modifiers
        if ($field->nullable) {
            $line .= '->nullable()';
        }

        if ($field->unique) {
            $line .= '->unique()';
        }

        if ($field->default !== null) {
            $line .= '->default(' . $this->formatDefault($field->default) . ')';
        }

        // Foreign key constraints
        if ($field->type === 'foreignId') {
            $refTable = \Illuminate\Support\Str::snake(\Illuminate\Support\Str::plural($field->relatedModel ?? 'users'));
            $onDelete = $field->nullable ? '->nullOnDelete()' : '->cascadeOnDelete()';
            $line .= "->constrained('{$refTable}'){$onDelete}";
        }

        return $line . ';';
    }

    private function formatDefault(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_numeric($value)) {
            return (string) $value;
        }

        return "'" . addslashes((string) $value) . "'";
    }
}
