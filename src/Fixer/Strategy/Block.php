<?php

namespace Realodix\Hippo\Fixer\Strategy;

use Illuminate\Container\Attributes\Singleton;
use Realodix\Hippo\Cache\Cache;
use Realodix\Hippo\Enums\Mode;
use Realodix\Hippo\Fixer\Processor;
use Realodix\Hippo\Fixer\ValueObject\FixOutput;

#[Singleton]
final class Block
{
    /**
     * The number of lines to process at a time.
     */
    public int $blockSize = 300;

    /**
     * The threshold for deciding to process all blocks.
     *
     * If the number of modified blocks exceeds threshold, a full process
     * will be triggered to rebuild the entire list. Below this threshold,
     * only incremental updates will be performed.
     */
    public int $threshold = 2;

    public function __construct(
        private Processor $processor,
        private Cache $cache,
    ) {}

    /**
     * Process file blocks, handling caching and selective block processing.
     *
     * @param string $filePath Path to the file being processed
     * @param array<string> $content File content
     * @param \Realodix\Hippo\Enums\Mode $mode Processing mode
     * @return \Realodix\Hippo\Fixer\ValueObject\FixOutput
     */
    public function handle(string $filePath, array $content, Mode $mode)
    {
        // For Partial and Force modes, we use the block-level logic.
        $originalBlocks = array_chunk($content, $this->blockSize);
        $totalBlocks = count($originalBlocks);
        $blocksToProcess = $this->getBlocksToProcess($filePath, $originalBlocks, $mode);

        // CASE: PROCESS ALL BLOCKS
        if ($this->shouldProcessAllBlocks($filePath, $totalBlocks, $blocksToProcess, $mode)) {
            return $this->processAllBlocks($content, $mode);
        }

        // CASE: CACHE-AWARE MODE
        if (empty($blocksToProcess)) {
            return new FixOutput;
        }

        return $this->processChangedBlocks($originalBlocks, $totalBlocks, $blocksToProcess);
    }

    /**
     * Decide if all blocks should be processed based on cache and threshold.
     *
     * @param string $filePath Path to file
     * @param int $totalBlocks Total number of blocks
     * @param array<int> $blocksToProcess Indices of changed blocks
     * @param \Realodix\Hippo\Enums\Mode $mode Processing mode
     */
    private function shouldProcessAllBlocks(string $filePath, int $totalBlocks, array $blocksToProcess, Mode $mode): bool
    {
        if ($mode === Mode::Force) {
            return true;
        }

        $cachedEntry = $this->cache->repository()->get($filePath);
        $isNewFile = $cachedEntry === null || !isset($cachedEntry['blocks']);
        $lastBlockChanged = in_array($totalBlocks - 1, $blocksToProcess);

        return $isNewFile
            || count($blocksToProcess) >= $this->threshold
            || $lastBlockChanged;
    }

    /**
     * Process all blocks (full file reprocessing).
     *
     * @param array<string> $content Full file content
     * @param \Realodix\Hippo\Enums\Mode $mode Processing mode
     * @return \Realodix\Hippo\Fixer\ValueObject\FixOutput
     */
    private function processAllBlocks(array $content, Mode $mode)
    {
        $processed = $this->processor->process($content);

        $newBlockHashes = [];
        if ($mode === Mode::Partial) {
            $newBlocks = array_chunk($processed, $this->blockSize);
            $newBlockHashes = $this->cache->repository()->blockHash($newBlocks);
        }

        return new FixOutput($processed, $newBlockHashes);
    }

    /**
     * Process only changed blocks (cache-aware mode).
     *
     * @param array<array<string>> $originalBlocks Original blocks
     * @param int $totalBlocks Total number of blocks
     * @param array<int> $blocksToProcess Indices of blocks to process
     * @return \Realodix\Hippo\Fixer\ValueObject\FixOutput
     */
    private function processChangedBlocks(array $originalBlocks, int $totalBlocks, array $blocksToProcess)
    {
        $blocksToProcessSet = array_flip($blocksToProcess); // O(1) lookup
        $processedCount = 0;
        $newContentParts = [];
        $processedBlocks = [];

        foreach ($originalBlocks as $i => $lines) {
            if (isset($blocksToProcessSet[$i])) {
                $linesToWrite = $this->processor->process($lines);
                $processedCount++;
            } else {
                $linesToWrite = $lines;
            }
            $newContentParts[] = $linesToWrite; // Keep as array, implode once later
            $processedBlocks[$i] = $linesToWrite;
        }

        $newBlockHashes = $this->cache->repository()->blockHash($processedBlocks);

        return new FixOutput($newContentParts, $newBlockHashes, $processedCount, $totalBlocks);
    }

    /**
     * Compare current block hashes with cached hashes to determine blocks to process.
     *
     * @param string $filePath Path to file
     * @param array<array<string>> $originalBlocks File split into blocks
     * @param \Realodix\Hippo\Enums\Mode $mode Processing mode
     * @return array<int>
     */
    private function getBlocksToProcess(string $filePath, array $originalBlocks, Mode $mode): array
    {
        if ($mode === Mode::Force) {
            return array_keys($originalBlocks);
        }

        $cachedEntry = $this->cache->repository()->get($filePath);
        $currentBlockHashes = $this->cache->repository()->blockHash($originalBlocks);

        if ($cachedEntry === null || !isset($cachedEntry['blocks']) || empty($cachedEntry['blocks'])) {
            return array_keys($currentBlockHashes);
        }

        $cachedBlockHashes = $cachedEntry['blocks'];
        $blocksToProcess = [];

        foreach ($currentBlockHashes as $index => $currentHash) {
            if (!isset($cachedBlockHashes[$index]) || $cachedBlockHashes[$index] !== $currentHash) {
                $blocksToProcess[] = $index;
            }
        }

        return $blocksToProcess;
    }
}
