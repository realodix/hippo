<?php

namespace Realodix\Hippo\Builder;

use Illuminate\Support\Arr;
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
     * @param bool $force If true, forces rebuild even when cache is valid.
     * @param string|null $configFile Optional path to the configuration file.
     */
    public function handle(bool $force, ?string $configFile): void
    {
        $mode = $force ? Mode::Force : Mode::Default;
        $config = $this->config->loadFromFile($configFile);

        // Prepare cache repository for this run
        $this->cache->prepareForRun($config, $mode, Scope::B);

        foreach ($config->builder()->filters as $filterConfig) {
            $outputPath = $filterConfig->outputPath;

            // Step 1: Read all source files or URLs
            $rawRources = $this->readSources($filterConfig);
            if ($rawRources === null) {
                $this->logger->skipped($outputPath);

                continue;
            }

            $sources = Cleaner::clean($rawRources);

            // Step 2: Generate a single hash from all source contents
            $sourceHash = $this->sourceHash($sources, $filterConfig);

            // Step 3: Skip processing if cache is still valid
            $cacheEntry = $this->cache->repository()->get($outputPath);
            if (!$force && $this->isCacheValid($cacheEntry, $sourceHash)) {
                $this->logger->skipped($outputPath);

                continue;
            }

            // Step 4: Build and write output file
            $this->buildAndWrite($sources, $filterConfig, $sourceHash, Arr::get($cacheEntry, 'version'));
        }

        // Save all updated cache entries to disk
        $this->cache->repository()->save();
    }

    /**
     * Builds the final output file from sources and metadata, and updates cache.
     *
     * @param list<string> $sources The list of raw source contents.
     * @param \Realodix\Hippo\Config\ValueObject\FilterSet $filterConfig The configuration for the current filter set.
     * @param string $sourceHash The hash representing the current source state.
     * @param string|null $currentVersion The current version string.
     *
     * @throws \Symfony\Component\Filesystem\Exception\IOException
     */
    private function buildAndWrite(array $sources, $filterConfig, string $sourceHash, ?string $currentVersion): void
    {
        $outputPath = $filterConfig->outputPath;
        $version = $this->determineVersion($filterConfig, $currentVersion);
        $metadata = $this->metadata->create($filterConfig, $version);

        $content = collect($metadata)->merge($sources)
            ->implode("\n")."\n";

        $this->filesystem->dumpFile($outputPath, $content);
        $this->logger->processed($outputPath);

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
     *
     * @throws \Symfony\Component\Filesystem\Exception\IOException
     */
    private function readSources($config): ?array
    {
        $sources = [];

        foreach ($config->source as $path) {
            $data = null;

            if (filter_var($path, FILTER_VALIDATE_URL)) {
                $context = stream_context_create(['http' => ['timeout' => 5]]);
                $data = @file_get_contents($path, false, $context) ?: null;
            } elseif (file_exists($path)) {
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
     * Checks if the cache entry is still valid by comparing source hashes.
     *
     * @param array<string, mixed>|null $cacheEntry
     * @return bool True if cache is valid, false otherwise.
     */
    private function isCacheValid(?array $cacheEntry, string $sourceHash): bool
    {
        return Arr::get($cacheEntry, 'source_hash') === $sourceHash;
    }

    /**
     * Generates a global hash representing all source contents combined.
     *
     * @param list<string> $sources The list of source contents.
     * @param \Realodix\Hippo\Config\ValueObject\FilterSet $filterConfig
     * @return string A hash that uniquely represents the current source state.
     */
    private function sourceHash(array $sources, $filterConfig): string
    {
        $data = array_merge($sources, $filterConfig->metadata());

        return $this->cache->hash(implode('', $data));
    }

    /**
     * Determines the version string for the output file.
     * Increments version if the current date matches the cached one,
     * or resets to `.1` if a new month has started.
     *
     * @param \Realodix\Hippo\Config\ValueObject\FilterSet $config
     * @param string|null $currentVersion The current version string.
     * @return string The new version string in 'YY.MM.rev' format.
     */
    private function determineVersion($config, ?string $currentVersion): string
    {
        $currentDate = date('y.m');
        if (
            // it doesn't enable versioning
            $config->metadata()['enable_version'] === false
            || empty($currentVersion) // no cached data, assume it's the first
        ) {
            return sprintf('%s.%d', $currentDate, 1);
        }

        $parts = explode('.', $currentVersion);
        $cachedDate = $parts[0].'.'.$parts[1];
        $cachedRevNum = (int) ($parts[2] ?? 0);

        $revNum = ($cachedDate === $currentDate) ? $cachedRevNum + 1 : 1;

        return sprintf('%s.%d', $currentDate, $revNum);
    }
}
