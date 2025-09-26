<?php

namespace Realodix\Hippo;

use Symfony\Component\Finder\Finder as BaseFinder;

final class Finder
{
    /**
     * Create a new Finder instance.
     *
     * @param string $directory The directory to search in
     * @param array<string> $ignore An array of patterns to ignore
     * @return BaseFinder The new Finder instance
     */
    public function create(string $directory, array $ignore): BaseFinder
    {
        $finder = new BaseFinder;
        $finder->files()
            ->in($directory)
            ->name(['*.txt', '*.adfl'])
            ->ignoreDotFiles(true)
            ->ignoreVCS(true);

        foreach ($ignore as $pattern) {
            $finder->notPath($pattern);
        }

        return $finder;
    }
}
