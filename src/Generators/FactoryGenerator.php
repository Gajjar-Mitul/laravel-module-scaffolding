<?php

namespace LaravelScaffolding\Scaffolding\Generators;

use LaravelScaffolding\Scaffolding\Support\ModuleConfig;
use LaravelScaffolding\Scaffolding\Support\StubRenderer;

final class FactoryGenerator
{
    public function __construct(
        private readonly StubRenderer $renderer,
    ) {}

    /**
     * @return string[]
     */
    public function generate(ModuleConfig $config): array
    {
        $content = $this->renderer->render('factory.stub', [
            'namespace' => $config->factoryNamespace(),
            'factoryClass' => $config->factoryClassName(),
            'modelFullClass' => $config->modelFullClass(),
            'modelClass' => $config->modelClassName(),
        ]);

        $path = base_path($config->factoryRelativePath());
        $this->renderer->write($path, $content, $config->force);

        return [$path];
    }
}
