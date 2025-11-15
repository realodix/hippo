<?php

namespace Realodix\Hippo\Fixer\Type;

use Composer\Pcre\Preg;
use Realodix\Hippo\Helper;

final class NetworkTidy
{
    /**
     * A list of options that can have multiple values.
     *
     * @var array<string>
     */
    const MULTI_VALUE_OPTIONS = [
        'domain', 'from', 'to', 'denyallow', 'method',
    ];

    /**
     * A list of options that have case-sensitive values.
     *
     * @var array<string>
     */
    const CASE_SENSITIVE_VALUE_OPTIONS = [
        'csp', 'permissions', 'removeparam', 'replace', 'urltransform',
        'cookie', 'hls',
    ];

    /**
     * Tidies a network filter rule by normalizing options and sorting domains.
     *
     * @param string $line The raw network filter line
     * @return string The tidied filter line
     */
    public function handle(string $line): string
    {
        // https://adguard.com/kb/general/ad-filtering/create-own-filters/#non-basic-rules-modifiers
        if (Preg::match('/^\[\$[a-z]+=[^\]]+\]/', $line)) {
            return $line;
        }

        $m = [];
        if (!Preg::match(Regex::NET_OPTION, $line, $m)) {
            return $this->removeUnnecessaryWildcard($line);
        }

        $filterText = $this->removeUnnecessaryWildcard($m[1]);
        $filterOptions = $this->parseOptions($m[2]);
        $optionList = $this->buildOptionList($filterOptions);

        return $filterText.'$'.$optionList->implode(',');
    }

    /**
     * @return array<string, array<string>>
     */
    private function parseOptions(string $rawOptions): array
    {
        // Initialize an empty array
        $parsed = ['genericOpts' => []];
        foreach (self::MULTI_VALUE_OPTIONS as $key) {
            $parsed[$key] = [];
        }

        foreach (Preg::split('/(?<!\\\),/', $rawOptions) as $option) {
            $parts = explode('=', $option, 2);
            $nameWithPrefix = $parts[0];
            $value = $parts[1] ?? null;

            $name = ltrim($nameWithPrefix, '~');
            $lowerName = strtolower($name);

            if (in_array($lowerName, self::MULTI_VALUE_OPTIONS)) {
                if ($value !== null) {
                    // if it's not a regex, make it lowercase
                    if (!str_contains($value, '/')) {
                        $value = strtolower($value);
                    }

                    array_push($parsed[$lowerName], ...explode('|', $value));
                }
            } elseif (in_array($lowerName, self::CASE_SENSITIVE_VALUE_OPTIONS, true)) {
                // Lowercase the name, preserve value
                $newNameWithPrefix = str_replace($name, $lowerName, $nameWithPrefix);
                if ($value !== null) {
                    $newNameWithPrefix .= '='.$value;
                }
                $parsed['genericOpts'][] = $newNameWithPrefix;
            } else {
                // Lowercase the whole option
                $parsed['genericOpts'][] = strtolower($option);
            }
        }

        return $parsed;
    }

    /**
     * Builds and sorts the list of filter options from parsed data.
     *
     * @param array<string, array<string>> $options Parsed options from parseOptions()
     * @return \Illuminate\Support\Collection<int, string>
     */
    private function buildOptionList(array $options)
    {
        $optionList = $options['genericOpts'];

        // Add back the consolidated domain-like options.
        foreach (self::MULTI_VALUE_OPTIONS as $name) {
            if (!empty($options[$name])) {
                $optionList[] = $name.'='.Helper::uniqueSorted(
                    $options[$name],
                    fn($s) => ltrim($s, '~'),
                )->implode('|');
            }
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
