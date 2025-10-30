<?php

namespace Realodix\Hippo\Cache;

use Illuminate\Container\Attributes\Singleton;
use Realodix\Hippo\Enums\Scope;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

#[Singleton]
final class Repository
{
    const DEFAULT_CACHE_FILENAME = '.hippo_cache.json';

    /**
     * The array of stored values.
     *
     * @var array<string, mixed>
     */
    private array $storage = [];

    private string $cachePath = self::DEFAULT_CACHE_FILENAME;

    private string $scope = Scope::F->value;

    public function __construct(
        private Filesystem $filesystem,
    ) {}

    /**
     * Set the cache file path.
     *
     * @param string $cachePath The cache file path.
     */
    public function setCacheFile(string $cachePath): self
    {
        $this->cachePath = $cachePath;

        return $this;
    }

    /**
     * Retrieves the path to the cache file.
     *
     * @return string The path to the cache file.
     */
    public function getCacheFile(): string
    {
        return $this->cachePath;
    }

    /**
     * Set the scope for which the cache is stored.
     *
     * @param \Realodix\Hippo\Enums\Scope $scope The scope to set
     */
    public function setScope(Scope $scope): self
    {
        $this->scope = $scope->value;

        return $this;
    }

    /**
     * Load the cache from a file.
     *
     * @throws IOException
     */
    public function load(): self
    {
        if (file_exists($this->cachePath)) {
            $content = @file_get_contents($this->cachePath);
            if ($content === false) {
                throw new IOException("Failed to read cache file: {$this->cachePath}");
            }

            $data = json_decode($content, true);
            if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
                throw new IOException("Failed to decode cache file: {$this->cachePath}");
            }

            $this->storage = $data ?? [];
        }

        return $this;
    }

    /**
     * Saves the current cache data to the cache file.
     *
     * @throws \Symfony\Component\Filesystem\Exception\IOException
     */
    public function save(): void
    {
        $json = json_encode($this->storage, JSON_PRETTY_PRINT);
        $this->filesystem->dumpFile($this->cachePath, $json);
    }

    /**
     * Set the cached data for the given key.
     *
     * @param string $key The key to set
     * @param array<string, mixed> $data The data to set
     */
    public function set(string $key, array $data): void
    {
        $this->storage[$this->scope][$key] = $data;
    }

    /**
     * Returns the cached data for the given key, or null if the key does not exist.
     *
     * @param string $key The key to retrieve
     * @return array<string, mixed>|null
     */
    public function get(string $key): ?array
    {
        return $this->storage[$this->scope][$key] ?? null;
    }

    /**
     * Returns the entire cache for the current scope as an array.
     *
     * @return array<string, mixed>|array{}
     */
    public function all(): array
    {
        return $this->storage[$this->scope] ?? [];
    }

    /**
     * Removes the given key from the cache.
     *
     * @param string $key The key to remove
     */
    public function remove(string $key): void
    {
        unset($this->storage[$this->scope][$key]);
    }

    /**
     * Clears the cache for the current scope.
     */
    public function clear(): void
    {
        $this->storage[$this->scope] = [];
    }
}
