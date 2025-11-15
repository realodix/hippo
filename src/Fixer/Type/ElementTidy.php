<?php

namespace Realodix\Hippo\Fixer\Type;

use Composer\Pcre\Preg;
use Realodix\Hippo\Helper;

final class ElementTidy
{
    // /** @var array<string, string> */
    // private array $stringLiterals = [];

    /**
     * Normalize an element hiding rule.
     *
     * @param string $line The rule line
     * @return string The normalized rule
     */
    public function handle(string $line): string
    {
        if (str_starts_with($line, '[$')) {
            return $line;
        }

        $m = [];
        if (!Preg::match(Regex::COSMETIC_RULE, $line, $m)) {
            return $line;
        }

        $domains = $m[1]; // Extract domains
        $separator = $m[2]; // Extract separator
        $selector = $m[3]; // Extract selector

        // Remove leading characters from the selector only if a domain is present.
        if ($domains !== '' && $selector !== '' && $selector[0] === ' ') {
            $selector = ltrim($selector, ' ');
        }

        $domains = $this->sortDomains($domains);

        // Wrap selector in a temporary character to handle edge cases
        $selector = '@'.$selector.'@';

        $selector = $this->extractStringLiterals($selector);
        $selector = $this->normalizeCombinatorWhitespace($selector);
        $selector = $this->lowercasePseudoClasses($selector);
        $selector = $this->restoreStringLiterals($selector);

        // Unwrap the selector and reconstruct the final rule
        $finalSelector = substr($selector, 1, -1);

        return $domains.$separator.$finalSelector;
    }

    private function sortDomains(string $domains): string
    {
        if (str_contains($domains, ',')) {
            return Helper::uniqueSorted(
                explode(',', $domains),
                fn($s) => ltrim($s, '~'),
            )->implode(',');
        }

        return $domains;
    }



    private function normalizeCombinatorWhitespace(string $selector): string
    {
        if (Preg::match(Regex::UBO_JS_PATTERN, $selector)) {
            return $selector;
        }

        return Preg::replaceCallback(
            Regex::SELECTOR_COMBINATOR,
            function ($matches) {
                $replaceBy = ($matches[1] === '(') ? ($matches[2].' ') : (' '.$matches[2].' ');
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
