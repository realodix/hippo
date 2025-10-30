<?php

namespace Realodix\Hippo\Builder;

use Carbon\Carbon;

final class Metadata
{
    /**
     * Builds an array of formatted metadata strings based on the configured filter set.
     *
     * The resulting array will contain the following metadata strings:
     * - "! Title: My Filter List"
     * - "! Last modified: 2022-01-01 12:00:00 +0000"
     *
     * If the "header" key is present in the data, it will be prepended to
     * the metadata array.
     *
     * @param \Realodix\Hippo\Config\ValueObject\FilterSet $config
     * @return array<int, string> The built metadata array.
     */
    public function build($config): array
    {
        $config = $config->metadata();

        $metadata = collect([
            $this->title($config['title']),
            $this->lastModified($config['date_modified']),
            $this->version($config['version']),
            $this->extras($config['extras']),
        ])->flatten()
            ->map(fn($m) => $m ? "! {$m}" : '')
            ->when($this->header($config['header']), fn($c, $h) => $c->prepend($h))
            ->filter(); // Remove empty values from the array

        return $metadata->toArray();
    }

    private function header(string $value): string
    {
        if (empty($value)) {
            return '';
        }

        return "[{$value}]";
    }

    private function lastModified(bool $value): string
    {
        if ($value === false) {
            return '';
        }

        $date = (new \DateTime)->format(\DateTime::RFC7231);

        return "Last modified: {$date}";
    }

    private function title(string $value): string
    {
        if (empty($value)) {
            return '';
        }

        return "Title: {$value}";
    }

    private function version(bool $value): string
    {
        if ($value === false) {
            return '';
        }

        return sprintf(
            'Version: %s.%d%d',
            date('y.m'),
            Carbon::now()->startOfDay()->diffInMinutes(Carbon::now()),
            Carbon::now()->second,
        );
    }

    /**
     * @return list<string>
     */
    private function extras(string $data): array
    {
        if (empty($data)) {
            return [];
        }

        return explode("\n", $data);
    }
}
