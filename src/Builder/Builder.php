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
            $sourceHash = $this->sourceHash(array_merge(
                $sources,
                Arr::flatten($filterConfig->metadata()),
            ));

            // Step 3: Skip processing if cache is still valid
            if (!$force && $this->isCacheValid($outputPath, $sourceHash)) {
                $this->logger->skipped($outputPath);

                continue;
            }

            // Step 4: Build and write output file
            $metadata = $this->metadata->build($filterConfig);
            $this->buildAndWrite($outputPath, $sources, $metadata, $sourceHash);
        }

        // Save all updated cache entries to disk
        $this->cache->repository()->save();
    }

    /**
     * Builds the final output file from sources and metadata, and updates cache.
     *
     * @param string $outputPath The path to the output file.
     * @param list<string> $sources The list of raw source contents.
     * @param array<int, string> $metadata The built metadata array.
     * @param string $sourceHash The hash representing the current source state.
     *
     * @throws \Symfony\Component\Filesystem\Exception\IOException
     */
    private function buildAndWrite(string $outputPath, array $sources, array $metadata, string $sourceHash): void
    {
        $content = collect($metadata)->merge($sources)
            ->implode("\n")."\n";

        $this->filesystem->dumpFile($outputPath, $content);
        $this->logger->processed($outputPath);

        $this->cache->repository()->set($outputPath, [
            'source_hash' => $sourceHash,
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
     */
    private function isCacheValid(string $outputPath, string $sourceHash): bool
    {
        $cacheEntry = $this->cache->repository()->get($outputPath);

        return Arr::get($cacheEntry, 'source_hash') === $sourceHash;
    }

    /**
     * Generates a global hash representing all source contents combined.
     *
     * @param list<string> $sources The list of source contents.
     * @return string A hash that uniquely represents the current source state.
     */
    private function sourceHash(array $sources): string
    {
        return $this->cache->hash(implode('', $sources));
    }
}
