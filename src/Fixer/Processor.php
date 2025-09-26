<?php

namespace Realodix\Haiku\Fixer;

use Composer\Pcre\Preg;
use Realodix\Haiku\Fixer\Type\ElementTidy;
use Realodix\Haiku\Fixer\Type\NetworkTidy;
use Realodix\Haiku\Helper;

final class Processor
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
     * @param array<string> $lines An array of raw filter lines
     * @return array<string> The processed and optimized list of filter lines
     */
    public function process(array $lines): array
    {
        $result = []; // Stores the final processed rules
        $section = []; // Temporary storage for a section of rules

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            // Check for comments, headers, or include directives,
            // which act as section breaks.
            if ($this->isSpecialLine($line)) {
                // Write current section if it exists and reset counters
                if ($section) {
                    $result = array_merge($result, $this->processSection($section));
                    $section = [];
                }

                // Add the comment/header line to the result
                $result[] = $line;

                continue;
            }

            // Categorize the line as either an element rule or a network filter.
            if (preg_match(Regex::COSMETIC_RULE, $line, $m) || str_starts_with($line, '[$')) {
                $section[] = $this->elementTidy->handle($line, $m);
            } else {
                $section[] = $this->networkTidy->handle($line);
            }
        }

        // Write any remaining section
        if ($section) {
            $result = array_merge($result, $this->processSection($section));
        }

        return $result;
    }

    /**
     * Processes a section of filter rules by normalizing, sorting, de-duplicating
     * and combining them into their final form.
     *
     * @param array<string> $section Tidied filter rules
     * @return array<string> The processed lines for the section
     */
    private function processSection(array $section): array
    {
        $cosmetic = [];
        $network = [];
        $adguardJs = [];

        foreach ($section as $rule) {
            if (preg_match(Regex::COSMETIC_RULE, $rule)) {
                $cosmetic[] = $rule;
            } elseif (preg_match(Regex::AG_JS_RULE, $rule)) {
                $adguardJs[] = $rule;
            } else {
                $network[] = $rule;
            }
        }

        $cosmeticResult = $this->combiner->handle(
            Helper::uniqueSorted(
                array_merge($cosmetic, $adguardJs),
                fn($value) => Preg::replace(Regex::COSMETIC_DOMAIN, '', $value),
            )->all(),
            Regex::COSMETIC_DOMAIN,
            ',',
        );

        $networkResult = $this->combiner->handle(
            Helper::uniqueSorted(
                $network,
                fn($value) => str_starts_with($value, '@@') ? '}'.$value : $value,
                SORT_STRING | SORT_FLAG_CASE,
            )->all(),
            Regex::NET_OPTION_DOMAIN,
            '|',
        );

        return array_merge($cosmeticResult, $networkResult);
    }

    /**
     * Determines if a given filter line is a special line.
     */
    public function isSpecialLine(string $line): bool
    {
        return
            // comment
            str_starts_with($line, '!')
            // special comments starting with # but not ## (element hiding)
            || str_starts_with($line, '#') && !$this->isCosmeticRule($line)
            // header
            || str_starts_with($line, '[') && str_ends_with($line, ']');
    }

    /**
     * Determines if a given filter line is a cosmetic filter rule.
     */
    public function isCosmeticRule(string $line): bool
    {
        // https://regex101.com/r/OW1tkq/1
        $basic = Preg::isMatch('/^#@?#[^\s|\#]|^#@?##[^\s|\#]/', $line);
        // https://regex101.com/r/SPcKMv/1
        $advanced = Preg::isMatch('/^(#(?:@?(?:\$|\?|%)|@?\$\?)#)[^\s]/', $line);

        return $basic || $advanced;
    }
}
