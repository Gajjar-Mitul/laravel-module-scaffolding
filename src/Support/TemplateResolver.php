<?php

namespace LaravelScaffolding\Scaffolding\Support;

use Illuminate\Support\Str;

final class TemplateResolver
{
    /**
     * @param  array<string, string> $extraTokens
     */
    public static function resolve(string $template, string $moduleName, array $extraTokens = []): string
    {
        $tokens = [
            '{Module}'  => Str::studly($moduleName),
            '{module}'  => Str::snake($moduleName),
            '{Modules}' => Str::studly(Str::plural($moduleName)),
            '{modules}' => Str::snake(Str::plural($moduleName)),
        ];

        foreach ($extraTokens as $token => $value) {
            $normalized = str_starts_with($token, '{') ? $token : '{' . $token . '}';
            $tokens[$normalized] = $value;
        }

        return strtr($template, $tokens);
    }
}
