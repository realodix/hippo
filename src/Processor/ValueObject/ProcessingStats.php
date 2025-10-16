<?php

namespace Realodix\Hippo\Processor\ValueObject;

final class ProcessingStats
{
    public function __construct(
        public int $total = 0,
        public int $processed = 0,
        public int $skipped = 0,
        public int $error = 0,
    ) {}

    public function incrementProcessed(): void
    {
        $this->processed++;
    }

    public function incrementSkipped(): void
    {
        $this->skipped++;
    }

    public function incrementError(): void
    {
        $this->error++;
    }

    public function total(): int
    {
        return $this->processed + $this->skipped + $this->error;
    }

    public function __toString(): string
    {
        return sprintf(
            'Total: %d, Processed: %d, Skipped: %d, Error: %d',
            $this->total(),
            $this->processed,
            $this->skipped,
            $this->error
        );
    }
}
