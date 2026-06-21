<?php

namespace LaravelScaffolding\Scaffolding\Generators;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use LaravelScaffolding\Scaffolding\Support\FieldDefinition;
use LaravelScaffolding\Scaffolding\Support\ModuleConfig;
use LaravelScaffolding\Scaffolding\Support\StubRenderer;

final class ModelGenerator
{
    public function __construct(
        private readonly StubRenderer $renderer,
    ) {}

    /**
     * @return string[]
     */
    public function generate(ModuleConfig $config): array
    {
        $this->ensureTraitExists($config);

        $content = $this->renderer->render('model.stub', [
            'modelNamespace' => $config->modelNamespace(),
            'class'          => $config->modelClassName(),
            'tableName'      => $config->tableName(),
            'imports'        => $this->buildImports($config),
            'useStatements'  => $this->buildUseStatements($config),
            'fillable'       => $this->buildFillable($config),
            'casts'          => $this->buildCasts($config),
            'relationships'  => $this->buildRelationships($config),
        ]);

        $path = base_path($config->modelRelativePath());
        $this->renderer->write($path, $content, $config->force);

        return [$path];
    }

    // ── Imports ───────────────────────────────────────────────────────────────

    private function buildImports(ModuleConfig $config): string
    {
        $imports = ['use Illuminate\Database\Eloquent\Model;'];

        if ($config->softDeletes) {
            $imports[] = 'use Illuminate\Database\Eloquent\SoftDeletes;';
        }

        if ($config->auditColumns) {
            $traitNs   = $config->traitsBasePath;
            $namespace = $this->pathToNamespace($traitNs);
            $imports[] = "use {$namespace}\\TracksUserStamps;";
        }

        // Enum imports
        foreach ($this->enumFields($config) as $field) {
            $enumClass = $config->enumClassName($field->name);
            $imports[] = "use {$config->enumNamespace()}\\{$enumClass};";
        }

        // Relationship model imports (for belongsTo)
        foreach ($this->foreignKeyFields($config) as $field) {
            if ($field->relatedModel) {
                // Only import if the related model is in a known domain (skip User which is usually top-level)
                if (!in_array($field->relatedModel, ['User'], true)) {
                    $relatedNs = $config->namespace . '\\' . $field->relatedModel . '\\Models\\' . $field->relatedModel;
                    $imports[] = "use {$relatedNs};";
                } else {
                    $imports[] = 'use App\\Models\\User;';
                }
            }
        }

        return implode("\n", array_unique($imports));
    }

    private function buildUseStatements(ModuleConfig $config): string
    {
        $uses = [];

        if ($config->softDeletes) {
            $uses[] = '    use SoftDeletes;';
        }

        if ($config->auditColumns) {
            $uses[] = '    use TracksUserStamps;';
        }

        return implode("\n", $uses);
    }

    // ── Fillable ──────────────────────────────────────────────────────────────

    private function buildFillable(ModuleConfig $config): string
    {
        $fields = array_map(
            static fn(FieldDefinition $f) => "        '{$f->name}',",
            $config->fields
        );

        if ($config->auditColumns) {
            $fields[] = "        'created_by',";
            $fields[] = "        'updated_by',";
        }

        return implode("\n", $fields);
    }

    // ── Casts ─────────────────────────────────────────────────────────────────

    private function buildCasts(ModuleConfig $config): string
    {
        $items = [];

        foreach ($config->fields as $field) {
            $cast = null;

            if ($field->type === 'enum') {
                $enumClass = $config->enumClassName($field->name);
                $cast      = "{$enumClass}::class";
            } elseif ($field->castType() !== null) {
                $cast = "'{$field->castType()}'";
            }

            if ($cast !== null) {
                $items[] = "        '{$field->name}' => {$cast},";
            }
        }

        if (empty($items)) {
            return '';
        }

        $body = implode("\n", $items);
        return "\n    protected $" . "casts = [\n{$body}\n    ];\n";
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    private function buildRelationships(ModuleConfig $config): string
    {
        $methods = [];

        foreach ($this->foreignKeyFields($config) as $field) {
            if ($field->relatedModel === null) {
                continue;
            }

            $methodName = Str::camel(str_replace('_id', '', $field->name));
            $methods[]  = "    public function {$methodName}(): \\Illuminate\\Database\\Eloquent\\Relations\\BelongsTo\n"
                        . "    {\n"
                        . "        return \$this->belongsTo({$field->relatedModel}::class);\n"
                        . "    }";
        }

        if ($config->auditColumns) {
            $methods[] = "    public function creator(): \\Illuminate\\Database\\Eloquent\\Relations\\BelongsTo\n"
                       . "    {\n"
                       . "        return \$this->belongsTo(\\App\\Models\\User::class, 'created_by');\n"
                       . "    }";
            $methods[] = "    public function updater(): \\Illuminate\\Database\\Eloquent\\Relations\\BelongsTo\n"
                       . "    {\n"
                       . "        return \$this->belongsTo(\\App\\Models\\User::class, 'updated_by');\n"
                       . "    }";
        }

        return empty($methods) ? '' : "\n" . implode("\n\n", $methods) . "\n";
    }

    // ── TracksUserStamps trait scaffolding ────────────────────────────────────

    private function ensureTraitExists(ModuleConfig $config): void
    {
        if (!$config->auditColumns) {
            return;
        }

        $traitDir  = $config->traitsBasePath;
        $traitPath = base_path($traitDir . '/TracksUserStamps.php');

        if (File::exists($traitPath)) {
            return;
        }

        $namespace = $this->pathToNamespace($traitDir);

        $content = <<<PHP
<?php

namespace {$namespace};

use Illuminate\Support\Facades\Auth;

trait TracksUserStamps
{
    public static function bootTracksUserStamps(): void
    {
        static::creating(static function (self \$model): void {
            if (Auth::check()) {
                if (\$model->isFillable('created_by')) {
                    \$model->created_by = Auth::id();
                }
                if (\$model->isFillable('updated_by')) {
                    \$model->updated_by = Auth::id();
                }
            }
        });

        static::updating(static function (self \$model): void {
            if (Auth::check() && \$model->isFillable('updated_by')) {
                \$model->updated_by = Auth::id();
            }
        });
    }
}
PHP;

        File::ensureDirectoryExists(base_path($traitDir));
        File::put($traitPath, $content);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * @return FieldDefinition[]
     */
    private function enumFields(ModuleConfig $config): array
    {
        return array_filter($config->fields, static fn(FieldDefinition $f) => $f->type === 'enum');
    }

    /**
     * @return FieldDefinition[]
     */
    private function foreignKeyFields(ModuleConfig $config): array
    {
        return array_filter($config->fields, static fn(FieldDefinition $f) => $f->isForeignKey());
    }

    private function pathToNamespace(string $path): string
    {
        // "app/Shared/Traits" → "App\Shared\Traits"
        $segments    = explode('/', trim($path, '/'));
        $segments[0] = Str::studly($segments[0]);
        return implode('\\', $segments);
    }
}
