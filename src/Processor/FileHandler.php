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
use Symfony\Component\Filesystem\Path;

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
        $stats = new ProcessingStats;

        $config = $this->config->loadFromFile($configFile, ['cache_dir' => $cachePath]);
        $this->cache->prepareForRun($config, $mode);

        $overrides = $path ? ['paths' => [$path]] : [];
        $fixerConfig = $config->fixer($overrides);

        foreach ($fixerConfig->paths as $path) {
            if (!file_exists($path) || !is_readable($path)) {
                $this->logger->error("Cannot read: $path");
                $stats->incrementError();

                continue;
            }

            if (is_file($path)) {
                $this->processFile($path, $mode, $stats);
            } elseif (is_dir($path)) {
                $this->processDirectory($path, $fixerConfig->ignore, $mode, $stats);
            }
        }

        $this->cache->repository()->save();

        return $stats;
    }

    /**
     * Process a single file, using block-based cache or force mode.
     *
     * @param string $filePath Path to file
     * @param \Realodix\Hippo\Enums\Mode $mode Processing mode
     * @param \Realodix\Hippo\Processor\ValueObject\ProcessingStats $stats Statistics of processed files
     */
    private function processFile(string $filePath, Mode $mode, ProcessingStats $stats): void
    {
        $filePath = Path::canonicalize($filePath);
        $content = file($filePath, FILE_IGNORE_NEW_LINES);

        if ($content === false) {
            $this->logger->error("Failed to read file: {$filePath}");
            $stats->incrementError();

            return;
        }

        $result = $this->processContent($filePath, $content, $mode);

        if ($result === null) {
            $stats->incrementSkipped();

            return;
        }

        $this->write($filePath, $result);
        $this->logger->processed($filePath, $result->processedBlocks, $result->totalBlocks);
        $stats->incrementProcessed();
    }

    /**
     * Process all files in a directory using Finder.
     *
     * @param string $directory Directory path
     * @param array<string> $ignore Paths to ignore
     * @param \Realodix\Hippo\Enums\Mode $mode Processing mode
     * @param \Realodix\Hippo\Processor\ValueObject\ProcessingStats $stats Statistics of processed files
     */
    private function processDirectory(string $directory, array $ignore, Mode $mode, ProcessingStats $stats): void
    {
        $finder = $this->finder->create($directory, $ignore);

        foreach ($finder as $file) {
            $this->processFile($file->getRealPath(), $mode, $stats);
        }
    }

    /**
     * Process content of a single file, using the appropriate strategy
     * (whole file or block-based).
     *
     * @param string $filePath Path to file
     * @param array<string> $content File content
     * @param \Realodix\Hippo\Enums\Mode $mode Processing mode
     * @return \Realodix\Hippo\Processor\ValueObject\ProcessingResult|null Process result if successful,
     *                                                                     null if skipped or failed
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
        // Write processed content
        $this->filesystem->dumpFile(
            $filePath,
            collect($result->content)->flatten()->implode("\n")."\n",
        );

        // Save file hash and block hashes to cache
        $this->cache->repository()->set($filePath, [
            'file_hash' => hash_file($this->cache->repository()::HASH_ALGO, $filePath),
            'blocks' => $result->blockHash,
        ]);
    }
}
