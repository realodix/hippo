<?php

namespace Realodix\Hippo\Config;

use Symfony\Component\Filesystem\Filesystem;

final class CompilerConfig
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
     * } $compilerConfigData
     */
    public function make(array $compilerConfigData, string $workingDirectory): self
    {
        $this->workingDirectory = $workingDirectory;
        $this->outputDir = $this->outputDir($compilerConfigData);
        $this->filters = $this->parseFilterSets($compilerConfigData['filter_list'] ?? []);

        return $this;
    }

    /**
     * @return string[]
     */
    public function getAllOutputPaths(): array
    {
        return array_map(fn(FilterSet $filter) => $filter->outputPath, $this->filters);
    }

    /**
     * @param array<int, array{output_file: string, metadata?: list<string>, source?: list<string>}> $filterLists
     * @return array<int, FilterSet>
     */
    private function parseFilterSets(array $filterLists): array
    {
        $filters = [];
        foreach ($filterLists as $list) {
            $filters[] = new FilterSet(
                outputFile: $list['output_file'],
                outputPath: $this->outputDir.'/'.ltrim($list['output_file'], '/'),
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
