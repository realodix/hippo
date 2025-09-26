<?php

namespace Realodix\Hippo\Compiler\ValueObject;

final readonly class FilterConfig
{
    /**
     * @param string $outputFile The output file name for the filter list.
     * @param list<string> $metadata The metadata for the filter list.
     * @param list<string> $source The source files of the filter list.
     *
     * @throws \InvalidArgumentException If the "source" key is missing or empty.
     */
    public function __construct(
        public string $outputFile,
        public array $metadata,
        public array $source,
    ) {
        if (empty($this->source)) {
            throw new \InvalidArgumentException(
                sprintf('The "source" key is missing or empty for the filter list "%s".', $this->outputFile),
            );
        }
    }
}
