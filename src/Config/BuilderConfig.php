<?php

namespace Realodix\Hippo\Config;

use Realodix\Hippo\Config\ValueObject\FilterSet;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

final class BuilderConfig
{
    /** @var list<FilterSet> */
    public array $filterSet;

    private string $outputDir;

    public function __construct(
        private Filesystem $filesystem,
    ) {}

    /**
     * @param array{
     *  output_dir?: string,
     *  filter_list?: list<array<string, mixed>>
     * } $builderConfigData
     */
    public function make(array $builderConfigData): self
    {
        $this->outputDir = $this->outputDir($builderConfigData);
        $this->filterSet = $this->filterSets($builderConfigData['filter_list'] ?? []);

        return $this;
    }

    /**
     * Resolves and ensures the existence of the output directory.
     *
     * - If the "output_dir" key is defined in the configuration, its path is
     *   resolved relative to the project base path. The directory will be
     *   created if it does not already exist.
     * - If no "output_dir" is provided, the project base path will be used.
     *
     * @param array{output_dir?: string} $configData
     *
     * @throws \Symfony\Component\Filesystem\Exception\IOException
     */
    private function outputDir(array $configData): string
    {
        if (!empty($configData['output_dir'])) {
            $outputDir = base_path($configData['output_dir']);

            if (!$this->filesystem->exists($outputDir)) {
                $this->filesystem->mkdir($outputDir);
            }

            return $outputDir;
        }

        return base_path();
    }

    /**
     * Resolves the filter list configuration for each filter list.
     *
     * @param list<array{
     *  filename: string,
     *  remove_duplicates?: bool,
     *  metadata?: array<string, mixed>,
     *  source?: list<string>
     * }> $filterLists
     * @return list<FilterSet>
     */
    private function filterSets(array $filterLists): array
    {
        $filters = [];
        foreach ($filterLists as $list) {
            $filters[] = new FilterSet(
                outputPath: Path::join($this->outputDir, $list['filename']),
                source: $list['source'] ?? [],
                metadata: $list['metadata'] ?? [],
                unique: $list['remove_duplicates'] ?? false,
            );
        }

        return $filters;
    }
}
