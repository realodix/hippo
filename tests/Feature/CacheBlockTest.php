<?php

namespace Realodix\Hippo\Test\Feature;

use PHPUnit\Framework\Attributes as PHPUnit;
use Realodix\Hippo\Cache\Repository;
use Realodix\Hippo\Processor\Strategy\Block;
use Realodix\Hippo\Test\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

class CacheBlockTest extends TestCase
{
    protected function tearDown(): void
    {
        $filesystem = new Filesystem;
        if ($filesystem->exists($this->tmpDir)) {
            $files = array_merge(
                glob($this->tmpDir.'/*.json'),
                glob($this->tmpDir.'/.*.json'),
            );
            if ($files) {
                $filesystem->remove($files);
            }
        }
    }

    public function testSetCacheFile(): void
    {
        $inputFile = __DIR__.'/../Integration/cache.txt';
        $processingFile = Path::canonicalize($this->tmpDir.'/'.basename($inputFile));

        $filesystem = new Filesystem;
        $filesystem->copy($inputFile, $processingFile, true);

        $this->runCommand($processingFile, $this->cacheFile);

        $cacheContent = json_decode(file_get_contents($this->cacheFile), true)['fixer'];
        $this->assertCount(1, $cacheContent);
        $this->assertArrayHasKey($processingFile, $cacheContent);
    }

    public function testSetCacheFolder(): void
    {
        $inputFile = __DIR__.'/../Integration/cache.txt';
        $processingFile = Path::canonicalize($this->tmpDir.'/'.basename($inputFile));

        $filesystem = new Filesystem;
        $filesystem->copy($inputFile, $processingFile, true);

        $this->runCommand($processingFile, $this->tmpDir);

        $content = file_get_contents($this->tmpDir.'/'.Repository::DEFAULT_CACHE_FILENAME);
        $cacheContent = json_decode($content, true)['fixer'];
        $this->assertCount(1, $cacheContent);
        $this->assertArrayHasKey($processingFile, $cacheContent);
    }

    #[PHPUnit\Test]
    public function partial_NewFile(): void
    {
        $block = $this->app->make(Block::class);
        $this->app->instance(Block::class, $block);
        $block->blockSize = 2;

        $this->assertFilter(
            __DIR__.'/../Integration/cache_partial/newfile_expected.txt',
            __DIR__.'/../Integration/cache_partial/newfile_actual.txt',
        );
    }

    #[PHPUnit\Test]
    public function partial_ExistingFile(): void
    {
        $block = $this->app->make(Block::class);
        $block->blockSize = 3;
        $this->app->instance(Block::class, $block);

        $tempCachePath = $this->createDynamicCache(
            __DIR__.'/../Integration/cache_partial/existingfile_default.json',
        );

        $this->assertFilter(
            __DIR__.'/../Integration/cache_partial/existingfile_default_expected.txt',
            __DIR__.'/../Integration/cache_partial/existingfile_default_actual.txt',
            $tempCachePath,
            ['--partial' => true],
        );
    }

    #[PHPUnit\Test]
    public function partial_ExistingFile_Threshold(): void
    {
        $block = $this->app->make(Block::class);
        $block->blockSize = 2;
        $this->app->instance(Block::class, $block);

        $tempCachePath = $this->createDynamicCache(
            __DIR__.'/../Integration/cache_partial/existingfile_threshold.json',
        );

        $this->assertFilter(
            __DIR__.'/../Integration/cache_partial/existingfile_threshold_expected.txt',
            __DIR__.'/../Integration/cache_partial/existingfile_threshold_actual.txt',
            $tempCachePath,
            ['--partial' => true],
        );
    }

    #[PHPUnit\Test]
    public function partial_ExistingFile_LastLine(): void
    {
        $block = $this->app->make(Block::class);
        $block->threshold = 4;
        $block->blockSize = 2;
        $this->app->instance(Block::class, $block);

        $tempCachePath = $this->createDynamicCache(
            __DIR__.'/../Integration/cache_partial/existingfile_lastline.json',
        );

        $this->assertFilter(
            __DIR__.'/../Integration/cache_partial/existingfile_lastline_expected.txt',
            __DIR__.'/../Integration/cache_partial/existingfile_lastline_actual.txt',
            $tempCachePath,
            ['--partial' => true],
        );
    }

    private function createDynamicCache(string $fixturePath): string
    {
        $cacheFixture = json_decode(file_get_contents($fixturePath), true);
        $newCacheData = $cacheFixture;
        $newCacheData['fixer'] = [];

        foreach ($cacheFixture['fixer'] as $path => $hash) {
            $absolutePath = Path::canonicalize(__DIR__.'/../../'.$path);
            $newCacheData['fixer'][$absolutePath] = $hash;
        }

        $tempCachePath = $this->tmpDir.'/'.basename($fixturePath);
        file_put_contents($tempCachePath, json_encode($newCacheData));

        return $tempCachePath;
    }
}
