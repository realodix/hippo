<?php

namespace Realodix\Hippo\Fixer;

use Illuminate\Support\Arr;
use Realodix\Hippo\Cache\Cache;
use Realodix\Hippo\Config\Config;
use Realodix\Hippo\Enums\Mode;
use Realodix\Hippo\Finder;
use Realodix\Hippo\Fixer\ValueObject\FixStats;
use Realodix\Hippo\OutputLogger;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

final class Fixer
{
    public function __construct(
        private Processor $processor,
        private Config $config,
        private Finder $finder,
        private Filesystem $filesystem,
        private Cache $cache,
        private FixStats $stats,
        private OutputLogger $logger,
    ) {}

    /**
     * Entry point for file or directory processing.
     *
     * @param \Realodix\Hippo\Enums\Mode $mode Processing mode
     * @param string|null $path File or directory path to process
     * @param string|null $cachePath Optional path to the cache file or directory.
     * @param string|null $configFile Optional path to the configuration file.
     */
    public function handle(Mode $mode, ?string $path, ?string $cachePath, ?string $configFile): void
    {
        $config = $this->config->loadFromFile($configFile, ['cache_dir' => $cachePath]);
        $this->cache->prepareForRun($config, $mode);

        $overrides = $path ? ['paths' => [$path]] : [];
        $fixerConfig = $config->fixer($overrides);

        foreach ($fixerConfig->paths as $path) {
            if (!file_exists($path) || !is_readable($path)) {
                $this->logger->error("Cannot read: $path");
                $this->stats->incrementError();

                continue;
            }

            if (is_file($path)) {
                $this->processFile($path, $this->stats);
            } elseif (is_dir($path)) {
                $finder = $this->finder->create($path, $fixerConfig->ignore);
                foreach ($finder as $file) {
                    $this->processFile($file->getRealPath(), $this->stats);
                }
            }
        }

        $this->cache->repository()->save();
    }

    /**
     * Process a single file, using block-based cache or force mode.
     *
     * @param string $filePath Path to file
     * @param \Realodix\Hippo\Fixer\ValueObject\FixStats $stats Statistics of processed files
     */
    private function processFile(string $filePath, FixStats $stats): void
    {
        $filePath = Path::canonicalize($filePath);
        $rawContent = file($filePath, FILE_IGNORE_NEW_LINES);

        if ($rawContent === false) {
            $this->logger->error("Failed to read file: {$filePath}");
            $stats->incrementError();

            return;
        }

        if (!$this->isFileChanged($filePath, $rawContent)) {
            $this->logger->skipped($filePath);
            $stats->incrementSkipped();

            return;
        }

        $this->write(
            $filePath,
            collect($this->processor->process($rawContent))
                ->flatten()->implode("\n")."\n",
        );

        $this->logger->processed($filePath);
        $stats->incrementProcessed();
    }

    /**
     * Write processed content to a file.
     *
     * @param string $filePath Path to file
     * @param string $content Processed content
     *
     * @throws \Symfony\Component\Filesystem\Exception\IOException
     */
    private function write(string $filePath, string $content): void
    {
        $this->filesystem->dumpFile($filePath, $content);

        $this->cache->repository()->set($filePath, [
            'reference' => $this->cache->hash($content),
        ]);
    }

    /**
     * Checks if a file has changed.
     *
     * @param string $filePath Path to file
     * @param array<string> $content File contents
     */
    private function isFileChanged(string $filePath, array $content): bool
    {
        $cacheEntry = $this->cache->repository()->get($filePath);
        $currentHash = $this->cache->hash(implode("\n", $content)."\n");

        return Arr::get($cacheEntry, 'reference') !== $currentHash;
    }

    public function stats(): FixStats
    {
        return $this->stats;
    }
}
