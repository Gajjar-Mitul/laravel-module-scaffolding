<?php

namespace LaravelScaffolding\Scaffolding\Detectors;

use Illuminate\Support\Facades\File;

/**
 * Detects whether a DataTables package is available in the project.
 */
final class DataTableDetector
{
    private const PACKAGES = [
        'yajra/laravel-datatables',
        'yajra/laravel-datatables-oracle',
    ];

    public function isInstalled(): bool
    {
        foreach (self::PACKAGES as $package) {
            if ($this->isInComposer($package) || $this->isInVendor($package)) {
                return true;
            }
        }

        return false;
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

    private function isInVendor(string $package): bool
    {
        return File::isDirectory(base_path("vendor/{$package}"));
    }
}
