<?php

namespace LaravelScaffolding\Scaffolding\Resolvers;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use LaravelScaffolding\Scaffolding\Detectors\SchemaDetector;
use LaravelScaffolding\Scaffolding\Support\FieldDefinition;
use Symfony\Component\Yaml\Yaml;

/**
 * Resolves field definitions using a three-tier strategy:
 *   1. Existing DB table   → read schema automatically
 *   2. YAML file           → parse field definitions from scaffolding/{name}.yml
 *   3. Interactive prompts → ask the user field-by-field
 */
final class FieldResolver
{
    private const FIELD_TYPES = [
        'string', 'text', 'longText',
        'integer', 'bigInteger', 'smallInteger',
        'boolean',
        'decimal', 'float',
        'date', 'datetime',
        'enum',
        'foreignId',
        'json',
    ];

    public function __construct(
        private readonly SchemaDetector $schemaDetector,
    ) {}

    /**
     * @return FieldDefinition[]
     */
    public function resolve(string $tableName, string $moduleName, Command $command): array
    {
        // ── Tier 1: Existing table ────────────────────────────────────────────
        if ($this->schemaDetector->tableExists($tableName)) {
            $command->line("  <fg=cyan>→</> Table <comment>{$tableName}</comment> found — reading schema from database.");
            return $this->schemaDetector->getFields($tableName);
        }

        // ── Tier 2: YAML file ─────────────────────────────────────────────────
        $yamlPath = $this->findYamlFile($moduleName);
        if ($yamlPath !== null) {
            $command->line("  <fg=cyan>→</> YAML definition found: <comment>{$yamlPath}</comment>");
            return $this->parseYaml($yamlPath);
        }

        // ── Tier 3: Interactive prompts ───────────────────────────────────────
        $command->line('  <fg=cyan>→</> No table or YAML found — entering interactive field definition.');
        return $this->promptFields($command);
    }

    // ── YAML parsing ──────────────────────────────────────────────────────────

    private function findYamlFile(string $moduleName): ?string
    {
        $dir  = base_path(config('scaffolding.paths.yaml', 'scaffolding'));
        $name = strtolower($moduleName);

        foreach (["{$dir}/{$name}.yml", "{$dir}/{$name}.yaml"] as $path) {
            if (File::exists($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * @return FieldDefinition[]
     */
    private function parseYaml(string $path): array
    {
        $data   = Yaml::parseFile($path);
        $fields = [];

        foreach ($data['fields'] ?? [] as $name => $def) {
            $type     = (string)  ($def['type']     ?? 'string');
            $nullable = (bool)    ($def['nullable']  ?? false);
            $unique   = (bool)    ($def['unique']    ?? false);
            $default  = $def['default'] ?? null;

            $enumValues   = [];
            $relatedModel = null;

            if ($type === 'enum') {
                $raw        = $def['values'] ?? [];
                $enumValues = is_array($raw) ? $raw : array_map('trim', explode(',', (string) $raw));
            }

            if ($type === 'foreignId' || str_ends_with($name, '_id')) {
                $type         = 'foreignId';
                $relatedModel = (string) ($def['related'] ?? Str::studly(str_replace('_id', '', $name)));
            }

            $fields[] = new FieldDefinition(
                name: $name,
                type: $type,
                nullable: $nullable,
                default: $default,
                enumValues: $enumValues,
                relatedModel: $relatedModel,
                unique: $unique,
            );
        }

        return $fields;
    }

    // ── Interactive prompts ───────────────────────────────────────────────────

    /**
     * @return FieldDefinition[]
     */
    private function promptFields(Command $command): array
    {
        $fields = [];

        $command->line('');
        $command->line('  Available types: <comment>' . implode(', ', self::FIELD_TYPES) . '</comment>');
        $command->line('  Enter an empty field name to finish.');
        $command->line('');

        while (true) {
            $name = $command->ask('  Field name');

            if (empty($name)) {
                break;
            }

            if (!preg_match('/^[a-z][a-z0-9_]*$/', $name)) {
                $command->error('  Field name must be snake_case (e.g. post_title, category_id).');
                continue;
            }

            $type = $command->choice('  Type', self::FIELD_TYPES, 0);

            $nullable     = false;
            $unique       = false;
            $default      = null;
            $enumValues   = [];
            $relatedModel = null;

            if ($type === 'enum') {
                $raw        = (string) $command->ask('  Enum values (comma-separated, e.g. draft,published)');
                $enumValues = array_map('trim', explode(',', $raw));
                $default    = $command->ask('  Default value', null);
            }

            if ($type === 'foreignId' || str_ends_with($name, '_id')) {
                $type         = 'foreignId';
                $guessed      = Str::studly(str_replace('_id', '', $name));
                $relatedModel = (string) $command->ask('  Related model class', $guessed);
                $nullable     = $command->confirm('  Nullable?', false);
            } else {
                $nullable = $command->confirm('  Nullable?', false);

                if ($type === 'string') {
                    $unique = $command->confirm('  Unique?', false);
                }

                if (!$nullable && !in_array($type, ['boolean', 'enum'], true)) {
                    $input   = $command->ask('  Default value (leave empty for none)', '');
                    $default = $input !== '' ? $input : null;
                }
            }

            $fields[] = new FieldDefinition(
                name: $name,
                type: $type,
                nullable: $nullable,
                default: $default,
                enumValues: $enumValues,
                relatedModel: $relatedModel,
                unique: $unique,
            );

            $command->line("  <fg=green>✓</> Added: <comment>{$name}</comment> ({$type})");
        }

        if (empty($fields)) {
            $command->warn('  No fields defined — placeholder comments will be left in generated files.');
        }

        return $fields;
    }
}
