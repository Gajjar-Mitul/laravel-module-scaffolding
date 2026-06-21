<?php

namespace LaravelScaffolding\Scaffolding\Generators;

use LaravelScaffolding\Scaffolding\Support\ModuleConfig;
use LaravelScaffolding\Scaffolding\Support\StubRenderer;

final class EventGenerator
{
    public function __construct(
        private readonly StubRenderer $renderer,
    ) {}

    /**
     * @return string[]
     */
    public function generate(ModuleConfig $config): array
    {
        $content = $this->renderer->render('event.stub', [
            'namespace' => $config->eventNamespace(),
            'eventClass' => $config->eventClassName(),
            'modelFullClass' => $config->modelFullClass(),
            'modelClass' => $config->modelClassName(),
            'variable' => $config->variableName(),
        ]);

        $path = base_path($config->eventRelativePath());
        $this->renderer->write($path, $content, $config->force);

        return [$path];
    }
}
