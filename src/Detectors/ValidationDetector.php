<?php

namespace LaravelScaffolding\Scaffolding\Detectors;

use Illuminate\Support\Facades\File;

/**
 * Detects whether the Spatie Laravel Data package is present.
 */
final class ValidationDetector
{
    public function isSpatieDataInstalled(): bool
    {
        return class_exists(\Spatie\LaravelData\Data::class)
            || $this->isInComposer('spatie/laravel-data');
    }

    private function isInComposer(string $package): bool
    {
        $path = base_path('composer.json');

        if (!File::exists($path)) {
            return false;
        }

        $composer = json_decode(File::get($path), true) ?? [];

        return isset($composer['require'][$package])
            || isset($composer['require-dev'][$package]);
    }
}
