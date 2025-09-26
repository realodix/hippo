<?php

namespace Realodix\Hippo\Fixer;

use Realodix\Hippo\App;
use Realodix\Hippo\Cache\Cache;
use Realodix\Hippo\Config\Config;
use Realodix\Hippo\Console\OutputLogger;
use Realodix\Hippo\Enums\Mode;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

final class Fixer
{
    public function __construct(
        private Processor $processor,
        private Config $config,
        private Filesystem $fs,
        private Cache $cache,
        private OutputLogger $logger,
    ) {}

    /**
     * Entry point for file or directory processing.
     *
     * @param \Realodix\Hippo\Enums\Mode $mode Processing mode
     * @param string|null $path File or directory path to process
     * @param string|null $cachePath Optional path to the cache file or directory
     * @param string|null $configFile Optional path to the configuration file
     */
    public function handle(Mode $mode, ?string $path, ?string $cachePath, ?string $configFile): void
    {
        $config = $this->config->loadFromFile($configFile, ['cache_dir' => $cachePath]);
        $fixerConfig = $config->fixer($path ? ['paths' => [$path]] : []);

        $this->cache->prepareForRun($fixerConfig->paths, $config->cacheDir, $mode);

        foreach ($fixerConfig->paths as $path) {
            if (!is_readable($path)) {
                $this->logger->error("Cannot read: {$path}");

                continue;
            }

            $this->processFile($path);
        }

        $this->cache->repository()->save();
    }

    /**
     * Process a single file, using block-based cache or force mode.
     *
     * @param string $filePath Path to file
     */
    private function processFile(string $filePath): void
    {
        $filePath = Path::canonicalize($filePath);
        $rawContent = file($filePath, FILE_IGNORE_NEW_LINES);

        if ($rawContent === false) {
            $this->logger->error("Failed to read file: {$filePath}");

            return;
        }

        $rawContentHash = $this->hash(implode("\n", $rawContent)."\n");
        if (
            $this->cache->isValid($filePath, $rawContentHash)
            || trim(implode($rawContent)) === '' // empty file
        ) {
            $this->logger->skipped($filePath);

            return;
        }

        $this->logger->processing($filePath);

        $this->write($filePath, $this->processor->process($rawContent));

        $this->logger->processed($filePath);
    }

    /**
     * Write processed content to a file.
     *
     * @param string $filePath Path to file
     * @param array<int, string> $content Processed content
     *
     * @throws \Symfony\Component\Filesystem\Exception\IOException
     */
    private function write(string $filePath, array $content): void
    {
        $content = implode("\n", $content)."\n";
        $this->fs->dumpFile($filePath, $content);

        $this->cache->set($filePath, $this->hash($content));
    }

    private function hash(string $data): string
    {
        // increases the suffix number when the Fixer changes
        // to invalidate the cache
        return hash('xxh3', $data.'1');
    }

    /**
     * @return \Realodix\Hippo\Console\Statistics
     */
    public function stats()
    {
        return $this->logger->stats();
    }
}
