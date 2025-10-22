<?php

namespace Realodix\Hippo\Test\Feature;

use PHPUnit\Framework\Attributes as PHPUnit;
use Realodix\Hippo\Cache\Repository;
use Realodix\Hippo\Fixer\Strategy\Block;
use Realodix\Hippo\Test\TestCase;
use Symfony\Component\Filesystem\Path;

class CacheBlockTest extends TestCase
{
    protected function tearDown(): void
    {
        if ($this->fs->exists($this->tmpDir)) {
            $files = array_merge(
                glob($this->tmpDir.'/*.json'),
                glob($this->tmpDir.'/.*.json'),
            );
            if ($files) {
                $this->fs->remove($files);
            }
        }
    }

    public function testSetCacheFile(): void
    {
        $inputFile = base_path('tests/Integration/cache.txt');
        $processingFile = Path::join($this->tmpDir, basename($inputFile));
        $this->fs->copy($inputFile, $processingFile, true);

        $this->runFixCommand($processingFile, $this->cacheFile);

        $cacheContent = json_decode(file_get_contents($this->cacheFile), true)['fixer'];
        $this->assertCount(1, $cacheContent);
        $this->assertArrayHasKey($processingFile, $cacheContent);
    }

    public function testSetCacheFolder(): void
    {
        $inputFile = base_path('tests/Integration/cache.txt');
        $processingFile = Path::join($this->tmpDir, basename($inputFile));
        $this->fs->copy($inputFile, $processingFile, true);

        $this->runFixCommand($processingFile, $this->tmpDir);

        $content = file_get_contents(Path::join($this->tmpDir, Repository::DEFAULT_CACHE_FILENAME));
        $cacheContent = json_decode($content, true)['fixer'];
        $this->assertCount(1, $cacheContent);
        $this->assertArrayHasKey($processingFile, $cacheContent);
    }

    #[PHPUnit\Test]
    public function partial_NewFile(): void
    {
        $block = app(Block::class);
        app()->instance(Block::class, $block);
        $block->blockSize = 2;

        $this->assertFilter(
            base_path('tests/Integration/cache_partial/newfile_expected.txt'),
            base_path('tests/Integration/cache_partial/newfile_actual.txt'),
        );
    }

    #[PHPUnit\Test]
    public function partial_ExistingFile(): void
    {
        $block = app(Block::class);
        $block->blockSize = 3;
        app()->instance(Block::class, $block);

        $tempCachePath = $this->createDynamicCache(
            base_path('tests/Integration/cache_partial/existingfile_default.json'),
        );

        $this->assertFilter(
            base_path('tests/Integration/cache_partial/existingfile_default_expected.txt'),
            base_path('tests/Integration/cache_partial/existingfile_default_actual.txt'),
            $tempCachePath,
            ['--partial' => true],
        );
    }

    #[PHPUnit\Test]
    public function partial_ExistingFile_Threshold(): void
    {
        $block = app(Block::class);
        $block->blockSize = 2;
        app()->instance(Block::class, $block);

        $tempCachePath = $this->createDynamicCache(
            base_path('tests/Integration/cache_partial/existingfile_threshold.json'),
        );

        $this->assertFilter(
            base_path('tests/Integration/cache_partial/existingfile_threshold_expected.txt'),
            base_path('tests/Integration/cache_partial/existingfile_threshold_actual.txt'),
            $tempCachePath,
            ['--partial' => true],
        );
    }

    #[PHPUnit\Test]
    public function partial_ExistingFile_LastLine(): void
    {
        $block = app(Block::class);
        $block->threshold = 4;
        $block->blockSize = 2;
        app()->instance(Block::class, $block);

        $tempCachePath = $this->createDynamicCache(
            base_path('tests/Integration/cache_partial/existingfile_lastline.json'),
        );

        $this->assertFilter(
            base_path('tests/Integration/cache_partial/existingfile_lastline_expected.txt'),
            base_path('tests/Integration/cache_partial/existingfile_lastline_actual.txt'),
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
            $absolutePath = base_path($path);
            $newCacheData['fixer'][$absolutePath] = $hash;
        }

        $tempCachePath = Path::join($this->tmpDir, basename($fixturePath));
        file_put_contents($tempCachePath, json_encode($newCacheData));

        return $tempCachePath;
    }
}
