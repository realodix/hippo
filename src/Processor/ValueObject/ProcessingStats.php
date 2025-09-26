<?php

namespace Realodix\Hippo\Processor\ValueObject;

final readonly class ProcessingStats
{
    public function __construct(
        public ?int $total,
        public ?int $processed,
        public ?int $skipped,
    ) {}

    public static function empty(): self
    {
        return new self(0, 0, 0);
    }
}
