<?php

namespace Realodix\Haiku\Builder;

use Carbon\Carbon;

final class Metadata
{
    /**
     * Builds an array of formatted metadata strings based on the configured filter set.
     *
     * If the "header" key is present in the data, it will be prepended to
     * the metadata array.
     *
     * @param array<string, mixed> $data
     * @return array<string> The built metadata array
     */
    public function build($data): array
    {
        $metadata = collect([
            $this->title($data['title']),
            $this->lastModified($data['date_modified']),
            $this->version($data['version']),
            $this->custom($data['custom']),
        ])->flatten()
            ->map(fn($m) => $m ? "! {$m}" : '')
            ->when($this->header($data['header']), fn($c, $h) => $c->prepend($h))
            ->filter(); // Remove empty values from the array

        return $metadata->all();
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

        $date = Carbon::now()->toRfc7231String();

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
     * @return array<string>
     */
    private function custom(string $data): array
    {
        if (empty($data)) {
            return [];
        }

        return explode("\n", $data);
    }
}
