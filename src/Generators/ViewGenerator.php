<?php

namespace LaravelScaffolding\Scaffolding\Generators;

use Illuminate\Support\Str;
use LaravelScaffolding\Scaffolding\Support\FieldDefinition;
use LaravelScaffolding\Scaffolding\Support\ModuleConfig;
use LaravelScaffolding\Scaffolding\Support\StubRenderer;

final class ViewGenerator
{
    public function __construct(
        private readonly StubRenderer $renderer,
    ) {}

    /**
     * @return string[]
     */
    public function generate(ModuleConfig $config): array
    {
        $framework = $config->cssFramework; // bootstrap|tailwind
        $paths     = [];

        $paths = array_merge($paths, $this->generateIndex($config, $framework));
        $paths = array_merge($paths, $this->generateCreate($config, $framework));
        $paths = array_merge($paths, $this->generateEdit($config, $framework));
        $paths = array_merge($paths, $this->generateShow($config, $framework));

        return $paths;
    }

    // ── Index view ────────────────────────────────────────────────────────────

    private function generateIndex(ModuleConfig $config, string $fw): array
    {
        $content = $this->renderer->render("{$fw}/index.blade.stub", [
            'layout'          => $config->layout,
            'class'           => $config->className(),
            'classPlural'     => $config->classNamePlural(),
            'routeName'       => $config->routeName(),
            'configElementId' => $config->configElementId(),
            'tableElementId'  => $config->tableElementId(),
            'jsPath'          => $config->jsFilePath(),
            'tableContent'    => $config->useDataTable
                ? $this->buildDataTableIndex($config)
                : $this->buildSimpleTableIndex($config),
        ]);

        $path = $this->viewPath($config, 'index');
        $this->renderer->write($path, $content, $config->force);

        return [$path];
    }

    private function buildDataTableIndex(ModuleConfig $config): string
    {
        $headers = '';
        foreach ($config->fields as $field) {
            if ($field->isDataTableColumn()) {
                $headers .= '                        <th>' . $field->label() . '</th>' . "\n";
            }
        }
        $headers .= '                        <th class="text-center">Actions</th>';

        return <<<HTML

                    <div
                        id="{$config->configElementId()}"
                        data-url="{{ route('{$config->routeName()}.data') }}"
                        data-csrf="{{ csrf_token() }}">
                    </div>

                    <table id="{$config->tableElementId()}" class="table table-striped table-hover w-100">
                        <thead>
                            <tr>
{$headers}
                            </tr>
                        </thead>
                    </table>
HTML;
    }

    private function buildSimpleTableIndex(ModuleConfig $config): string
    {
        $variable = $config->variableName();
        $plural   = $config->variableNamePlural();

        // Table headers
        $headers = '';
        foreach ($config->fields as $field) {
            $headers .= '                        <th>' . $field->label() . '</th>' . "\n";
        }
        $headers .= '                        <th class="text-center">Actions</th>';

        // Table rows
        $cells = '';
        foreach ($config->fields as $field) {
            $cells .= "                        <td>{{ \${$variable}->{$field->name} }}</td>\n";
        }
        $cells .= <<<BLADE
                        <td class="text-center">
                            <a href="{{ route('{$config->routeName()}.edit', \${$variable}) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            <a href="{{ route('{$config->routeName()}.show', \${$variable}) }}" class="btn btn-sm btn-outline-info">View</a>
                            <form method="POST" action="{{ route('{$config->routeName()}.destroy', \${$variable}) }}" class="d-inline"
                                  onsubmit="return confirm('Are you sure?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        </td>
BLADE;

        $routeName = $config->routeName();

        return <<<BLADE

                    @forelse (\${$plural} as \${$variable})
                        <tr>
{$cells}
                        </tr>
                    @empty
                        <tr>
                            <td colspan="100%" class="text-center text-muted py-4">No records found.</td>
                        </tr>
                    @endforelse
BLADE;
    }

    // ── Create view ───────────────────────────────────────────────────────────

    private function generateCreate(ModuleConfig $config, string $fw): array
    {
        $content = $this->renderer->render("{$fw}/create.blade.stub", [
            'layout'     => $config->layout,
            'class'      => $config->className(),
            'routeName'  => $config->routeName(),
            'formFields' => $this->buildFormFields($config, null),
        ]);

        $path = $this->viewPath($config, 'create');
        $this->renderer->write($path, $content, $config->force);

        return [$path];
    }

    // ── Edit view ─────────────────────────────────────────────────────────────

    private function generateEdit(ModuleConfig $config, string $fw): array
    {
        $content = $this->renderer->render("{$fw}/edit.blade.stub", [
            'layout'     => $config->layout,
            'class'      => $config->className(),
            'routeName'  => $config->routeName(),
            'variable'   => $config->variableName(),
            'formFields' => $this->buildFormFields($config, $config->variableName()),
        ]);

        $path = $this->viewPath($config, 'edit');
        $this->renderer->write($path, $content, $config->force);

        return [$path];
    }

