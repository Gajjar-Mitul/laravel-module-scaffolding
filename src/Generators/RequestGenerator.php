<?php

namespace LaravelScaffolding\Scaffolding\Generators;

use Illuminate\Support\Str;
use LaravelScaffolding\Scaffolding\Support\FieldDefinition;
use LaravelScaffolding\Scaffolding\Support\ModuleConfig;
use LaravelScaffolding\Scaffolding\Support\StubRenderer;

/**
 * Generates either FormRequest classes or Spatie Data DTO classes
 * depending on the configured validation driver.
 */
final class RequestGenerator
{
    public function __construct(
        private readonly StubRenderer $renderer,
    ) {}

    /**
     * @return string[]
     */
    public function generate(ModuleConfig $config): array
    {
        if ($config->validationDriver === 'spatie') {
            return $this->generateSpatieData($config);
        }

        return $this->generateFormRequests($config);
    }

    // ── FormRequest ───────────────────────────────────────────────────────────

    private function generateFormRequests(ModuleConfig $config): array
    {
        $paths = [];

        $storeClass  = 'Store' . $config->className() . 'Request';
        $updateClass = 'Update' . $config->className() . 'Request';
        $ns          = $config->requestNamespace();

        // Store request — all required fields enforced
        $storeContent = $this->renderer->render('form-request.stub', [
            'namespace' => $ns,
            'class'     => $storeClass,
            'imports'   => $this->buildFormRequestImports($config),
            'rules'     => $this->buildRules($config, isUpdate: false),
        ]);
        $storePath = base_path($this->requestPath($config, $storeClass));
        $this->renderer->write($storePath, $storeContent, $config->force);
        $paths[] = $storePath;

        // Update request — all fields optional (sometimes rule)
        $updateContent = $this->renderer->render('form-request.stub', [
            'namespace' => $ns,
            'class'     => $updateClass,
            'imports'   => $this->buildFormRequestImports($config),
            'rules'     => $this->buildRules($config, isUpdate: true),
        ]);
        $updatePath = base_path($this->requestPath($config, $updateClass));
        $this->renderer->write($updatePath, $updateContent, $config->force);
        $paths[] = $updatePath;

        return $paths;
    }

    // ── Spatie Data ───────────────────────────────────────────────────────────

    private function generateSpatieData(ModuleConfig $config): array
    {
        $paths = [];

        $storeClass  = 'Store' . $config->className() . 'Data';
        $updateClass = 'Update' . $config->className() . 'Data';
        $ns          = $config->dtoNamespace();

        $storeContent = $this->renderer->render('spatie-dto.stub', [
            'namespace'  => $ns,
            'class'      => $storeClass,
            'properties' => $this->buildSpatieProperties($config, isUpdate: false),
        ]);
        $storePath = base_path($this->dtoPath($config, $storeClass));
        $this->renderer->write($storePath, $storeContent, $config->force);
        $paths[] = $storePath;

        $updateContent = $this->renderer->render('spatie-dto.stub', [
            'namespace'  => $ns,
            'class'      => $updateClass,
            'properties' => $this->buildSpatieProperties($config, isUpdate: true),
        ]);
        $updatePath = base_path($this->dtoPath($config, $updateClass));
        $this->renderer->write($updatePath, $updateContent, $config->force);
        $paths[] = $updatePath;

        return $paths;
    }

    // ── Rule builders ─────────────────────────────────────────────────────────

    private function buildRules(ModuleConfig $config, bool $isUpdate): string
    {
        $lines = [];

        foreach ($config->fields as $field) {
            $rules = $this->fieldRules($field, $config, $isUpdate);
            $rulesStr = implode(', ', $rules);
            $lines[] = "            '{$field->name}' => [{$rulesStr}],";
        }

        return implode("\n", $lines);
    }

