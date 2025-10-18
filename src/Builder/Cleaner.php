<?php

namespace Realodix\Hippo\Builder;

final class Cleaner
{
    /**
     * Cleans a list of raw filter source contents by removing metadata, comments,
     * and empty lines, leaving only the valid filter rules.
     *
     * @param list<string> $sources The list of raw filter source contents.
     * @return list<string> The cleaned filter contents, each containing only valid rules.
     */
    public static function clean(array $sources): array
    {
        return array_map(function (string $content) {
            $content = self::stripMetadataAgent($content);
            $content = self::stripComments($content);
            $content = self::stripEmptyLines($content);

            return rtrim($content);
        }, $sources);
    }

    /**
     * Remove adblock agent metadata.
     *
     * Like this:
     * - [Adblock Plus 2.0]
     * - [uBlock Origin]
     * - [AdGuard]
     */
    private static function stripMetadataAgent(string $content): string
    {
        return preg_replace('/^\[.*\]$/m', '', $content);
    }

    /**
     * Removes comments (lines that start with !) from lines.
     *
     * Don not remove comments that start with !# (Preprocessor directives).
     * - https://github.com/gorhill/uBlock/wiki/Static-filter-syntax#pre-parsing-directives
     * - https://adguard.com/kb/general/ad-filtering/create-own-filters/#preprocessor-directives
     * - https://regex101.com/r/VSOcD6/1
     */
    private static function stripComments(string $content): string
    {
        return preg_replace('/^!(?!#\s?(?:include\s|if|endif|else)).*/m', '', $content);
    }

    /**
     * Remove empty lines.
     */
    private static function stripEmptyLines(string $content): string
    {
        return preg_replace('/^\h*\v+/m', '', $content);
    }
}
