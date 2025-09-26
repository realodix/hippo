<?php

namespace Realodix\Hippo\Compiler;

final class Metadata
{
    /**
     * Creates an array of metadata strings from the given data and revision.
     *
     * The resulting array will contain the following metadata strings:
     * - Version: e.g. "Version: 1.0.0"
     * - Title: e.g. "Title: My Filter List"
     * - Description: e.g. "Description: My filter list for ad blocking"
     * - Last modified: e.g. "Last modified: 2022-01-01 12:00:00 +0000"
     * - Expires: e.g. "Expires: 4 days (update frequency)"
     * - Homepage: e.g. "Homepage: https://example.com"
     * - License: e.g. "License: MIT License"
     *
     * If the "header" key is present in the data, it will be prepended to
     * the metadata array.
     *
     * @param list<string> $data The data to create the metadata from.
     * @param int $rev The revision number to include in the version string.
     * @return list<string> The created metadata array.
     */
    public function create(array $data, int $rev): array
    {
        $metadata = collect([
            $this->version($rev),
            $this->title($data),
            $this->description($data),
            $this->lastModified(),
            $this->expires($data),
            $this->homepage($data),
            $this->license($data),
        ])->map(fn($m) => $m ? "! {$m}" : '')
            ->when($this->header($data), fn($c, $h) => $c->prepend($h))
            ->filter(); // Remove empty values from the array

        return $metadata->toArray();
    }

    /**
     * @param array{header?: string} $metadata
     */
    private function header(array $metadata): string
    {
        return data_get($metadata, 'header', '');
    }

    private function lastModified(): string
    {
        $date = (new \DateTime)->format(\DateTime::RFC1123);

        return "Last modified: {$date}";
    }

    private function version(int $rev): string
    {
        return sprintf('Version: %s.%s', date('y.m'), $rev);
    }

    /**
     * @param array{description?: string} $metadata
     */
    private function description(array $metadata): string
    {
        if (empty($metadata['description'])) {
            return '';
        }

        return "Description: {$metadata['description']}";
    }

    /**
     * @param array{expires?: string} $metadata
     */
    private function expires(array $metadata): string
    {
        $exp = '4 days (update frequency)';

        if (!empty($metadata['expires'])) {
            $exp = $metadata['expires'];
        }

        return "Expires: {$exp}";
    }

    /**
     * @param array{homepage?: string} $metadata
     */
    private function homepage(array $metadata): string
    {
        if (empty($metadata['homepage'])) {
            return '';
        }

        return "Homepage: {$metadata['homepage']}";
    }

    /**
     * @param array{license?: string} $metadata
     */
    private function license(array $metadata): string
    {
        if (empty($metadata['license'])) {
            return '';
        }

        return "License: {$metadata['license']}";
    }

    /**
     * @param array{title?: string} $metadata
     */
    private function title(array $metadata): string
    {
        if (empty($metadata['title'])) {
            return '';
        }

        return "Title: {$metadata['title']}";
    }
}
