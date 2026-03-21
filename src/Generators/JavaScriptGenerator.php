<?php

namespace LaravelScaffolding\Scaffolding\Generators;

use Illuminate\Support\Str;
use LaravelScaffolding\Scaffolding\Support\FieldDefinition;
use LaravelScaffolding\Scaffolding\Support\ModuleConfig;
use LaravelScaffolding\Scaffolding\Support\StubRenderer;

final class JavaScriptGenerator
{
    public function __construct(
        private readonly StubRenderer $renderer,
    ) {}

    /**
     * @return string[]
     */
    public function generate(ModuleConfig $config): array
    {
        if (!$config->useDataTable) {
            return [];
        }

        $class      = $config->className();
        $plural     = Str::plural($class);
        $className  = "{$class}IndexClass";
        $objectName = "{$class}IndexObject";

        $content = $this->renderer->render("{$config->cssFramework}/index.js.stub", [
            'className'       => $className,
            'objectName'      => $objectName,
            'configElementId' => $config->configElementId(),
            'tableElementId'  => $config->tableElementId(),
            'dtColumns'       => $this->buildDtColumns($config),
            'renderActionsBody' => $this->buildRenderActionsBody(),
        ]);

        $path = base_path($config->jsFilePath());
        $this->renderer->write($path, $content, $config->force);

        return [$path];
    }

    private function buildDtColumns(ModuleConfig $config): string
    {
        $columns = [];

        foreach ($config->fields as $field) {
            if (!$field->isDataTableColumn()) {
                continue;
            }

            $key = $field->dataKey();
            $columns[] = "\t\t\t\t{ data: '{$key}' },";
        }

        // Actions column (always last)
        $columns[] = "\t\t\t\t{";
        $columns[] = "\t\t\t\t\tdata: null,";
        $columns[] = "\t\t\t\t\torderable: false,";
        $columns[] = "\t\t\t\t\tsearchable: false,";
        $columns[] = "\t\t\t\t\tclassName: 'text-center',";
        $columns[] = "\t\t\t\t\trender: (data, type, row) => this.renderActions(row),";
        $columns[] = "\t\t\t\t},";

        return implode("\n", $columns);
    }

    /**
     * Generates the renderActions method body — always uses the standard Edit / View / Delete pattern.
     * The user can extend with renderStatusActionItem etc. as needed.
     */
    private function buildRenderActionsBody(): string
    {
        return <<<'JS'
		renderActions(row) {
			return `
				<div class="d-inline-flex align-items-center gap-2">
					<a class="btn btn-sm btn-outline-primary btn-rounded" href="${row.edit_url}" title="Edit">
						<i class="mdi mdi-pencil"></i>
					</a>
					<a class="btn btn-sm btn-outline-info btn-rounded" href="${row.view_url}" title="View">
						<i class="mdi mdi-eye"></i>
					</a>
					<div class="btn-group">
						<button type="button" class="btn btn-sm btn-outline-secondary btn-rounded dropdown-toggle"
							data-bs-toggle="dropdown" aria-expanded="false" title="More actions">
							<i class="mdi mdi-dots-vertical"></i>
						</button>
						<ul class="dropdown-menu dropdown-menu-end">
							<li>
								<form method="POST" action="${row.delete_url}">
									<input type="hidden" name="_token" value="${this.csrfToken}">
									<input type="hidden" name="_method" value="DELETE">
									<button class="dropdown-item text-danger" type="submit">Delete</button>
								</form>
							</li>
						</ul>
					</div>
				</div>
			`;
		}
JS;
    }
}