    private function fieldRules(FieldDefinition $field, ModuleConfig $config, bool $isUpdate): array
    {
        $rules = [];

        if ($isUpdate) {
            $rules[] = "'sometimes'";
        }

        $rules[] = $field->nullable ? "'nullable'" : "'required'";

        $rules = array_merge($rules, match ($field->type) {
            'string'                               => ["'string'", "'max:255'"],
            'text', 'longText'                     => ["'string'"],
            'integer', 'bigInteger', 'smallInteger' => ["'integer'"],
            'decimal', 'float'                     => ["'numeric'"],
            'boolean'                              => ["'boolean'"],
            'date'                                 => ["'date'"],
            'datetime'                             => ["'date'"],
            'foreignId'                            => ["'integer'", "'exists:" . \Illuminate\Support\Str::snake(\Illuminate\Support\Str::plural($field->relatedModel ?? 'users')) . ",id'"],
            'enum'                                 => ["Rule::enum(" . $this->enumClassName($config, $field) . "::class)"],
            'json'                                 => ["'array'"],
            default                                => [],
        });

        if ($field->unique && !$isUpdate) {
            $table = $config->tableName();
            $rules[] = "'unique:{$table},{$field->name}'";
        }

        return $rules;
    }

    private function buildFormRequestImports(ModuleConfig $config): string
    {
        $imports = [
            'use Illuminate\\Foundation\\Http\\FormRequest;',
        ];

        $enumFields = array_filter(
            $config->fields,
            static fn(FieldDefinition $field): bool => $field->type === 'enum' && !empty($field->enumValues)
        );

        if (!empty($enumFields)) {
            $imports[] = 'use Illuminate\\Validation\\Rule;';

            foreach ($enumFields as $field) {
                $imports[] = 'use ' . $config->enumNamespace() . '\\' . $this->enumClassName($config, $field) . ';';
            }
        }

        return implode("\n", array_values(array_unique($imports)));
    }

    private function enumClassName(ModuleConfig $config, FieldDefinition $field): string
    {
        return $config->className() . Str::studly($field->name) . 'Enum';
    }

    // ── Spatie property builders ──────────────────────────────────────────────

    private function buildSpatieProperties(ModuleConfig $config, bool $isUpdate): string
    {
        $lines = [];

        foreach ($config->fields as $field) {
            $phpType   = $this->fieldToPhpType($field, $isUpdate);
            $attribute = $this->fieldToSpatieAttribute($field);
            $lines[]   = "    {$attribute}public readonly {$phpType} \${$field->name},";
        }

        return implode("\n", $lines);
    }

    private function fieldToPhpType(FieldDefinition $field, bool $isUpdate): string
    {
        $base = match ($field->type) {
            'string', 'text', 'longText', 'enum' => 'string',
            'integer', 'bigInteger', 'smallInteger', 'foreignId' => 'int',
            'decimal', 'float'                   => 'float',
            'boolean'                            => 'bool',
            'date', 'datetime'                   => '\\Carbon\\Carbon',
            'json'                               => 'array',
            default                              => 'string',
        };

        $nullable = $field->nullable || $isUpdate;
        return $nullable ? "?{$base}" : $base;
    }

    private function fieldToSpatieAttribute(FieldDefinition $field): string
    {
        if ($field->nullable) {
            return '#[\\Spatie\\LaravelData\\Attributes\\Validation\\Nullable] ';
        }

        return '';
    }

    // ── Path helpers ──────────────────────────────────────────────────────────

    private function requestPath(ModuleConfig $config, string $class): string
    {
        $dir = str_replace('\\', '/', $config->requestNamespace());
        $dir = preg_replace('/^App\//i', 'app/', $dir);
        return "{$dir}/{$class}.php";
    }

    private function dtoPath(ModuleConfig $config, string $class): string
    {
        $dir = str_replace('\\', '/', $config->dtoNamespace());
        $dir = preg_replace('/^App\//i', 'app/', $dir);
        return "{$dir}/{$class}.php";
    }
}
