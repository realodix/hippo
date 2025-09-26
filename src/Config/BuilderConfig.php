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
        private Filesystem $fs,
    ) {}

    /**
     * @param array{
     *  output_dir?: string,
     *  filter_list?: array<array<string, mixed>>
     * } $config
     */
    public function make(array $config): self
    {
        $this->outputDir = $this->outputDir($config['output_dir'] ?? null);
        $this->filterSet = $this->filterSets($config['filter_list'] ?? []);

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
     * @param string|null $dir The output directory
     *
     * @throws \Symfony\Component\Filesystem\Exception\IOException
     */
    private function outputDir(?string $dir): string
    {
        if ($dir === null) {
            return base_path();
        }

        if (Path::isAbsolute($dir)) {
            throw new InvalidConfigurationException(sprintf(
                'The "output_dir" must be a relative path, %s given.',
                $dir,
            ));
        }

        $outputDir = base_path($dir);
        if (!$this->fs->exists($outputDir)) {
            $this->fs->mkdir($outputDir);
        }

        return $outputDir;
    }

    /**
     * Resolves the filter list configuration for each filter list.
     *
     * @param array<array<string, mixed>> $filterLists
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
