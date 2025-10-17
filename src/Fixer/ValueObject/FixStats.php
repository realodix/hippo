<?php

namespace Realodix\Hippo\Fixer\ValueObject;

final class FixStats
{
    private int $processed = 0;

    private int $skipped = 0;

    private int $error = 0;

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

    public function allSkipped(): bool
    {
        return $this->total() === $this->skipped;
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
            $this->error,
        );
    }
}
