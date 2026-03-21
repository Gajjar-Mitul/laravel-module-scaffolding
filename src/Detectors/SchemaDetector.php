<?php

namespace LaravelScaffolding\Scaffolding\Detectors;

use Illuminate\Support\Facades\Schema;
use LaravelScaffolding\Scaffolding\Support\FieldDefinition;

/**
 * Reads column definitions from an existing database table.
 */
final class SchemaDetector
{
    /**
     * Columns that are managed automatically and should never be scaffolded.
     */
    private const SKIP_COLUMNS = [
        'id', 'created_at', 'updated_at', 'deleted_at',
        'created_by', 'updated_by',
    ];

    public function tableExists(string $tableName): bool
    {
        try {
            return Schema::hasTable($tableName);
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Read column metadata from an existing table and return FieldDefinitions.
     *
     * @return FieldDefinition[]
     */
    public function getFields(string $tableName): array
    {
        $columns = Schema::getColumns($tableName);
        $fields  = [];

        foreach ($columns as $column) {
            $name = $column['name'];

            if (in_array($name, self::SKIP_COLUMNS, true)) {
                continue;
            }

            $fields[] = $this->toFieldDefinition($column, $tableName);
        }

        return $fields;
    }

    private function toFieldDefinition(array $column, string $tableName): FieldDefinition
    {
        $name     = $column['name'];
        $typeName = strtolower((string) ($column['type_name'] ?? $column['type'] ?? 'string'));
        $nullable = (bool) ($column['nullable'] ?? false);
        $default  = $column['default'] ?? null;

        [$type, $enumValues] = $this->resolveType($typeName, $name, $tableName);

        // Detect foreign keys (columns ending in _id → foreignId)
        $relatedModel = null;
        if (str_ends_with($name, '_id')) {
            $type         = 'foreignId';
            $relatedModel = $this->guessRelatedModel($name);
        }

        return new FieldDefinition(
            name: $name,
            type: $type,
            nullable: $nullable,
            default: $default !== '' ? $default : null,
            enumValues: $enumValues,
            relatedModel: $relatedModel,
        );
    }

    private function resolveType(string $typeName, string $columnName, string $tableName): array
    {
        if (in_array($typeName, ['enum', 'set'], true)) {
            $values = $this->getEnumValues($tableName, $columnName);
            return ['enum', $values];
        }

        $type = match (true) {
            in_array($typeName, ['varchar', 'char', 'string'], true)          => 'string',
            in_array($typeName, ['text', 'mediumtext'], true)                  => 'text',
            $typeName === 'longtext'                                           => 'longText',
            in_array($typeName, ['int', 'integer', 'mediumint'], true)         => 'integer',
            in_array($typeName, ['bigint', 'unsigned bigint'], true)           => 'bigInteger',
            $typeName === 'smallint'                                           => 'smallInteger',
            $typeName === 'tinyint'                                            => 'boolean',
            in_array($typeName, ['decimal', 'numeric'], true)                  => 'decimal',
            in_array($typeName, ['float', 'real', 'double'], true)             => 'float',
            $typeName === 'date'                                               => 'date',
            in_array($typeName, ['datetime', 'timestamp'], true)               => 'datetime',
            in_array($typeName, ['json', 'jsonb'], true)                       => 'json',
            default                                                            => 'string',
        };

        return [$type, []];
    }

    /**
     * Query MySQL information to retrieve enum values for a column.
     */
    private function getEnumValues(string $table, string $column): array
    {
        try {
            $rows = \DB::select('SHOW COLUMNS FROM `' . $table . '` WHERE Field = ?', [$column]);

            if (!empty($rows)) {
                $typeString = $rows[0]->Type ?? '';
                if (preg_match("/^enum\('(.+)'\)$/", $typeString, $matches)) {
                    return array_map(
                        static fn(string $v) => trim($v, "'"),
                        explode("','", $matches[1])
                    );
                }
            }
        } catch (\Throwable) {
            // Non-MySQL driver or access issue; return empty and let user fill in
        }

        return [];
    }

    private function guessRelatedModel(string $columnName): string
    {
        // user_id → User,  category_id → Category
        return \Illuminate\Support\Str::studly(str_replace('_id', '', $columnName));
    }
}
