<?php

namespace LaravelScaffolding\Scaffolding\Generators;

use Illuminate\Support\Str;
use LaravelScaffolding\Scaffolding\Support\FieldDefinition;
use LaravelScaffolding\Scaffolding\Support\ModuleConfig;
use LaravelScaffolding\Scaffolding\Support\StubRenderer;

/**
 * Generates PHP 8.1+ backed enum classes for each enum field.
 * e.g. PostStatusEnum, PostTypeEnum
 */
final class EnumGenerator
{
    public function __construct(
        private readonly StubRenderer $renderer,
    ) {}

    /**
     * @return string[]
     */
    public function generate(ModuleConfig $config): array
    {
        $paths = [];

        foreach ($config->fields as $field) {
            if ($field->type !== 'enum' || empty($field->enumValues)) {
                continue;
            }

            $className = $config->enumClassName($field->name);

            $content = $this->renderer->render('enum.stub', [
                'namespace' => $config->enumNamespace(),
                'class'     => $className,
                'cases'     => $this->buildCases($field),
            ]);

            $path = base_path($config->enumRelativePath($className));

            $this->renderer->write($path, $content, $config->force);
            $paths[] = $path;
        }

        return $paths;
    }

    private function buildCases(FieldDefinition $field): string
    {
        $lines = [];

        foreach ($field->enumValues as $value) {
            $caseName = strtoupper(Str::snake($value));
            $lines[]  = "    case {$caseName} = '{$value}';";
        }

        return implode("\n", $lines);
    }
}
