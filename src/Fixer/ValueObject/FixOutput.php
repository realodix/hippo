<?php

namespace Realodix\Hippo\Fixer\ValueObject;

use Realodix\Hippo\Enums\Status;

/**
 * The output of a fixer operation.
 */
final readonly class FixOutput
{
    /**
     * @param array<string|array<string>> $content Processed content
     * @param array<string> $blockHash Block hashes
     */
    public function __construct(
        public array $content = [],
        public array $blockHash = [],
        public ?int $processedBlocks = null,
        public ?int $totalBlocks = null,
    ) {}

    public function status(): Status
    {
        return !empty($this->content) ? Status::Success : Status::Skipped;
    }
}
