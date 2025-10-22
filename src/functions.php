<?php

use Composer\Autoload\ClassLoader;
use Illuminate\Container\Container;
use Symfony\Component\Filesystem\Path;

if (!function_exists('app')) {
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
    function app($abstract = null, array $parameters = [])
    {
        if (is_null($abstract)) {
            return Container::getInstance();
        }

        return Container::getInstance()->make($abstract, $parameters);
    }
}

if (!function_exists('base_path')) {
    /**
     * Get the path to the base of the application root .
     */
    function base_path(): string
    {
        return dirname(array_values(array_filter(
            array_keys(ClassLoader::getRegisteredLoaders()),
            fn($path) => !str_starts_with($path, 'phar://'),
        ))[0]);
    }
}
