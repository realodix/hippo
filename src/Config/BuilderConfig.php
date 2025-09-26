<?php

namespace Realodix\Hippo\Config;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

final class BuilderConfig
{
    public string $outputDir;

    /** @var array<int, FilterSet> */
    public array $filters;

    private string $workingDirectory;

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
     */
    public function make(array $builderConfigData, string $workingDirectory): self
    {
        $this->workingDirectory = $workingDirectory;
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
                outputPath: Path::canonicalize(Path::join($this->outputDir, $list['output_file'])),
                metadata: $list['metadata'] ?? [],
                source: $list['source'] ?? [],
            );
        }

        return $filters;
    }

    /**
     * @param array{output_dir?: string} $configData
     */
    private function outputDir(array $configData): string
    {
        if (isset($configData['output_dir']) && ! empty($configData['output_dir'])) {
            $outputDir = $configData['output_dir'];

            if (substr($outputDir, 0, 1) !== DIRECTORY_SEPARATOR) {
                $outputDir = DIRECTORY_SEPARATOR.$outputDir;
            }

            $fullOutputDir = $this->workingDirectory.$outputDir;
            if (!$this->filesystem->exists($fullOutputDir)) {
                $this->filesystem->mkdir($fullOutputDir);
            }

            return $fullOutputDir;
        }

        return $this->workingDirectory;
    }
}
