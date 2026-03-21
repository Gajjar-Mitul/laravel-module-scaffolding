<?php

namespace LaravelScaffolding\Scaffolding\Support;

use Illuminate\Support\Facades\File;
use RuntimeException;

/**
 * Loads stub files and replaces [[ TOKEN ]] placeholders.
 *
 * We intentionally use [[ ]] instead of {{ }} to avoid conflicts
 * with Laravel's Blade template syntax in generated view files.
 */
final class StubRenderer
{
    public function __construct(
        private readonly ?string $customStubsPath = null,
    ) {}

    /**
     * Load a stub file by relative name and replace all tokens.
     *
     * @param  string               $stubName  e.g. "migration.stub" or "bootstrap/index.blade.stub"
     * @param  array<string, string> $tokens
     */
    public function render(string $stubName, array $tokens): string
    {
        $content = $this->loadStub($stubName);

        foreach ($tokens as $key => $value) {
            $content = str_replace('[[ ' . $key . ' ]]', (string) $value, $content);
            $content = str_replace('[[' . $key . ']]', (string) $value, $content);
        }

        return $content;
    }

    /**
     * Write a rendered stub to an absolute file path.
     * Creates the directory if it does not exist.
     *
     * @throws \RuntimeException when file exists and force is false.
     */
    public function write(string $absolutePath, string $content, bool $force = false): void
    {
        if (File::exists($absolutePath) && !$force) {
            throw new \RuntimeException("File already exists: {$absolutePath}. Use --force to overwrite.");
        }

        File::ensureDirectoryExists(dirname($absolutePath));
        File::put($absolutePath, $content);
    }

    /**
     * Prepend content to an existing file.
     */
    public function prepend(string $absolutePath, string $content): void
    {
        $existing = File::exists($absolutePath) ? File::get($absolutePath) : '';
        File::put($absolutePath, $content . $existing);
    }

    /**
     * Append content to an existing file.
     */
    public function append(string $absolutePath, string $content): void
    {
        File::append($absolutePath, $content);
    }

    private function loadStub(string $stubName): string
    {
        // 1. Custom stubs path (published stubs)
        if ($this->customStubsPath) {
            $custom = rtrim($this->customStubsPath, '/') . '/' . $stubName;
            if (File::exists($custom)) {
                return File::get($custom);
            }
        }

        // 2. Packaged stubs
        $package = dirname(__DIR__, 2) . '/stubs/' . $stubName;
        if (File::exists($package)) {
            return File::get($package);
        }

        throw new RuntimeException("Stub not found: [{$stubName}]");
    }
}
