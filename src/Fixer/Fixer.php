<?php

namespace Realodix\Hippo\Fixer;

use Realodix\Hippo\Cache\Cache;
use Realodix\Hippo\Config\Config;
use Realodix\Hippo\Enums\Mode;
use Realodix\Hippo\Enums\Status;
use Realodix\Hippo\Finder;
use Realodix\Hippo\Fixer\Strategy\Block;
use Realodix\Hippo\Fixer\Strategy\WholeFile;
use Realodix\Hippo\Fixer\ValueObject\FixStats;
use Realodix\Hippo\OutputLogger;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

final class Fixer
{
    public function __construct(
        private Config $config,
        private Finder $finder,
        private Filesystem $filesystem,
        private Block $block,
        private WholeFile $file,
        private Cache $cache,
        private FixStats $stats,
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
                $this->processFile($path, $mode, $this->stats);
            } elseif (is_dir($path)) {
                $finder = $this->finder->create($path, $fixerConfig->ignore);
                foreach ($finder as $file) {
                    $this->processFile($file->getRealPath(), $mode, $this->stats);
                }
            }
        }

        $this->cache->repository()->save();
    }

    /**
     * Process a single file, using block-based cache or force mode.
     *
     * @param string $filePath Path to file
     * @param \Realodix\Hippo\Enums\Mode $mode Processing mode
     * @param \Realodix\Hippo\Fixer\ValueObject\FixStats $stats Statistics of processed files
     */
    private function processFile(string $filePath, Mode $mode, FixStats $stats): void
    {
        $filePath = Path::canonicalize($filePath);
        $content = file($filePath, FILE_IGNORE_NEW_LINES);

        if ($content === false) {
            $this->logger->error("Failed to read file: {$filePath}");
            $stats->incrementError();

            return;
        }

        $output = $mode === Mode::Default
            ? $this->file->handle($filePath, $content)
            : $this->block->handle($filePath, $content, $mode);

        if ($output->status() === Status::Skipped) {
            $this->logger->skipped($filePath);
            $stats->incrementSkipped();

            return;
        }

        $this->write($filePath, $output);
        $this->logger->processed($filePath, $output->processedBlocks, $output->totalBlocks);
        $stats->incrementProcessed();
    }

    /**
     * Write processed content to a file.
     *
     * @param string $filePath Path to file
     * @param \Realodix\Hippo\Fixer\ValueObject\FixOutput $output Process result
     */
    private function write(string $filePath, $output): void
    {
        // Write processed content
        $this->filesystem->dumpFile(
            $filePath,
            collect($output->content)->flatten()->implode("\n")."\n",
        );

        // Save file hash and block hashes to cache
        $this->cache->repository()->set($filePath, [
            'file_hash' => hash_file($this->cache->repository()::HASH_ALGO, $filePath),
            'blocks' => $output->blockHash,
        ]);
    }

    public function stats(): FixStats
    {
        return $this->stats;
    }
}