    // ── Show view ─────────────────────────────────────────────────────────────

    private function generateShow(ModuleConfig $config, string $fw): array
    {
        $content = $this->renderer->render("{$fw}/show.blade.stub", [
            'layout'     => $config->layout,
            'class'      => $config->className(),
            'routeName'  => $config->routeName(),
            'variable'   => $config->variableName(),
            'showFields' => $this->buildShowFields($config),
        ]);

        $path = $this->viewPath($config, 'show');
        $this->renderer->write($path, $content, $config->force);

        return [$path];
    }

    // ── Form field HTML generation ────────────────────────────────────────────

    /**
     * @param string|null $model  Variable name for editing, null for create
     */
    private function buildFormFields(ModuleConfig $config, ?string $model): string
    {
        $html = '';

        foreach ($config->fields as $field) {
            if ($field->isForeignKey()) {
                continue; // Skip FK fields — typically handled by a select from a relation
            }

            $html .= $this->buildFormField($field, $model);
        }

        return $html;
    }

    private function buildFormField(FieldDefinition $field, ?string $model): string
    {
        $label      = $field->label();
        $name       = $field->name;
        $inputType  = $field->formInputType();
        $oldValue   = $model
            ? "old('{$name}', \${$model}->{$name})"
            : "old('{$name}')";
        $required   = $field->nullable ? '' : ' required';
        $errorBlock = $this->buildErrorBlock($name);

        if ($inputType === 'textarea') {
            return <<<HTML

                <div class="mb-3">
                    <label for="{$name}" class="form-label">{$label}{$required}</label>
                    <textarea
                        name="{$name}"
                        id="{$name}"
                        class="form-control @error('{$name}') is-invalid @enderror"
                        rows="4"{$required}>{{ {$oldValue} }}</textarea>
{$errorBlock}
                </div>
HTML;
        }

        if ($inputType === 'select' && !empty($field->enumValues)) {
            $options = '';
            foreach ($field->enumValues as $val) {
                $optLabel = ucfirst($val);
                $options .= "                        <option value=\"{$val}\" {{ {$oldValue} === '{$val}' ? 'selected' : '' }}>{$optLabel}</option>\n";
            }

            return <<<HTML

                <div class="mb-3">
                    <label for="{$name}" class="form-label">{$label}{$required}</label>
                    <select name="{$name}" id="{$name}" class="form-select @error('{$name}') is-invalid @enderror"{$required}>
                        <option value="">-- Select --</option>
{$options}                    </select>
{$errorBlock}
                </div>
HTML;
        }

        if ($inputType === 'checkbox') {
            $checked = "{{ {$oldValue} ? 'checked' : '' }}";
            return <<<HTML

                <div class="mb-3 form-check">
                    <input
                        type="checkbox"
                        name="{$name}"
                        id="{$name}"
                        value="1"
                        class="form-check-input @error('{$name}') is-invalid @enderror"
                        {$checked}>
                    <label class="form-check-label" for="{$name}">{$label}</label>
{$errorBlock}
                </div>
HTML;
        }

        // Default: input
        return <<<HTML

                <div class="mb-3">
                    <label for="{$name}" class="form-label">{$label}{$required}</label>
                    <input
                        type="{$inputType}"
                        name="{$name}"
                        id="{$name}"
                        class="form-control @error('{$name}') is-invalid @enderror"
                        value="{{ {$oldValue} }}"{$required}>
{$errorBlock}
                </div>
HTML;
    }

    private function buildErrorBlock(string $name): string
    {
        return <<<HTML
                    @error('{$name}')
                        <div class="invalid-feedback">{{ \$message }}</div>
                    @enderror
HTML;
    }

    // ── Show fields HTML generation ───────────────────────────────────────────

    private function buildShowFields(ModuleConfig $config): string
    {
        $variable = $config->variableName();
        $html     = '';

        foreach ($config->fields as $field) {
            $label = $field->label();
            $value = "\${$variable}->{$field->name}";

            $html .= <<<HTML

                <div class="row mb-3">
                    <div class="col-sm-3 fw-semibold text-muted">{$label}</div>
                    <div class="col-sm-9">{{ {$value} }}</div>
                </div>
HTML;
        }

        return $html;
    }

    // ── Path helper ───────────────────────────────────────────────────────────

    private function viewPath(ModuleConfig $config, string $view): string
    {
        $base   = config('scaffolding.paths.views', 'resources/views/modules');
        $folder = Str::snake($config->name);

        return base_path("{$base}/{$folder}/{$view}.blade.php");
    }
}
