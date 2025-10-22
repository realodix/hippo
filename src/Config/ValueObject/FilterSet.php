<?php

namespace Realodix\Hippo\Config\ValueObject;

/**
 * A filter list configuration
 */
final readonly class FilterSet
{
    /**
     * @param list<string> $source The source files for the filter list
     * @param array<string, mixed> $metadata The metadata configuration
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
     *  date_modified: bool,
     *  enable_version: bool,
     *  description: string,
     *  header: string,
     *  title: string,
     *  extras: list<string>,
     * } $metadata
     */
    public function metadata(): array
    {
        $defautl = [
            'date_modified' => true,
            'enable_version' => false,
            'description' => '',
            'header' => '',
            'title' => '',
            'extras' => [''],
        ];

        return array_merge($defautl, $this->metadata);
    }
}
