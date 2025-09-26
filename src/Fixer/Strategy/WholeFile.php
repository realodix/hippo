<?php

namespace Realodix\Hippo\Fixer\Strategy;

use Illuminate\Support\Arr;
use Realodix\Hippo\Cache\Cache;
use Realodix\Hippo\Fixer\Processor;
use Realodix\Hippo\Fixer\ValueObject\FixOutput;

final class WholeFile
{
    public function __construct(
        private Processor $processor,
        private Cache $cache,
    ) {}

    /**
     * Process all blocks (full file reprocessing).
     *
     * @param string $filePath Path to file
     * @param array<string> $content Full file content
     * @return \Realodix\Hippo\Fixer\ValueObject\FixOutput
     */
    public function handle(string $filePath, array $content): object
    {
        if (!$this->isFileChanged($filePath)) {
            return new FixOutput;
        }

        return new FixOutput($this->processor->process($content));
    }

    /**
     * Checks if the file has changed by comparing its hash with the cached hash.
     */
    public function isFileChanged(string $filePath): bool
    {
        // If the file doesn't exist on disk, we can't compare it, so treat as changed.
        if (!file_exists($filePath)) {
            return true;
        }

        $cacheEntry = $this->cache->repository()->get($filePath);
        $currentHash = $this->cache->hashFile($filePath);

        // Compares the stored hash with the current file hash. If the 'file_hash' key
        // doesn't exist, it will evaluates to true (meaning it has changed).
        return Arr::get($cacheEntry, 'file_hash') !== $currentHash;
    }
}
