<?php

namespace Realodix\Hippo\Test\Feature;

use Illuminate\Container\Container;
use Realodix\Hippo\Cache\Cache;
use Realodix\Hippo\Cache\Repository;
use Realodix\Hippo\Config\Config;
use Realodix\Hippo\Enums\Mode;
use Realodix\Hippo\Test\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

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
        $container->singleton(
            \Symfony\Component\Console\Output\OutputInterface::class,
            \Symfony\Component\Console\Output\BufferedOutput::class,
        );

        $this->filesystem = new Filesystem;
        $container->instance(Filesystem::class, $this->filesystem);

        $this->cache = $container->build(Cache::class);
        $this->repository = $this->cache->repository();
        $this->testCacheFile = $this->tmpDir.DIRECTORY_SEPARATOR.'.test_cache.json';
    }

    protected function tearDown(): void
    {
        if ($this->filesystem->exists($this->testCacheFile)) {
            $this->filesystem->remove($this->testCacheFile);
        }

        parent::tearDown();
    }

    public function testResolveCachePathAllScenarios(): void
    {
        $baseDir = $this->tmpDir.DIRECTORY_SEPARATOR.'ResolveCachePath';
        $defaultCacheFile = Repository::DEFAULT_CACHE_FILENAME;

        $cases = [
            // Input, Expected output (relative to baseDir if applicable)
            [null, $defaultCacheFile],
            ['', Path::join($baseDir, $defaultCacheFile)],
            ['custom_cache.json', Path::join($baseDir, 'custom_cache.json')],
            ['cache_dir', Path::join($baseDir, 'cache_dir', $defaultCacheFile)],
            ['cache_dir/', Path::join($baseDir, 'cache_dir', $defaultCacheFile)],
            ['.tmp', Path::join($baseDir, '.tmp', $defaultCacheFile)],
            ['.tmp/', Path::join($baseDir, '.tmp', $defaultCacheFile)],
            ['.tmp/abc.json', Path::join($baseDir, '.tmp', 'abc.json')],
            ['.tmp/abc.txt', Path::join($baseDir, '.tmp', 'abc.txt')],
        ];

        foreach ($cases as [$input, $expected]) {
            // 1. Make sure environment is clean before each case
            $this->filesystem->remove($baseDir);
            $this->filesystem->mkdir($baseDir);

            // 2. Prepend baseDir for relative paths
            $inputPath = $input === null ? null : Path::join($baseDir, $input);

            // 3. Assertion
            $actual = $this->callPrivateMethod($this->cache, 'resolveCachePath', [$inputPath]);
            $this->assertSame($expected, $actual, 'Failed for input: '.var_export($input, true));
        }

        // 6. Cleanup (optional safety)
        if ($this->filesystem->exists($baseDir)) {
            $this->filesystem->remove($baseDir);
        }
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
        $config = $this->app->make(Config::class);
        $config->cacheDir = $this->testCacheFile;

        $this->cache->prepareForRun($config, Mode::Default);
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

        $config = $this->app->make(Config::class);
        $config->cacheDir = $this->testCacheFile;

        $this->cache->prepareForRun($config, Mode::Default);
        $this->assertEmpty($this->repository->all()); // Stale entry should be cleaned
    }

    public function testPrepareForRunWithForceAndNoExistingCache(): void
    {
        $this->repository->setCacheFile($this->testCacheFile);
        $this->repository->set('key1', ['data1']); // Add some data
        $this->repository->save();

        $config = $this->app->make(Config::class);
        $config->cacheDir = $this->testCacheFile;

        $this->cache->prepareForRun($config, Mode::Force);
        $this->assertEmpty($this->repository->all()); // Cache should be cleared
        // $this->assertFalse($this->filesystem->exists($this->testCacheFile)); // Cache file should be removed
    }

    public function testPrepareForRunWithForceAndExistingCache(): void
    {
        $this->repository->setCacheFile($this->testCacheFile);
        $this->repository->set('key1', ['data1']);
        $this->repository->save();

        $config = $this->app->make(Config::class);
        $config->cacheDir = $this->testCacheFile;

        $this->cache->prepareForRun($config, Mode::Force);
        $this->assertEmpty($this->repository->all());
        // $this->assertFalse($this->filesystem->exists($this->testCacheFile));
    }

    public function testPrepareForRunWithForceMultipleTimes(): void
    {
        $this->repository->setCacheFile($this->testCacheFile);
        $this->repository->set('key1', ['data1']);
        $this->repository->save();

        $config = $this->app->make(Config::class);
        $config->cacheDir = $this->testCacheFile;

        $this->cache->prepareForRun($config, Mode::Force);
        $this->assertEmpty($this->repository->all());
        // $this->assertFalse($this->filesystem->exists($this->testCacheFile));

        // Add some data again, then call prepareForRun with force again
        $this->repository->set('key2', ['data2']);
        $this->repository->save();

        $this->cache->prepareForRun($config, Mode::Force);
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
