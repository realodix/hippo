<?php

namespace Realodix\Haiku\Config\ValueObject;

/**
 * A filter list configuration
 */
final readonly class FilterSet
{
    /**
     * @param string $outputPath The path to the output file
     * @param array<string> $source The source files for the filter list
     * @param array<string, mixed> $metadata The metadata configuration
     * @param bool $unique Removes duplicate filter rules
     */
    public function __construct(
        public string $outputPath,
        public string $header,
        public array $source,
        private array $metadata,
        public bool $unique,
    ) {}

    /**
     * Get the metadata configuration
     *
     * @return array<string, mixed>
     */
    public function metadata(): array
    {
        $default = [
            'date_modified' => !empty($this->metadata) ? true : false,
            'header' => '',
            'title' => '',
            'version' => false,
            'custom' => '',
        ];

        return array_merge($default, $this->metadata);
    }
}
