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

    public function handle(?string $configFile, bool $force = false): void
    {
        $mode = $force ? Mode::Force : Mode::Default;
        $config = $this->config->loadFromFile($configFile);

        $this->cache->prepareForRun($config, $mode, Scope::B);

        foreach ($config->builder()->filters as $filterConfig) {
            $outputPath = $filterConfig->outputPath;

            // Skip compilation if the source has not changed (cache is valid)
            $sourceHash = $this->sourceHash($filterConfig);
            $cacheEntry = $this->cache->repository()->get($outputPath);
            if (!$force && $cacheEntry !== null && ($cacheEntry['source_hash'] ?? null) === $sourceHash) {
                $this->logger->skipped($outputPath);

                continue;
            }

            $version = $this->determineVersion($filterConfig, $cacheEntry);
            $metadata = $this->metadata->create($filterConfig, $version);
            $contentFilter = $this->buildFilterList($filterConfig);
            $filterList = collect($metadata)->merge($contentFilter)
                ->filter()->implode("\n")."\n";

            $this->filesystem->dumpFile($outputPath, $filterList);
            $this->logger->processed($outputPath, null, null);

            // Set the source hash in the cache
            $this->cache->repository()->set($outputPath, [
                'source_hash' => $sourceHash,
                'version' => $version,
            ]);
        }

        // Save the updated cache to disk for the next run
        $this->cache->repository()->save();
    }

    /**
     * Build a filter list from a list of source files.
     *
     * @param \Realodix\Hippo\Config\ValueObject\FilterSet $config
     * @return list<string> The built filter list
     */
    private function buildFilterList($config): array
    {
        $content = '';

        foreach ($config->source as $filter) {
            $content .= $this->filesystem->readFile($filter)."\n";
            $content = $this->stripMetadataAgent($content);
            $content = $this->stripComments($content);
            $content = $this->stripEmptyLines($content);
        }

        return [$content];
    }

    /**
     * Remove adblock agent metadata.
     *
     * Like this:
     * - [Adblock Plus 2.0]
     * - [uBlock Origin]
     * - [AdGuard]
     */
    private function stripMetadataAgent(string $content): string
    {
        return preg_replace('/^\[.*\]$/m', '', $content);
    }

    /**
     * Removes comments (lines that start with !) from lines.
     *
     * Don not remove comments that start with !# (Preprocessor directives).
     * - https://github.com/gorhill/uBlock/wiki/Static-filter-syntax#pre-parsing-directives
     * - https://adguard.com/kb/general/ad-filtering/create-own-filters/#preprocessor-directives
     * - https://regex101.com/r/VSOcD6/1
     */
    private function stripComments(string $content): string
    {
        return preg_replace('/^!(?!#\s?(?:include\s|if|endif|else)).*/m', '', $content);
    }

    /**
     * Remove empty lines.
     */
    private function stripEmptyLines(string $content): string
    {
        return preg_replace('/^\h*\v+/m', '', $content);
    }

    /**
     * Calculate a hash for a given set of source files.
     *
     * The hash is calculated by hashing each source file individually,
     * and then hashing the concatenated hashes of all files.
     *
     * @param \Realodix\Hippo\Config\ValueObject\FilterSet $config
     * @return string The calculated hash
     */
    private function sourceHash($config): string
    {
        $hashes = [];
        foreach ($config->source as $source) {
            $hashes[] = hash_file($this->cache->repository()::HASH_ALGO, $source);
        }

        return hash($this->cache->repository()::HASH_ALGO, implode('', $hashes));
    }

    /**
     * Determine the version string to assign to the current build.
     *
     * This method checks the filter configuration and cache data to decide
     * whether versioning is enabled and, if so, what version number to use.
     *
     * - If `enable_version` is missing or disabled, a default version string
     *   (based on the current date and revision 1) is returned.
     * - If the cache is empty (first run), the same default version is used.
     * - Otherwise, it increments or resets the revision number.
     *
     * @param \Realodix\Hippo\Config\ValueObject\FilterSet $config
     * @param ?array{version?: string} $cacheEntry
     * @return string The computed version in 'YY.MM.rev' format.
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
