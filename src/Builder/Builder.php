<?php

namespace Realodix\Hippo\Builder;

use Illuminate\Support\Arr;
use Realodix\Hippo\Cache\Cache;
use Realodix\Hippo\Config\Config;
use Realodix\Hippo\Console\OutputLogger;
use Realodix\Hippo\Enums\Mode;
use Realodix\Hippo\Enums\Scope;
use Symfony\Component\Filesystem\Filesystem;

final class Builder
{
    public function __construct(
        private Config $config,
        private Metadata $metadata,
        private Filesystem $fs,
        private Cache $cache,
        private OutputLogger $logger,
    ) {}

    /**
     * Main entry point for building filter lists.
     *
     * @param bool $force If true, forces rebuild even when cache is valid
     * @param string|null $configFile Optional path to the configuration file
     */
    public function handle(bool $force, ?string $configFile): void
    {
        $mode = $force ? Mode::Force : Mode::Default;
        $config = $this->config->loadFromFile($configFile);
        $filterSets = $config->builder()->filterSet;

        // Prepare cache repository for this run
        $this->cache->prepareForRun(
            // builder.filter_list.filename
            array_map(fn($filterSet) => $filterSet->outputPath, $filterSets),
            $config->cacheDir,
            $mode,
            Scope::B,
        );

        foreach ($filterSets as $filterSet) {
            $outputPath = $filterSet->outputPath;

            // Step 1: Read all source files or URLs
            $rawContent = $this->readSources($filterSet->source);
            if ($rawContent === null) {
                $this->logger->skipped($outputPath);

                continue;
            }

            $content = Cleaner::clean($rawContent, $filterSet->unique);

            // Step 2: Generate a single hash from all source contents
            $sourceHash = $this->sourceHash($content, Arr::flatten($filterSet->metadata()));

            // Step 3: Skip processing if cache is still valid
            if (!$force && $this->cache->isValid($outputPath, $sourceHash)) {
                $this->logger->skipped($outputPath);

                continue;
            }

            // Step 4: Build and write output file
            $this->write(
                $outputPath,
                array_merge($this->metadata->build($filterSet->metadata()), $content),
                $sourceHash,
            );
        }

        // Save all updated cache entries to disk
        $this->cache->repository()->save();
    }

    /**
     * Writes the final output file from sources and metadata, and updates cache.
     *
     * @param string $outputPath The path to the output file.
     * @param array<int, string> $content Source contents.
     * @param string $sourceHash The hash representing the current source state.
     *
     * @throws \Symfony\Component\Filesystem\Exception\IOException
     */
    private function write(string $outputPath, array $content, string $sourceHash): void
    {
        $this->fs->dumpFile($outputPath, implode("\n", $content)."\n");
        $this->logger->processed($outputPath);

        $this->cache->set($outputPath, $sourceHash);
    }

    /**
     * Reads all source files or URLs defined in the configuration.
     *
     * @param array<int, string> $paths
     * @return array<int, string>|null Source contents, or null if a read fails.
     */
    private function readSources($paths): ?array
    {
        $text = [];

        foreach ($paths as $path) {
            $data = null;

            if (filter_var($path, FILTER_VALIDATE_URL)) {
                $context = stream_context_create(['http' => ['timeout' => 5]]);
                $data = @file($path, 0, $context) ?: null;
            } elseif (file_exists($path)) {
                $data = file($path);
            }

            if ($data === null) {
                $this->logger->error($path.' not found');

                return null;
            }

            $text[] = $data;
        }

        return Arr::flatten($text);
    }

    /**
     * Generates a global hash representing all source contents combined.
     *
     * @param array<int, string> $sources Source contents.
     * @return string A hash that uniquely represents the current source state.
     */
    private function sourceHash(array ...$sources): string
    {
        return hash('xxh3', implode('', array_merge(...$sources)));
    }

    /**
     * @return \Realodix\Hippo\Console\Statistics
     */
    public function stats()
    {
        return $this->logger->stats();
    }
}
