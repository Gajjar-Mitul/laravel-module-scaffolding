<?php

namespace LaravelScaffolding\Scaffolding\Generators;

use LaravelScaffolding\Scaffolding\Support\ModuleConfig;
use LaravelScaffolding\Scaffolding\Support\StubRenderer;

final class ServiceGenerator
{
    public function __construct(
        private readonly StubRenderer $renderer,
    ) {}

    /**
     * @return string[]
     */
    public function generate(ModuleConfig $config): array
    {
        $content = $this->renderer->render('service.stub', [
            'serviceNamespace' => $config->serviceNamespace(),
            'modelFullClass'   => $config->modelFullClass(),
            'class'            => $config->className(),
            'variable'         => $config->variableName(),
        ]);

        $path = base_path($config->serviceRelativePath());
        $this->renderer->write($path, $content, $config->force);

        return [$path];
    }
}
