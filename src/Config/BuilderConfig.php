<?php

namespace Realodix\Hippo\Config;

use Realodix\Hippo\Config\ValueObject\FilterSet;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

final class BuilderConfig
{
    public string $outputDir;

    /** @var list<FilterSet> */
    public array $filters;

    /**
     * Current working directory
     */
    private string $cwd;

    public function __construct(
        private Filesystem $filesystem,
    ) {}

    /**
     * @param array{
     *   output_dir?: string,
     *   filter_list?: list<array{
     *     output_file: string,
     *     metadata?: list<string>,
     *     source?: list<string>
     *   }>
     * } $builderConfigData
     * @param string $cwd Current working directory
     */
    public function make(array $builderConfigData, string $cwd): self
    {
        $this->cwd = $cwd;
        $this->outputDir = $this->outputDir($builderConfigData);
        $this->filters = $this->parseFilterSets($builderConfigData['filter_list'] ?? []);

        return $this;
    }

    /**
     * @return list<string>
     */
    public function outputPaths(): array
    {
        return array_map(fn(FilterSet $filter) => $filter->outputPath, $this->filters);
    }

    /**
     * @param list<array{output_file: string, metadata?: list<string>, source?: list<string>}> $filterLists
     * @return list<FilterSet>
     */
    private function parseFilterSets(array $filterLists): array
    {
        $filters = [];
        foreach ($filterLists as $list) {
            $filters[] = new FilterSet(
                outputFile: $list['output_file'],
                outputPath: Path::join($this->outputDir, $list['output_file']),
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
            $outputDir = Path::join($this->cwd, $configData['output_dir']);

            if (!$this->filesystem->exists($outputDir)) {
                $this->filesystem->mkdir($outputDir);
            }

            return $outputDir;
        }

        return $this->cwd;
    }
}
