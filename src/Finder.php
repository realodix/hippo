<?php

namespace Realodix\Hippo;

use Symfony\Component\Finder\Finder as BaseFinder;

final class Finder
{
    /**
     * Create a new Finder instance.
     *
     * @param string $directory The directory to search in
     * @param array<string> $ignores An array of patterns to ignore
     * @return BaseFinder The new Finder instance
     */
    public function create(string $directory, array $ignores): BaseFinder
    {
        $finder = new BaseFinder;
        $finder->files()
            ->in($directory)
            ->name(['*.txt', '*.adfl'])
            ->ignoreDotFiles(true)
            ->ignoreVCS(true)
            ->notPath($ignores);

        return $finder;
    }
}
