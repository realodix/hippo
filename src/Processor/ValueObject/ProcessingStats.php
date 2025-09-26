<?php

namespace Realodix\Hippo\Processor\ValueObject;

final class ProcessingStats
{
    public function __construct(
        public int $total = 0,
        public int $processed = 0,
        public int $skipped = 0,
    ) {}

    public static function empty(): self
    {
        return new self;
    }

    public function add(self|int $total, int $processed = 0, int $skipped = 0): self
    {
        if ($total instanceof self) {
            $this->total += $total->total;
            $this->processed += $total->processed;
            $this->skipped += $total->skipped;
        } else {
            $this->total += $total;
            $this->processed += $processed;
            $this->skipped += $skipped;
        }

        return $this;
    }
}
