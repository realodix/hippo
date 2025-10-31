<?php

namespace Realodix\Hippo\Config\ValueObject;

use Symfony\Component\Filesystem\Path;

/**
 * A filter list configuration
 */
final readonly class FilterSet
{
    /**
     * @param list<string> $source The source files for the filter list
     * @param array<string, mixed> $metadata The metadata configuration
     * @param bool $unique Removes duplicate filter rules
     */
    public function __construct(
        public string $outputPath,
        public array $source,
        private array $metadata,
        public bool $unique,
    ) {
        if (empty($this->source)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'The "source" key for the filter list "%s" must contain at least one item.',
                    Path::makeRelative($this->outputPath, base_path()),
                ),
            );
        }
    }

    /**
     * Get the metadata configuration
     *
     * @return array{
     *  date_modified: bool,
     *  version: bool,
     *  header: string,
     *  title: string,
     *  custom: string,
     * } $metadata
     */
    public function metadata(): array
    {
        $defautl = [
            'date_modified' => !empty($this->metadata) ? true : false,
            'header' => '',
            'title' => '',
            'version' => false,
            'custom' => '',
        ];

        return array_merge($defautl, $this->metadata);
    }
}
