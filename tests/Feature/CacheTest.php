<?php

namespace Realodix\Hippo\Test\Feature;

use Illuminate\Container\Container;
use Realodix\Hippo\Cache\Cache;
use Realodix\Hippo\Cache\Repository;
use Realodix\Hippo\Enums\Mode;
use Realodix\Hippo\Test\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class CacheTest extends TestCase
{
    private Cache $cache;

    private Repository $repository;

    private Filesystem $filesystem;

    private string $testCacheFile;

    protected function setUp(): void
    {
        parent::setUp();

        $container = Container::getInstance();
        $container->flush();

        $this->filesystem = new Filesystem;
        $container->instance(Filesystem::class, $this->filesystem);

        $this->cache = $container->build(Cache::class);
        $this->repository = $this->cache->repository();
        $this->testCacheFile = $this->tmpDir.DIRECTORY_SEPARATOR.'.test_cache.json';

        $cacheFile = Repository::DEFAULT_CACHE_FILENAME;
        if ($this->filesystem->exists($cacheFile)) {
            $this->filesystem->rename($cacheFile, $cacheFile.'.tmp');
        }
    }

    protected function tearDown(): void
    {
        if ($this->filesystem->exists($this->testCacheFile)) {
            $this->filesystem->remove($this->testCacheFile);
        }

        // Clean up cache directory created in tests
        $cacheDir = $this->tmpDir.DIRECTORY_SEPARATOR.'cache_dir';
        if ($this->filesystem->exists($cacheDir)) {
            $this->filesystem->remove($cacheDir);
        }

        $cacheFile = Repository::DEFAULT_CACHE_FILENAME;
        if ($this->filesystem->exists($cacheFile)) {
            $this->filesystem->remove($cacheFile);
        }
        if ($this->filesystem->exists($cacheFile.'.tmp')) {
            $this->filesystem->rename($cacheFile.'.tmp', $cacheFile);
        }

        parent::tearDown();
    }

    // Test resolveCachePath scenarios
    public function testResolveCachePathWithNull(): void
    {
        $resolvedPath = $this->callPrivateMethod($this->cache, 'resolveCachePath', [null]);
        $this->assertSame(Repository::DEFAULT_CACHE_FILENAME, $resolvedPath);
    }

    public function testResolveCachePathWithEmptyString(): void
    {
        $resolvedPath = $this->callPrivateMethod($this->cache, 'resolveCachePath', ['']);
        $this->assertSame(Repository::DEFAULT_CACHE_FILENAME, $resolvedPath);
    }

    public function testResolveCachePathWithDirectory(): void
    {
        $testDir = $this->tmpDir.DIRECTORY_SEPARATOR.'cache_dir';
        $this->filesystem->mkdir($testDir);
        $resolvedPath = $this->callPrivateMethod($this->cache, 'resolveCachePath', [$testDir]);
        $this->assertSame($testDir.DIRECTORY_SEPARATOR.Repository::DEFAULT_CACHE_FILENAME, $resolvedPath);
    }

    public function testResolveCachePathWithDirectoryAndTrailingSlash(): void
    {
        $testDir = $this->tmpDir.DIRECTORY_SEPARATOR.'cache_dir'.DIRECTORY_SEPARATOR;
        $this->filesystem->mkdir($testDir);
        $resolvedPath = $this->callPrivateMethod($this->cache, 'resolveCachePath', [$testDir]);
        $this->assertSame(
            rtrim($testDir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.Repository::DEFAULT_CACHE_FILENAME,
            $resolvedPath,
        );
    }

    public function testResolveCachePathWithFilePath(): void
    {
        $filePath = $this->tmpDir.DIRECTORY_SEPARATOR.'custom_cache.json';
        $resolvedPath = $this->callPrivateMethod($this->cache, 'resolveCachePath', [$filePath]);
        $this->assertSame($filePath, $resolvedPath);
    }

    // Test setCacheFile
    public function testSetCacheFileMethod(): void
    {
        $this->repository->setCacheFile($this->testCacheFile);
        $this->assertSame($this->testCacheFile, $this->repository->getCacheFile());
    }

    // Test basic cache operations
    public function testSetAndGetCache(): void
    {
        $key = 'testKey';
        $data = ['foo' => 'bar'];
        $this->repository->set($key, $data);
        $this->assertSame($data, $this->repository->get($key));
    }

    public function testGetAllCache(): void
    {
        $this->repository->set('key1', ['data1']);
        $this->repository->set('key2', ['data2']);
        $this->assertSame(['key1' => ['data1'], 'key2' => ['data2']], $this->repository->all());
    }

    public function testRemoveCacheEntry(): void
    {
        $key = 'testKey';
        $this->repository->set($key, ['foo' => 'bar']);
        $this->repository->remove($key);
        $this->assertNull($this->repository->get($key));
    }

    public function testClearCache(): void
    {
        $this->repository->set('key1', ['data1']);
        $this->repository->clear();
        $this->assertEmpty($this->repository->all());
    }

    public function testSaveAndLoadCache(): void
    {
        $this->repository->setCacheFile($this->testCacheFile);
        $this->repository->set('key1', ['data1']);
        $this->repository->save();

        $newRepository = $this->repository;
        $newRepository->setCacheFile($this->testCacheFile);
        $newRepository->load();

        $this->assertSame(['key1' => ['data1']], $newRepository->all());
    }

    public function testLoadNonExistentCacheFile(): void
    {
        $this->repository->setCacheFile($this->testCacheFile);
        $this->repository->load(); // Should not throw error
        $this->assertEmpty($this->repository->all());
    }

    // Test prepareForRun scenarios
    public function testPrepareForRunWithNoForceAndNoExistingCache(): void
    {
        $this->cache->prepareForRun($this->testCacheFile, Mode::Default);
        $this->assertEmpty($this->repository->all());
    }

    public function testPrepareForRunWithNoForceAndExistingCache(): void
    {
        // Create a dummy cache file with a stale entry
        $staleFile = $this->tmpDir.DIRECTORY_SEPARATOR.'non_existent_file.txt';
        $this->repository->setCacheFile($this->testCacheFile);
        $this->repository->set($staleFile, ['data' => 'stale']);
        $this->repository->save();

        // Ensure the stale file does not exist
        if ($this->filesystem->exists($staleFile)) {
            $this->filesystem->remove($staleFile);
        }

        $this->cache->prepareForRun($this->testCacheFile, Mode::Default);
        $this->assertEmpty($this->repository->all()); // Stale entry should be cleaned
    }

    public function testPrepareForRunWithForceAndNoExistingCache(): void
    {
        $this->repository->setCacheFile($this->testCacheFile);
        $this->repository->set('key1', ['data1']); // Add some data
        $this->repository->save();

        $this->cache->prepareForRun($this->testCacheFile, Mode::Force);
        $this->assertEmpty($this->repository->all()); // Cache should be cleared
        // $this->assertFalse($this->filesystem->exists($this->testCacheFile)); // Cache file should be removed
    }

    public function testPrepareForRunWithForceAndExistingCache(): void
    {
        $this->repository->setCacheFile($this->testCacheFile);
        $this->repository->set('key1', ['data1']);
        $this->repository->save();

        $this->cache->prepareForRun($this->testCacheFile, Mode::Force);
        $this->assertEmpty($this->repository->all());
        // $this->assertFalse($this->filesystem->exists($this->testCacheFile));
    }

    public function testPrepareForRunWithForceMultipleTimes(): void
    {
        $this->repository->setCacheFile($this->testCacheFile);
        $this->repository->set('key1', ['data1']);
        $this->repository->save();

        $this->cache->prepareForRun($this->testCacheFile, Mode::Force);
        $this->assertEmpty($this->repository->all());
        // $this->assertFalse($this->filesystem->exists($this->testCacheFile));

        // Add some data again, then call prepareForRun with force again
        $this->repository->set('key2', ['data2']);
        $this->repository->save();

        $this->cache->prepareForRun($this->testCacheFile, Mode::Force);
        $this->assertNotEmpty($this->repository->all()); // Should not clear again
        $this->assertTrue($this->filesystem->exists($this->testCacheFile));
    }

    // Test cleanStaleEntries
    public function testCleanStaleEntriesRemovesNonExistentFiles(): void
    {
        $existingFile = $this->tmpDir.DIRECTORY_SEPARATOR.'existing_file.txt';
        $staleFile = $this->tmpDir.DIRECTORY_SEPARATOR.'non_existent_file.txt';

        $this->filesystem->dumpFile($existingFile, 'content');

        $this->repository->setCacheFile($this->testCacheFile);
        $this->repository->set($existingFile, ['data' => 'exists']);
        $this->repository->set($staleFile, ['data' => 'stale']);
        $this->repository->save();

        $this->callPrivateMethod($this->cache, 'cleanStaleEntries');

        $loadedCache = $this->repository->all();
        $this->assertArrayHasKey($existingFile, $loadedCache);
        $this->assertArrayNotHasKey($staleFile, $loadedCache);
        $this->assertTrue($this->filesystem->exists($this->testCacheFile)); // Cache file should still exist

        // Verify the cache file content after cleaning
        $newRepository = $this->repository;
        $newRepository->setCacheFile($this->testCacheFile);
        $newRepository->load();
        $this->assertArrayHasKey($existingFile, $newRepository->all());
        $this->assertArrayNotHasKey($staleFile, $newRepository->all());
    }

    public function testCleanStaleEntriesDoesNotRemoveExistingFiles(): void
    {
        $existingFile = $this->tmpDir.DIRECTORY_SEPARATOR.'existing_file.txt';
        $this->filesystem->dumpFile($existingFile, 'content');

        $this->repository->setCacheFile($this->testCacheFile);
        $this->repository->set($existingFile, ['data' => 'exists']);
        $this->repository->save();

        $this->callPrivateMethod($this->cache, 'cleanStaleEntries');

        $loadedCache = $this->repository->all();
        $this->assertArrayHasKey($existingFile, $loadedCache);
        $this->assertTrue($this->filesystem->exists($this->testCacheFile));
    }
}
