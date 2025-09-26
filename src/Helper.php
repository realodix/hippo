<?php

namespace Realodix\Hippo;

use Illuminate\Container\Container;

final class Helper
{
    /**
     * Get the available container instance.
     *
     * @template TClass of object
     *
     * @param string|class-string<TClass> $abstract
     * @param list<mixed> $parameters
     * @return ($abstract is class-string<TClass> ? TClass : mixed)
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public static function app($abstract = null, array $parameters = [])
    {
        if (is_null($abstract)) {
            return Container::getInstance();
        }

        return Container::getInstance()->make($abstract, $parameters);
    }

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
