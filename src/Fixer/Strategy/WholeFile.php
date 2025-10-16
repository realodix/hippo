<?php

namespace Realodix\Hippo\Fixer\Strategy;

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
        if (!$this->cache->isFileChanged($filePath)) {
            return new FixOutput;
        }

        return new FixOutput($this->processor->process($content));
    }
}
