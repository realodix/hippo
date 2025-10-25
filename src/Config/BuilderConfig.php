<?php

namespace Realodix\Hippo\Config;

use Realodix\Hippo\Config\ValueObject\FilterSet;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

final class BuilderConfig
{
    public string $outputDir;

    /** @var list<FilterSet> */
    public array $filterSet;

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
        $this->filterSet = $this->parseFilterSets($builderConfigData['filter_list'] ?? []);

        return $this;
    }

    /**
     * @return list<string>
     */
    public function outputPaths(): array
    {
        return array_map(fn(FilterSet $filter) => $filter->outputPath, $this->filterSet);
    }

    /**
     * @param list<array{
     *  filename: string,
     *  metadata?: array<string, mixed>,
     *  source?: list<string>
     * }> $filterLists
     * @return list<FilterSet>
     */
    private function parseFilterSets(array $filterLists): array
    {
        $filters = [];
        foreach ($filterLists as $list) {
            $filters[] = new FilterSet(
                outputPath: Path::join($this->outputDir, $list['filename']),
                source: $list['source'] ?? [],
                metadata: $list['metadata'] ?? [],
            );
        }

        return $filters;
    }

    /**
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
}
