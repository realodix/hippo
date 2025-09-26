<?php

namespace Realodix\Hippo\Cache;

use Realodix\Hippo\Enums\Mode;
use Realodix\Hippo\Enums\Scope;
use Symfony\Component\Filesystem\Filesystem;

final class Cache
{
    private bool $cacheCleared = false;

    public function __construct(
        private Repository $repository,
        private Filesystem $filesystem,
    ) {}

    public function repository(): Repository
    {
        return $this->repository;
    }

    /**
     * Prepare cache state before a run.
     *
     * - If not forced: clean stale entries (files that no longer exist)
     * - If forced: clear entire cache, but only once per run
     *
     * @param list<string> $activeOutputFiles List of active compiled output files
     */
    public function prepareForRun(
        ?string $cachePath,
        Mode $mode,
        Scope $scope = Scope::F,
        array $activeOutputFiles = [],
    ): void {
        $resolvedCachePath = $this->resolveCachePath($cachePath);
        $this->repository()
            ->setCacheFile($resolvedCachePath)
            ->setScope($scope)
            ->load();

        if ($mode !== Mode::Force) {
            $this->cleanStaleEntries($activeOutputFiles);
        }

        if ($mode == Mode::Force && !$this->cacheCleared) {
            $this->repository()->clear();
            $this->cacheCleared = true;
        }
    }

    /**
     * Remove cache entries that are no longer valid.
     *
     * - If a list of active files is provided, remove any entry not in that list.
     * - Otherwise, remove entries for files that no longer exist on disk.
     *
     * @param list<string> $activeOutputFiles List of active compiled output files
     */
    private function cleanStaleEntries(array $activeOutputFiles = []): void
    {
        $modified = false;

        foreach ($this->repository()->all() as $relativePath => $entry) {
            $isStale = !empty($activeOutputFiles)
                ? !in_array($relativePath, $activeOutputFiles)
                : !$this->filesystem->exists($relativePath);

            if ($isStale) {
                $this->repository()->remove($relativePath);
                $modified = true;
            }
        }

        if ($modified) {
            $this->repository()->save();
        }
    }

    /**
     * Resolves the cache file path and ensures its directory exists.
     *
     * This method is the single source of truth for determining the final cache file path.
     * - If the provided path is null or empty, it defaults to the default cache
     *   filename in the current working directory.
     * - If the path is relative, it's made absolute based on the current working directory.
     * - It assumes the path is a directory and creates it if it doesn't exist.
     * - Finally, it appends the default cache filename to the directory path.
     *
     * @param string|null $cachePath The user-provided cache directory path (can be
     *                               relative, absolute, or null).
     * @return string The absolute path to the final cache file.
     */
    private function resolveCachePath(?string $cachePath): string
    {
        // If no cache path is provided, use the default in the current working directory.
        if (empty($cachePath)) {
            return getcwd().DIRECTORY_SEPARATOR.Repository::DEFAULT_CACHE_FILENAME;
        }

        // If the path is not absolute, make it relative to the current working directory.
        $absolutePath = $cachePath;
        if (!$this->filesystem->isAbsolutePath($absolutePath)) {
            $absolutePath = getcwd().DIRECTORY_SEPARATOR.$absolutePath;
        }

        // At this point, we assume the path is a directory.
        // Create it if it doesn't exist.
        if (!$this->filesystem->exists($absolutePath)) {
            $this->filesystem->mkdir($absolutePath);
        }

        // Append the default cache file name and return the full path.
        return rtrim($absolutePath, DIRECTORY_SEPARATOR)
            .DIRECTORY_SEPARATOR
            .Repository::DEFAULT_CACHE_FILENAME;
    }

    /**
     * Checks if the file has changed by comparing its hash with the cached hash.
     */
    public function isFileChanged(string $filePath): bool
    {
        $cachedEntry = $this->repository()->get($filePath);

        if (!isset($cachedEntry['file_hash']) || !$this->filesystem->exists($filePath)) {
            return true; // Treat as changed if no hash or file doesn't exist
        }

        return $cachedEntry['file_hash'] !== hash_file(Repository::HASH_ALGO, $filePath);
    }
}
