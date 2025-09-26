<?php

namespace Realodix\Hippo\Processor\ValueObject;

use Realodix\Hippo\Enums\Status;

final readonly class ProcessingResult
{
    public Status $status;

    /**
     * @param array<string|array<string>> $content Processed content
     * @param array<string> $blockHash Block hashes
     */
    public function __construct(
        public array $content = [],
        public array $blockHash = [],
        public ?int $processedBlocks = null,
        public ?int $totalBlocks = null,
        ?Status $status = null,
    ) {
        $this->status = $status ?? self::resolveStatus($content);
    }

    /**
     * @param array<string|array<string>> $content Processed content
     */
    private static function resolveStatus(array $content): Status
    {
        return !empty($content) ? Status::Success : Status::Skipped;
    }
}
