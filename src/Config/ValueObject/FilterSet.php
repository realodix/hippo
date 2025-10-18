<?php

namespace Realodix\Hippo\Config\ValueObject;

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
        public array $source = [],
        private array $metadata = [],
    ) {
        if (empty($this->source)) {
            throw new \InvalidArgumentException(
                sprintf('The "source" key is missing or empty for the filter list "%s".', $this->outputFile),
            );
        }
    }

    /**
     * Get the metadata configuration
     *
     * @return array{
     *   header?: string,
     *   title?: string,
     *   description?: string,
     *   expires?: string,
     *   homepage?: string,
     *   enable_version: bool
     * } $metadata
     */
    public function metadata(): array
    {
        $metadata = $this->metadata;

        if (empty($metadata['enable_version'])) {
            $metadata['enable_version'] = false;
        }

        return $metadata;
    }
}
