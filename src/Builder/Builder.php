<?php

namespace Realodix\Hippo\Builder;

use Realodix\Hippo\Cache\Cache;
use Realodix\Hippo\Config\Config;
use Realodix\Hippo\Enums\Mode;
use Realodix\Hippo\Enums\Scope;
use Realodix\Hippo\OutputLogger;
use Symfony\Component\Filesystem\Filesystem;

final class Builder
{
    public function __construct(
        private Config $config,
        private Metadata $metadata,
        private Cache $cache,
        private Filesystem $filesystem,
        private OutputLogger $logger,
    ) {}

    /**
     * Main entry point for building filter lists.
     *
     * @param string|null $configFile Path to the configuration file.
     * @param bool $force If true, forces rebuild even when cache is valid.
     */
    public function handle(?string $configFile, bool $force = false): void
    {
        $mode = $force ? Mode::Force : Mode::Default;
        $config = $this->config->loadFromFile($configFile);

        // Prepare cache repository for this run
        $this->cache->prepareForRun($config, $mode, Scope::B);

        foreach ($config->builder()->filters as $filterConfig) {
            $outputPath = $filterConfig->outputPath;

            // Step 1: Read all source files or URLs
            $sources = $this->readSources($filterConfig);
            if ($sources === null) {
                $this->logger->skipped($outputPath);

                continue;
            }

            // Step 2: Generate a single hash from all source contents
            $sourceHash = $this->sourceHash($sources);

            // Step 3: Skip processing if cache is still valid
            $cacheEntry = $this->cache->repository()->get($outputPath);
            if (!$force && $this->isCacheValid($cacheEntry, $sourceHash)) {
                $this->logger->skipped($outputPath);

                continue;
            }

            // Step 4: Build and write output file
            $this->buildAndWrite($sources, $filterConfig, $cacheEntry, $sourceHash);
        }

        // Save all updated cache entries to disk
        $this->cache->repository()->save();
    }

    /**
     * Builds the final output file from sources and metadata, and updates cache.
     *
     * @param list<string> $sources The list of raw source contents.
     * @param \Realodix\Hippo\Config\ValueObject\FilterSet $filterConfig The configuration for the current filter set.
     * @param array{source_hash?: string, version?: string}|null $cacheEntry The previous cache entry, if available.
     * @param string $sourceHash The hash representing the current source state.
     */
    private function buildAndWrite(array $sources, $filterConfig, ?array $cacheEntry, string $sourceHash): void
    {
        $outputPath = $filterConfig->outputPath;
        $version = $this->determineVersion($filterConfig, $cacheEntry);
        $metadata = $this->metadata->create($filterConfig, $version);

        $content = collect($metadata)->merge(Cleaner::clean($sources))
            ->implode("\n")."\n";

        $this->filesystem->dumpFile($outputPath, $content);
        $this->logger->processed($outputPath, null, null);

        $this->cache->repository()->set($outputPath, [
            'source_hash' => $sourceHash,
            'version' => $version,
        ]);
    }

    /**
     * Reads all source files or URLs defined in the configuration.
     * Returns null if any source cannot be read.
     *
     * @param \Realodix\Hippo\Config\ValueObject\FilterSet $config
     * @return list<string>|null The list of source contents, or null if a read fails.
     */
    private function readSources($config): ?array
    {
        $sources = [];

        foreach ($config->source as $path) {
            $data = null;

            if (filter_var($path, FILTER_VALIDATE_URL)) {
                $context = stream_context_create(['http' => ['timeout' => 5]]);
                $data = @file_get_contents($path, false, $context) ?: null;
            } elseif ($this->filesystem->exists($path)) {
                $data = $this->filesystem->readFile($path);
            }

            if ($data === null) {
                $this->logger->error($path.' not found');

                return null;
            }

            $sources[] = $data;
        }

        return $sources;
    }

    /**
     * Generates a global hash representing all source contents combined.
     *
     * @param list<string> $sources The list of source contents.
     * @return string A hash that uniquely represents the current source state.
     */
    private function sourceHash(array $sources): string
    {
        $hashes = [];
        foreach ($sources as $data) {
            $hashes[] = $this->cache->hash($data);
        }

        return $this->cache->hash(implode('', $hashes));
    }

    /**
     * Checks if the cache entry is still valid by comparing source hashes.
     *
     * @param array{source_hash?: string}|null $cacheEntry
     * @return bool True if cache is valid, false otherwise.
     */
    private function isCacheValid(?array $cacheEntry, string $sourceHash): bool
    {
        return $cacheEntry !== null && ($cacheEntry['source_hash'] ?? null) === $sourceHash;
    }

    /**
     * Determines the version string for the output file.
     * Increments version if the current date matches the cached one,
     * or resets to `.1` if a new month has started.
     *
     * @param \Realodix\Hippo\Config\ValueObject\FilterSet $config
     * @param array{version: string}|null $cacheEntry
     * @return string The new version string in 'YY.MM.rev' format.
     */
    private function determineVersion($config, ?array $cacheEntry): string
    {
        $currentDate = date('y.m');
        if (
            // no config is provided, or it doesn't enable versioning
            !isset($config->metadata['enable_version']) || $config->metadata['enable_version'] === false
            || empty($cacheEntry['version']) // no cached data, assume it's the first
        ) {
            return sprintf('%s.%d', $currentDate, 1);
        }

        $parts = explode('.', $cacheEntry['version']);
        $cachedDate = $parts[0].'.'.$parts[1];
        $cachedRevNum = (int) ($parts[2] ?? 0);

        $revNum = ($cachedDate === $currentDate) ? $cachedRevNum + 1 : 1;

        return sprintf('%s.%d', $currentDate, $revNum);
    }
}
