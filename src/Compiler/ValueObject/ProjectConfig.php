<?php

namespace Realodix\Hippo\Compiler\ValueObject;

final readonly class ProjectConfig
{
    /**
     * @param FilterConfig[] $filters
     */
    public function __construct(
        public ?string $cacheDir,
        public string $outputDir,
        public array $filters,
    ) {}
}
