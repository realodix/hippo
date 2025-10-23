<?php

namespace Realodix\Hippo\Builder;

use Illuminate\Support\Arr;
use Realodix\Hippo\Cache\Cache;

final class Metadata
{
    /**
     * @var \Realodix\Hippo\Config\ValueObject\FilterSet
     */
    private $config;

    public function __construct(
        private Cache $cache,
    ) {}

    /**
     * Sets up the metadata generator with the given filter set configuration.
     *
     * @param \Realodix\Hippo\Config\ValueObject\FilterSet $config
     */
    public function setUp($config): self
    {
        $this->config = $config;

        return $this;
    }

    /**
     * Builds an array of formatted metadata strings based on the configured filter set.
     *
     * The resulting array will contain the following metadata strings:
     * - "! Title: My Filter List"
     * - "! Description: My filter list for ad blocking"
     * - "! Version: 1.0.0"
     * - "! Last modified: 2022-01-01 12:00:00 +0000"
     *
     * If the "header" key is present in the data, it will be prepended to
     * the metadata array.
     *
     * @return array<int, string> The built metadata array.
     */
    public function build(): array
    {
        $config = $this->config->metadata();

        $metadata = collect([
            $this->title($config['title']),
            $this->description($config['description']),
            $this->fVersion($config['version']),
            $this->lastModified($config['date_modified']),
            $this->extras($config['extras']),
        ])->flatten()
            ->map(fn($m) => $m ? "! {$m}" : '')
            ->when($this->header($config['header']), fn($c, $h) => $c->prepend($h))
            ->filter(); // Remove empty values from the array

        return $metadata->toArray();
    }

    public function version(): string
    {
        $config = $this->config;
        $cacheEntry = $this->cache->repository()->get($config->outputPath);
        $currentVersion = Arr::get($cacheEntry, 'version');

        $currentDate = date('y.m');
        if (
            // it doesn't enable versioning
            $config->metadata()['version'] === false
            || empty($currentVersion) // no cached data, assume it's the first
        ) {
            return sprintf('%s.%d', $currentDate, 1);
        }

        $parts = explode('.', $currentVersion);
        $cachedDate = $parts[0].'.'.$parts[1];
        $cachedRevNum = (int) ($parts[2] ?? 0);

        $revNum = ($cachedDate === $currentDate) ? $cachedRevNum + 1 : 1;

        return sprintf('%s.%d', $currentDate, $revNum);
    }

    private function fVersion(bool $value): string
    {
        if ($value === false) {
            return '';
        }

        return sprintf('Version: %s', $this->version());
    }

    private function header(string $value): string
    {
        if (empty($value)) {
            return '';
        }

        return "[{$value}]";
    }

    private function description(string $value): string
    {
        if (empty($value)) {
            return '';
        }

        return "Description: {$value}";
    }

    private function lastModified(bool $value): string
    {
        if ($value === false) {
            return '';
        }

        $date = (new \DateTime)->format(\DateTime::RFC1123);

        return "Last modified: {$date}";
    }

    private function title(string $value): string
    {
        if (empty($value)) {
            return '';
        }

        return "Title: {$value}";
    }

    /**
     * @param list<string> $data
     * @return list<string>
     */
    private function extras(array $data): array
    {
        if (empty($data)) {
            return [];
        }

        return $data;
    }
}
