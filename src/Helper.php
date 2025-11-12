<?php

namespace Realodix\Hippo;

final class Helper
{
    /**
     * Returns a sorted, unique array of strings.
     *
     * @param array<string> $value The array of strings to process
     * @param callable|null $sortBy The sorting function to use
     * @return \Illuminate\Support\Collection<int, string>
     */
    public static function uniqueSorted(array $value, ?callable $sortBy = null)
    {
        $c = collect($value)
            ->filter(fn($s) => $s !== '')
            ->unique();

        if (is_callable($sortBy)) {
            $c = $c->sortBy($sortBy);
        } else {
            $c = $c->sort();
        }

        return $c->values();
    }
}
