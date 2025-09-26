<?php

namespace Realodix\Hippo\Config;

/**
 * A filter list configuration
 */
final readonly class FilterSet
{
    /**
     * @param array{
     *   header?: string,
     *   title?: string,
     *   description?: string,
     *   expires?: string,
     *   homepage?: string,
     *   enable_version?: bool
     * } $metadata
     * @param list<string> $source The source files for the filter list
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
