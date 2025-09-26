<?php

namespace Realodix\Hippo;

final class Helper
{
    /**
     * Returns a sorted, unique array of strings.
     *
     * @param array<string> $value The array of strings to process.
     * @param callable|null $sortBy The sorting function to use. Defaults to null.
     * @return \Illuminate\Support\Collection<int, string>
     */
    public static function uniqueSorted(array $value, ?callable $sortBy = null)
    {
        return collect($value)
            ->filter(fn($s) => $s !== '')
            ->unique()
            ->sortBy($sortBy)
            ->values();
    }
}
