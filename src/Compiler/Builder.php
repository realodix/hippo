<?php

namespace Realodix\Hippo\Compiler;

use Realodix\Hippo\Cache\Cache;
use Realodix\Hippo\Compiler\ValueObject\FilterConfig;
use Realodix\Hippo\Compiler\ValueObject\ProjectConfig;
use Realodix\Hippo\Enums\Mode;
use Realodix\Hippo\Enums\Scope;
use Realodix\Hippo\OutputLogger;
use Symfony\Component\Filesystem\Filesystem;

final class Builder
{
    private ProjectConfig $projectConfig;

    public function __construct(
        private Config $config,
        private Metadata $metadata,
        private Filesystem $filesystem,
        private OutputLogger $logger,
        private Cache $cache,
    ) {}

    public function handle(string $configFile, bool $force = false): void
    {
        $this->projectConfig = $this->config->createFromFile($configFile);

        $outputFiles = array_map(
            fn(FilterConfig $config) => $this->outputPath($config),
            $this->projectConfig->filters,
        );

        $this->cache->prepareForRun(
            $this->projectConfig->cacheDir,
            $force ? Mode::Force : Mode::Default,
            Scope::C,
            $outputFiles,
        );

        foreach ($this->projectConfig->filters as $filterConfig) {
            $outputPath = $this->outputPath($filterConfig);

            // Skip compilation if the source has not changed (cache is valid)
            $sourceHash = $this->getSourceHash($filterConfig->source);
            $cachedData = $this->cache->repository()->get($outputPath);
            if (!$force && $cachedData !== null && ($cachedData['source_hash'] ?? null) === $sourceHash) {
                $this->logger->skipped($outputPath);

                continue;
            }

            $rev = ($cachedData['rev'] ?? 0) + 1;
            $metadata = $this->metadata->create($filterConfig->metadata, $rev);
            $contentFilter = $this->createFilterList($filterConfig->source);

            $filterList = collect($metadata)
                ->merge($contentFilter)
                ->filter()
                ->implode("\n")."\n";

            $this->filesystem->dumpFile($outputPath, $filterList);
            $this->logger->processed($outputPath, null, null);

            // Set the source hash in the cache
            $this->cache->repository()->set($outputPath, [
                'source_hash' => $sourceHash,
                'rev' => $rev,
            ]);
        }

        // Save the updated cache to disk for the next run
        $this->cache->repository()->save();
    }

    private function outputPath(FilterConfig $config): string
    {
        return $this->projectConfig->outputDir.'/'.$config->outputFile;
    }

    /**
     * Calculate a hash for a given set of source files.
     *
     * The hash is calculated by hashing each source file individually,
     * and then hashing the concatenated hashes of all files.
     *
     * @param array<string> $sources List of source files to hash
     * @return string The calculated hash
     */
    private function getSourceHash(array $sources): string
    {
        $hashes = [];
        foreach ($sources as $source) {
            $content = $this->filesystem->readFile($source);
            $hashes[] = hash('xxh3', $content);
        }

        return hash('xxh3', implode('', $hashes));
    }

    /**
     * Compile a list of filters into a single filter list.
     *
     * @param list<string> $data List of source files to compile
     * @return list<string> The compiled filter list
     */
    private function createFilterList(array $data): array
    {
        $content = '';

        foreach ($data as $filter) {
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
}
