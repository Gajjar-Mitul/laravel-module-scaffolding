<?php

namespace LaravelScaffolding\Scaffolding\Detectors;

use Illuminate\Support\Facades\File;

/**
 * Scans the project's Blade views to determine which layout is most used.
 */
final class LayoutDetector
{
    /**
     * Find the most commonly referenced @extends() value across all Blade files.
     * Falls back to 'app' if nothing is found.
     */
    public function detect(): string
    {
        $viewsPath = resource_path('views');

        if (!File::isDirectory($viewsPath)) {
            return 'layouts.app';
        }

        $counts = [];

        foreach (File::allFiles($viewsPath) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $content = File::get($file->getPathname());
            $matches = [];

            if (preg_match_all("/@extends\s*\(\s*['\"]([^'\"]+)['\"]\s*\)/", $content, $matches)) {
                foreach ($matches[1] as $layout) {
                    $counts[$layout] = ($counts[$layout] ?? 0) + 1;
                }
            }
        }

        if (empty($counts)) {
            return $this->fallbackLayout();
        }

        arsort($counts);

        return (string) array_key_first($counts);
    }

    private function fallbackLayout(): string
    {
        $candidates = [
            'layouts.app' => resource_path('views/layouts/app.blade.php'),
            'app'         => resource_path('views/app.blade.php'),
            'layouts.master' => resource_path('views/layouts/master.blade.php'),
            'master'      => resource_path('views/master.blade.php'),
        ];

        foreach ($candidates as $layout => $path) {
            if (File::exists($path)) {
                return $layout;
            }
        }

        return 'layouts.app';
    }
}
