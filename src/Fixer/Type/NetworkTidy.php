<?php

namespace Realodix\Hippo\Fixer\Type;

use Composer\Pcre\Preg;
use Realodix\Hippo\Helper;

final class NetworkTidy
{
    /**
     * Tidies a network filter rule by normalizing options and sorting domains.
     *
     * @param string $line The raw network filter line
     * @return string The tidied filter line
     */
    public function handle(string $line): string
    {
        $line = trim($line);

        // https://adguard.com/kb/general/ad-filtering/create-own-filters/#non-basic-rules-modifiers
        if (Preg::match('/^\[\$[a-z]+=[^\]]+\]/', $line)) {
            return $line;
        }

        $m = [];
        if (!Preg::match(Regex::NET_OPTION, $line, $m)) {
            return $this->removeUnnecessaryWildcard($line);
        }

        $filterText = $this->removeUnnecessaryWildcard($m[1]);
        $rawOptions = $m[2];

        $parsedOptions = $this->parseOptions($rawOptions);
        $optionList = $this->buildSortedOptionList($parsedOptions);

        return $filterText.'$'.$optionList->implode(',');
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function parseOptions(string $rawOptions): array
    {
        $parsed = [
            'domain' => [], 'from' => [], 'to' => [], 'denyallow' => [],
            'method' => [],
            'otherOpts' => [],
        ];

        $caseSensitiveValueOptions = [
            'cookie', 'hls', 'removeparam', 'replace', 'urltransform',
            'permissions', 'csp',
        ];

        foreach (Preg::split('/(?<!\\\),/', $rawOptions) as $option) {
            $parts = explode('=', $option, 2);
            $nameWithPrefix = $parts[0];
            $value = $parts[1] ?? null;

            $name = ltrim($nameWithPrefix, '~');
            $lowerName = strtolower($name);

            if (in_array($lowerName, ['domain', 'from', 'to', 'denyallow'])) {
                if ($value !== null) {
                    array_push($parsed[$lowerName], ...explode('|', strtolower($value)));
                }
            } elseif ($lowerName === 'method') {
                if ($value !== null) {
                    array_push($parsed['method'], ...explode('|', strtolower($value)));
                }
            } elseif (in_array($lowerName, $caseSensitiveValueOptions, true)) {
                // Lowercase the name, preserve value
                $newNameWithPrefix = str_replace($name, $lowerName, $nameWithPrefix);
                $otherOption = $newNameWithPrefix;
                if ($value !== null) {
                    $otherOption .= '='.$value;
                }
                $parsed['otherOpts'][] = $otherOption;
            } else {
                // Lowercase the whole option
                $parsed['otherOpts'][] = strtolower($option);
            }
        }

        return $parsed;
    }

    /**
     * @param array<string, array<int, string>> $parsedOptions
     * @return \Illuminate\Support\Collection<int, string>
     */
    private function buildSortedOptionList(array $parsedOptions)
    {
        $optionList = $parsedOptions['otherOpts'];

        // Add back the consolidated domain-like options.
        foreach (['domain', 'from', 'to', 'denyallow'] as $name) {
            if (!empty($parsedOptions[$name])) {
                $optionList[] = $name.'='.Helper::uniqueSorted(
                    $parsedOptions[$name],
                    fn($s) => ltrim((string) $s, '~'),
                )->implode('|');
            }
        }

        // Add back the consolidated method option.
        if (!empty($parsedOptions['method'])) {
            $optionList[] = 'method='.Helper::uniqueSorted($parsedOptions['method'])->implode('|');
        }

        return Helper::uniqueSorted(
            $optionList,
            fn($a) => $this->prioritizeFilterOption($a),
        );
    }

    /**
     * Determines the priority of a filter option for sorting purposes.
     *
     * This method transforms a filter option (e.g., 'script', '~third-party', 'important')
     * into a modified string used for sorting filter options in a consistent order.
     *
     * The sorting prioritizes specific options as follows:
     * - High-priority options like `important`, `badfilter`,`first-party`, `strict1p` are
     *   placed "at the front" of the sorted list.
     * - All other options are sorted alphabetically.
     *
     * @param string $option The option to sort (e.g., 'script', '~third-party')
     * @return string The sorted option
     */
    private function prioritizeFilterOption(string $option): string
    {
        // Prio 1: (Highest): 'important' and 'party' options must always be at the top.
        if ($option === 'important' || $option === 'badfilter') {
            return '0'.$option;
        }
        if ($option === 'strict1p' || $option === 'strict3p') {
            return '1'.$option;
        }
        if (Preg::match('/^~?((?:1|3)p|(first|third)-party)/', $option)) {
            if ($option[0] === '~') {
                return '2'.substr($option, 1).'~';
            }

            return '2'.$option;
        }

        // Prio 3
        if (Preg::match('/
            ^(csp|header|method|permissions|redirect(?:-rule)?
                |removeparam|replace
            )=
            /x', $option)) {
            return '6'.$option;
        }

        if (str_starts_with($option, 'denyallow=')) {
            return '7'.$option;
        }

        if (
            str_starts_with($option, 'domain=')
            || str_starts_with($option, 'from=')
            || str_starts_with($option, 'to=')
            || str_starts_with($option, 'ipaddress=')
        ) {
            return '8'.$option;
        }

        // https://github.com/realodix/AdBlockID/commit/372694cdf4
        if ($option[0] === '~') {
            return '5'.substr($option, 1).'~';
        }

        // Prio 2: Other options
        return '5'.$option;
    }

    /**
     * Removes unnecessary wildcard characters ('*') from a filter rule.
     *
     * @param string $filterText The filter text.
     * @return string string The filter text with unnecessary wildcards removed.
     */
    private function removeUnnecessaryWildcard(string $filterText): string
    {
        // $allowlist = false;
        // $had_star = false;

        // if (str_starts_with($filterText, '@@')) {
        //     $allowlist = true;
        //     $filterText = substr($filterText, 2);
        // }

        // while (strlen($filterText) > 1 && $filterText[0] === '*' && $filterText[1] !== '|' && $filterText[1] !== '!') {
        //     $filterText = substr($filterText, 1);
        //     $had_star = true;
        // }

        // while (strlen($filterText) > 1 && $filterText[strlen($filterText) - 1] === '*' && $filterText[strlen($filterText) - 2] !== '|') {
        //     $filterText = substr($filterText, 0, -1);
        //     $had_star = true;
        // }

        // if ($had_star && str_starts_with($filterText, '/') && str_ends_with($filterText, '/')) {
        //     $filterText .= '*';
        // }

        // if ($allowlist) {
        //     $filterText = '@@' . $filterText;
        // }
        return $filterText;
    }
}
