<?php

namespace Realodix\Hippo\Builder;

final class Metadata
{
    /**
     * Creates an array of metadata strings from the given data and revision.
     *
     * The resulting array will contain the following metadata strings:
     * - "! Title: My Filter List"
     * - "! Description: My filter list for ad blocking"
     * - "! Version: 1.0.0"
     * - "! Last modified: 2022-01-01 12:00:00 +0000"
     * - "! Expires: 4 days (update frequency)"
     * - "! Homepage: https://example.com"
     * - "! License: MIT License"
     *
     * If the "header" key is present in the data, it will be prepended to
     * the metadata array.
     *
     * @param \Realodix\Hippo\Config\ValueObject\FilterSet $config
     * @param string $version The version string (e.g., '25.10.1') to include in the metadata.
     * @return list<string> The created metadata array.
     */
    public function create($config, string $version): array
    {
        $config = $config->metadata;

        $metadata = collect([
            $this->title($config),
            $this->description($config),
            $this->version($config, $version),
            $this->lastModified(),
            $this->expires($config),
            $this->homepage($config),
            $this->license($config),
        ])->map(fn($m) => $m ? "! {$m}" : '')
            ->when($this->header($config), fn($c, $h) => $c->prepend($h))
            ->filter(); // Remove empty values from the array

        return $metadata->toArray();
    }

    /**
     * @param array{header?: string} $config
     */
    private function header(array $config): string
    {
        if (empty($config['header'])) {
            return '';
        }

        return "[{$config['header']}]";
    }

    private function lastModified(): string
    {
        $date = (new \DateTime)->format(\DateTime::RFC1123);

        return "Last modified: {$date}";
    }

    /**
     * @param array{enable_version?: bool} $config
     */
    private function version(array $config, string $version): string
    {
        if (!isset($config['enable_version']) || $config['enable_version'] === false) {
            return '';
        }

        return sprintf('Version: %s', $version);
    }

    /**
     * @param array{description?: string} $config
     */
    private function description(array $config): string
    {
        if (empty($config['description'])) {
            return '';
        }

        return "Description: {$config['description']}";
    }

    /**
     * @param array{expires?: string} $config
     */
    private function expires(array $config): string
    {
        if (empty($config['expires'])) {
            return '';
        }

        return "Expires: {$config['expires']}";
    }

    /**
     * @param array{homepage?: string} $config
     */
    private function homepage(array $config): string
    {
        if (empty($config['homepage'])) {
            return '';
        }

        return "Homepage: {$config['homepage']}";
    }

    /**
     * @param array{license?: string} $config
     */
    private function license(array $config): string
    {
        if (empty($config['license'])) {
            return '';
        }

        return "License: {$config['license']}";
    }

    /**
     * @param array{title?: string} $config
     */
    private function title(array $config): string
    {
        if (empty($config['title'])) {
            return '';
        }

        return "Title: {$config['title']}";
    }
}
