<?php

namespace Realodix\Hippo\Config;

final readonly class FilterSet
{
    /**
     * @param list<string> $metadata
     * @param list<string> $source
     */
    public function __construct(
        public string $outputFile,
        public string $outputPath,
        public array $metadata = [],
        public array $source = [],
    ) {
        if (empty($this->source)) {
            throw new \InvalidArgumentException(
                sprintf('The "source" key is missing or empty for the filter list "%s".', $this->outputFile),
            );
        }
    }
}
