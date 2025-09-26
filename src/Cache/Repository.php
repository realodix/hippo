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
    const HASH_ALGO = 'xxh3';

    /** @var array<string, mixed> */
    private array $cache = [];

    private string $cacheFile = self::DEFAULT_CACHE_FILENAME;

    private string $scope = Scope::F->value;

    public function __construct(
        private Filesystem $filesystem,
    ) {}

    public function setCacheFile(?string $cacheFile): self
    {
        $this->cacheFile = $cacheFile;

        return $this;
    }

    public function getCacheFile(): string
    {
        return $this->cacheFile;
    }

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
        if ($this->filesystem->exists($this->cacheFile)) {
            $content = file_get_contents($this->cacheFile);
            if ($content === false) {
                throw new IOException("Failed to read cache file: {$this->cacheFile}");
            }

            $data = json_decode($content, true);
            if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
                throw new IOException("Failed to decode cache file: {$this->cacheFile}");
            }

            $this->cache = $data ?? [];
        }

        return $this;
    }

    public function save(): void
    {
        $json = json_encode($this->cache, JSON_PRETTY_PRINT);
        $this->filesystem->dumpFile($this->cacheFile, $json);
    }

    /**
     * Set the cached data for the given key.
     *
     * @param string $key The key to set
     * @param array<string, mixed> $data The data to set
     */
    public function set(string $key, array $data): void
    {
        $this->cache[$this->scope][$key] = $data;
    }

    /**
     * Returns the cached data for the given key, or null if the key does not exist.
     *
     * @param string $key The key to retrieve
     * @return array<string>|null
     */
    public function get(string $key): ?array
    {
        return $this->cache[$this->scope][$key] ?? null;
    }

    /**
     * Returns the entire cache as an array.
     *
     * @return array<string, mixed> The entire cache as an associative array
     */
    public function all(): array
    {
        return $this->cache[$this->scope] ?? [];
    }

    /**
     * Removes the given key from the cache.
     *
     * @param string $key The key to remove
     */
    public function remove(string $key): void
    {
        unset($this->cache[$this->scope][$key]);
    }

    /**
     * Clears the cache for the current scope.
     */
    public function clear(): void
    {
        $this->cache[$this->scope] = [];
    }

    /**
     * Compute block hashes for a given set of blocks.
     *
     * @param array<array<string>> $blocks
     * @return array<string>
     */
    public function blockHash(array $blocks): array
    {
        $hashes = [];
        foreach ($blocks as $i => $block) {
            $hashes[$i] = hash(Repository::HASH_ALGO, implode("\n", $block));
        }

        return $hashes;
    }
}
