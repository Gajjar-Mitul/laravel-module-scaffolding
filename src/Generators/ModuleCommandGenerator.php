<?php

namespace LaravelScaffolding\Scaffolding\Generators;

use LaravelScaffolding\Scaffolding\Support\ModuleConfig;
use LaravelScaffolding\Scaffolding\Support\StubRenderer;

final class ModuleCommandGenerator
{
    public function __construct(
        private readonly StubRenderer $renderer,
    ) {}

    /**
     * @return string[]
     */
    public function generate(ModuleConfig $config): array
    {
        $content = $this->renderer->render('module-command.stub', [
            'namespace' => $config->moduleCommandNamespace(),
            'commandClass' => $config->moduleCommandClassName(),
            'commandSignature' => 'module:sync-' . str_replace('_', '-', $config->routeName()),
            'commandDescription' => 'Sync ' . $config->className() . ' domain data.',
            'module' => $config->className(),
        ]);

        $path = base_path($config->moduleCommandRelativePath());
        $this->renderer->write($path, $content, $config->force);

        return [$path];
    }
}
