<?php

namespace Realodix\Haiku;

final class Helper
{
    /**
     * Returns a sorted, unique array of strings.
     *
     * @param array<string> $value The array of strings to process
     * @param callable|null $sortBy The sorting function to use
     * @param int $flags The sorting flags
     * @return \Illuminate\Support\Collection<int, string>
     */
    public static function uniqueSorted(array $value, ?callable $sortBy = null, $flags = SORT_REGULAR)
    {
        $c = collect($value)
            ->filter(fn($s) => $s !== '')
            ->unique();

        $c = is_callable($sortBy)
            ? $c->sortBy($sortBy, $flags)
            : $c->sort();

        return $c->values();
    }
}
