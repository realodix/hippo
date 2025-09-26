<?php

namespace Realodix\Hippo\Processor;

use Realodix\Hippo\Cache\Cache;
use Realodix\Hippo\Config\Config;
use Realodix\Hippo\Enums\Mode;
use Realodix\Hippo\Enums\Status;
use Realodix\Hippo\Finder;
use Realodix\Hippo\OutputLogger;
use Realodix\Hippo\Processor\Strategy\Block;
use Realodix\Hippo\Processor\Strategy\WholeFile;
use Realodix\Hippo\Processor\ValueObject\ProcessingStats;
use Symfony\Component\Filesystem\Filesystem;

final class FileHandler
{
    public function __construct(
        private Config $config,
        private Finder $finder,
        private Filesystem $filesystem,
        private Block $block,
        private WholeFile $file,
        private Cache $cache,
        private OutputLogger $logger,
    ) {}

    /**
     * Entry point for file or directory processing.
     *
     * @param \Realodix\Hippo\Enums\Mode $mode Processing mode
     * @param string|null $path File or directory path to process
     * @param string|null $cachePath Path to cache file
     * @param string|null $configFile Path to config file
     */
    public function handle(Mode $mode, ?string $path, ?string $cachePath, ?string $configFile): ProcessingStats
    {
        $stats = ProcessingStats::empty();
        $config = $this->config->loadFromFile($configFile, ['cache_dir' => $cachePath]);
        $fixerConfig = $config->fixer(['path' => $path]);

        $this->cache->prepareForRun($config, $mode);

        $path = $fixerConfig->path;

        if (!file_exists($path) || !is_readable($path)) {
            $this->logger->error("Cannot read: $path");

            return $stats;
        }

        if (is_file($path)) {
            $processed = $this->processFile($path, $mode);

            $stats = new ProcessingStats(
                total: 1,
                processed: (int) $processed,
                skipped: (int) !$processed,
            );
        } elseif (is_dir($path)) {
            $stats = $this->processDirectory($path, $fixerConfig->ignore, $mode);
        }

        $this->cache->repository()->save();

        return $stats;
    }

    /**
     * Process a single file, using block-based cache or force mode.
     *
     * @param string $filePath Path to file
     * @param \Realodix\Hippo\Enums\Mode $mode Processing mode
     * @return bool True if processed, false if skipped or failed
     */
    private function processFile(string $filePath, Mode $mode): bool
    {
        $content = file($filePath, FILE_IGNORE_NEW_LINES);
        if ($content === false) {
            $this->logger->error("Failed to read file: {$filePath}");

            return false;
        }

        $result = $this->processContent($filePath, $content, $mode);
        if ($result === null) {
            return false;
        }

        $this->write($filePath, $result);
        $this->logger->processed($filePath, $result->processedBlocks, $result->totalBlocks);

        return true;
    }

    /**
     * Process all files in a directory using Finder.
     *
     * @param string $directory Directory path
     * @param array<string> $ignore Paths to ignore
     * @param \Realodix\Hippo\Enums\Mode $mode Processing mode
     */
    private function processDirectory(string $directory, array $ignore, Mode $mode): ProcessingStats
    {
        $finder = $this->finder->create($directory, $ignore);

        $totalCount = $finder->count();
        $processedCount = 0;

        foreach ($finder as $file) {
            if ($this->processFile($file->getRealPath(), $mode)) {
                $processedCount++;
            }
        }

        return new ProcessingStats(
            total: $totalCount,
            processed: $processedCount,
            skipped: $totalCount - $processedCount,
        );
    }

    /**
     * Process content of a single file, using the appropriate strategy
     * (whole file or block-based).
     *
     * @param string $filePath Path to file
     * @param array<string> $content File content
     * @param \Realodix\Hippo\Enums\Mode $mode Processing mode
     * @return \Realodix\Hippo\Processor\ValueObject\ProcessingResult|null
     *   Process result if successful, null if skipped or failed
     */
    private function processContent(string $filePath, array $content, Mode $mode)
    {
        $result = $mode === Mode::Default
            ? $this->file->handle($filePath, $content)
            : $this->block->handle($filePath, $content, $mode);

        if ($result->status === Status::Skipped) {
            $this->logger->skipped($filePath);

            return null;
        }

        if ($result->status === Status::Failed) {
            $this->logger->error("Failed to process: $filePath");

            return null;
        }

        return $result;
    }

    /**
     * Write processed content to a file.
     *
     * @param string $filePath Path to file
     * @param \Realodix\Hippo\Processor\ValueObject\ProcessingResult $result Process result
     */
    private function write(string $filePath, $result): void
    {
        $this->filesystem->dumpFile(
            $filePath,
            collect($result->content)->flatten()->implode("\n")."\n",
        );
        $this->cache->repository()->update($filePath, $result->blockHash);
    }
}
