<?php

namespace Realodix\Hippo\Processor;

use Composer\Pcre\Preg;
use Realodix\Hippo\Processor\Type\Combiner;
use Realodix\Hippo\Processor\Type\ElementTidy;
use Realodix\Hippo\Processor\Type\NetworkTidy;
use Realodix\Hippo\Processor\Type\Regex;

final class FilterProcessor
{
    public function __construct(
        private Combiner $combiner,
        private ElementTidy $elementTidy,
        private NetworkTidy $networkTidy,
    ) {}

    /**
     * Processes an array of filter lines, optimizing them into a sorted
     * and combined list.
     *
     * This is the main entry point of the processor. It splits the list into
     * sections, processes each section individually, and then concatenates
     * the results.
     *
     * @param array<string> $lines An array of raw filter lines.
     * @return array<string> The processed and optimized list of filter lines.
     */
    public function process(array $lines): array
    {
        $result = []; // Stores the final processed rules
        $section = []; // Temporary storage for a section of rules

        foreach ($lines as $raw) {
            $line = trim((string) $raw);
            if ($line === '') {
                continue; // Skip blank lines
            }

            // Check for comments, headers, or include directives,
            // which act as section breaks.
            if ($this->isSpecialLine($line)) {
                // Write current section if it exists and reset counters
                if ($section) {
                    $result = array_merge($result, $this->writeFilters($section));
                    $section = [];
                }

                // Add the comment/header line to the result
                $result[] = $line;

                continue;
            }

            // Categorize the line as either an element rule or a network filter.
            if (Preg::match(Regex::COSMETIC_RULE, $line) || str_starts_with($line, '[$')) {
                $section[] = $this->elementTidy->handle($line);
            } else {
                $section[] = $this->networkTidy->handle($line);
            }
        }

        // Write any remaining section
        if ($section) {
            $result = array_merge($result, $this->writeFilters($section));
        }

        return $result;
    }

    /**
     * Writes and combines a section of tidied rules, sorting them based on rule type.
     *
     * @param array<string> $section Array of tidied filter rules.
     * @return array<string> The processed lines for the section.
     */
    private function writeFilters(array $section): array
    {
        $cosmeticFilters = [];
        $networkFilters = [];

        foreach ($section as $rule) {
            if (preg_match(Regex::COSMETIC_DOMAIN, $rule) || str_starts_with($rule, '[$')) {
                $cosmeticFilters[] = $rule;
            } else {
                $networkFilters[] = $rule;
            }
        }

        $result = [];
        if (!empty($cosmeticFilters)) {
            $uncombined = collect($cosmeticFilters)
                ->unique()
                ->sortBy(fn($a) => preg_replace(Regex::COSMETIC_DOMAIN, '', $a))
                ->values();
            $result = array_merge(
                $result,
                $this->combiner->handle($uncombined->all(), Regex::COSMETIC_DOMAIN, ','),
            );
        }

        if (!empty($networkFilters)) {
            $uncombined = collect($networkFilters)
                ->unique()
                ->sortBy(fn($value) => $value, SORT_STRING | SORT_FLAG_CASE)
                ->values();
            $result = array_merge(
                $result,
                $this->combiner->handle($uncombined->all(), Regex::NET_OPTION_HAS_DOMAIN, '|'),
            );
        }

        return $result;
    }

    /**
     * Determines if a given filter line is a special line.
     *
     * Special lines are comments, special comments starting with # but not ##
     * (element hiding), headers, or the python-abp directives.
     */
    public function isSpecialLine(string $line): bool
    {
        return
            // comment
            str_starts_with($line, '!')
            // special comments starting with # but not ## (element hiding)
            || str_starts_with($line, '#') && !$this->isCosmeticRule($line)
            // header
            || (str_starts_with($line, '[') && str_ends_with($line, ']'))
            // the python-abp directives
            || (str_starts_with($line, '%include') && str_ends_with($line, '%'));
    }

    /**
     * Determines if a given filter line is a cosmetic filter rule.
     */
    public function isCosmeticRule(string $line): bool
    {
        // https://regex101.com/r/OW1tkq/1
        $basic = (bool) Preg::match('/^#@?#[^\s|\#]|^#@?##[^\s|\#]/', $line);
        // https://regex101.com/r/SPcKMv/1
        $advanced = (bool) Preg::match('/^(#(?:@?(?:\$|\?|%)|@?\$\?)#)[^\s]/', $line);

        return $basic || $advanced;
    }
}
