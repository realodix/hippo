<?php

namespace Realodix\Hippo\Processor\Strategy;

use Realodix\Hippo\Cache\Cache;
use Realodix\Hippo\Processor\FilterProcessor;
use Realodix\Hippo\Processor\ValueObject\ProcessingResult;

final class WholeFile
{
    public function __construct(
        private FilterProcessor $processor,
        private Cache $cache,
    ) {}

    /**
     * Process all blocks (full file reprocessing).
     *
     * @param string $filePath Path to file
     * @param array<string> $content Full file content
     * @return ProcessingResult
     */
    public function handle(string $filePath, array $content): object
    {
        if (!$this->cache->isFileChanged($filePath)) {
            return new ProcessingResult;
        }

        return new ProcessingResult($this->processor->process($content));
    }
}
