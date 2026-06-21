<?php

namespace LaravelScaffolding\Scaffolding\Generators;

use LaravelScaffolding\Scaffolding\Support\FieldDefinition;
use LaravelScaffolding\Scaffolding\Support\ModuleConfig;
use LaravelScaffolding\Scaffolding\Support\StubRenderer;

final class ControllerGenerator
{
    public function __construct(
        private readonly StubRenderer $renderer,
    ) {}

    /**
     * @return string[]
     */
    public function generate(ModuleConfig $config): array
    {
        $isSpatie  = $config->validationDriver === 'spatie';
        $class     = $config->className();
        $controllerClass = $config->controllerClassName();
        $modelClass = $config->modelClassName();
        $variable  = $config->variableName();
        $variablePlural = $config->variableNamePlural();
        $queryClass = $config->queryClassName();
        $serviceClass = $config->serviceClassName();
        $routeName  = $config->routeName();

        if ($isSpatie) {
            $storeClass  = $config->storeValidationClassName();
            $updateClass = $config->updateValidationClassName();
            $storeNs     = $config->dtoNamespace();
            $updateNs    = $config->dtoNamespace();
            $storeSignature  = "{$storeClass} \$data";
            $updateSignature = "{$updateClass} \$data, {$modelClass} \${$variable}";
            $storeData       = '$data->toArray()';
            $updateData      = '$data->toArray()';
            $validationImports = "use {$storeNs}\\{$storeClass};\nuse {$updateNs}\\{$updateClass};";
        } else {
            $storeClass  = $config->storeValidationClassName();
            $updateClass = $config->updateValidationClassName();
            $requestNs   = $config->requestNamespace();
            $storeSignature  = "{$storeClass} \$request";
            $updateSignature = "{$updateClass} \$request, {$modelClass} \${$variable}";
            $storeData       = '$request->validated()';
            $updateData      = '$request->validated()';
            $validationImports = "use {$requestNs}\\{$storeClass};\nuse {$requestNs}\\{$updateClass};";
        }

        $dataMethod = '';
        if ($config->useDataTable) {
            $dataMethod = $this->buildDataMethod($config);
        }

        $content = $this->renderer->render('controller.stub', [
            'controllerNamespace' => $config->controllerNamespace(),
            'baseControllerImport' => $config->baseController,
            'modelFullClass'      => $config->modelFullClass(),
            'queryFullClass'      => $config->queryFullClass(),
            'serviceFullClass'    => $config->serviceFullClass(),
            'validationImports'   => $validationImports,
            'class'               => $class,
            'controllerClass'     => $controllerClass,
            'modelClass'          => $modelClass,
            'queryClass'          => $queryClass,
            'serviceClass'        => $serviceClass,
            'variable'            => $variable,
            'variablePlural'      => $variablePlural,
            'storeSignature'      => $storeSignature,
            'updateSignature'     => $updateSignature,
            'storeData'           => $storeData,
            'updateData'          => $updateData,
            'viewPath'            => $config->viewPath(),
            'routeName'           => $routeName,
            'dataMethod'          => $dataMethod,
        ]);

        $path = base_path($config->controllerRelativePath());
        $this->renderer->write($path, $content, $config->force);

        return [$path];
    }

    private function buildDataMethod(ModuleConfig $config): string
    {
        $routeName  = $config->routeName();
        $variable   = $config->variableName();

        $mapLines = $this->buildDataMapLines($config, $variable);

        return <<<PHP

    public function data(): \\Illuminate\\Http\\JsonResponse
    {
        \$items = \$this->queries->getAllForDataTable();

        \$data = \$items->map(static function (\${$variable}) use (\$items): array {
            return [
{$mapLines}
                'edit_url'   => route('{$routeName}.edit', \${$variable}),
                'view_url'   => route('{$routeName}.show', \${$variable}),
                'delete_url' => route('{$routeName}.destroy', \${$variable}),
            ];
        });

        return response()->json(['data' => \$data]);
    }
PHP;
    }

    private function buildDataMapLines(ModuleConfig $config, string $variable): string
    {
        $lines = ["                'id' => \${$variable}->id,"];

        foreach ($config->fields as $field) {
            if (in_array($field->type, ['text', 'longText', 'json'], true)) {
                continue;
            }

            if ($field->type === 'enum') {
                $lines[] = "                '{$field->name}_label' => ucfirst((string) \${$variable}->{$field->name}?->value ?? \${$variable}->{$field->name}),";
            } elseif ($field->type === 'boolean') {
                $lines[] = "                '{$field->name}_label' => \${$variable}->{$field->name} ? 'Yes' : 'No',";
            } elseif (in_array($field->type, ['date', 'datetime'], true)) {
                $lines[] = "                '{$field->name}' => \${$variable}->{$field->name}?->format('Y-m-d H:i'),";
            } else {
                $lines[] = "                '{$field->name}' => \${$variable}->{$field->name},";
            }
        }

        return implode("\n", $lines);
    }
}
