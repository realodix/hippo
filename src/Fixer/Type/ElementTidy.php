<?php

namespace Realodix\Haiku\Fixer\Type;

use Composer\Pcre\Preg;
use Realodix\Haiku\Fixer\Regex;
use Realodix\Haiku\Helper;

final class ElementTidy
{
    // /** @var array<string, string> */
    // private array $stringLiterals = [];

    /**
     * Normalize an element hiding rule.
     *
     * @param string $line The rule line
     * @param array<string> $m The regex match
     * @return string The normalized rule
     */
    public function handle(string $line, array $m): string
    {
        // https://adguard.com/kb/general/ad-filtering/create-own-filters/#non-basic-rules-modifiers
        if (str_starts_with($line, '[$')) {
            return $line;
        }

        $domain = $m[1]; // Extract domains
        $separator = $m[2]; // Extract separator
        $selector = $m[3]; // Extract selector

        $normalizedDomain = $this->normalizeDomain($domain);
        $normalizedSelector = $this->normalizeSelector($selector, $domain);

        return $normalizedDomain.$separator.$normalizedSelector;
    }

    private function normalizeDomain(string $domain): string
    {
        if (str_contains($domain, ',')) {
            return Helper::uniqueSorted(explode(',', $domain), fn($s) => ltrim($s, '~'))
                ->implode(',');
        }

        return $domain;
    }

    private function normalizeSelector(string $selector, string $domain): string
    {
        // `example.com## .ads` to `example.com##.ads`
        if ($domain !== '' && $selector !== '' && $selector[0] === ' ') {
            $selector = ltrim($selector);
        }

        $selector = "@{$selector}@"; // Wrap selector in a temporary character to handle edge cases
        $selector = $this->extractStringLiterals($selector);
        $selector = $this->normalizeCombinatorWhitespace($selector);
        $selector = $this->lowercasePseudoClasses($selector);
        $selector = $this->restoreStringLiterals($selector);
        $selector = substr($selector, 1, -1); // Unwrap the selector and reconstruct the final rule

        return $selector;
    }

    private function normalizeCombinatorWhitespace(string $selector): string
    {
        if (preg_match(Regex::UBO_JS_PATTERN, $selector)) {
            return $selector;
        }

        return Preg::replaceCallback(
            Regex::SELECTOR_COMBINATOR,
            function ($matches) {
                $replaceBy = $matches[1] === '(' ? $matches[2].' ' : ' '.$matches[2].' ';
                if ($replaceBy === '   ') {
                    $replaceBy = ' ';
                }

                return $matches[1].$replaceBy.$matches[3];
            },
            $selector,
        );
    }

    private function lowercasePseudoClasses(string $selector): string
    {
        return Preg::replaceCallback(
            Regex::PSEUDO_PATTERN,
            fn($matches) => strtolower($matches[0]),
            $selector,
        );
    }

    private function extractStringLiterals(string $selector): string
    {
        // $this->stringLiterals = [];

        // return Preg::replaceCallback(
        //     Regex::ATTRIBUTE_VALUE_PATTERN,
        //     function ($matches) {
        //         $placeholder = '___STRING_LITERAL_'.count($this->stringLiterals).'___';
        //         $this->stringLiterals[$placeholder] = $matches[0];

        //         return $placeholder;
        //     },
        //     $selector,
        // );

        return $selector;
    }

    private function restoreStringLiterals(string $selector): string
    {
        // if (!empty($this->stringLiterals)) {
        //     return str_replace(
        //         array_keys($this->stringLiterals),
        //         array_values($this->stringLiterals),
        //         $selector,
        //     );
        // }

        return $selector;
    }
}
