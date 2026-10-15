<?php

/**
 * AnimeDb package.
 *
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2026, Peter Gribanov
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */

/*
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace AnimeDb\PluginContracts\LinkRules;

/**
 * Applies an ordered list of `link-rules.yaml` rules to a link.
 *
 * The single implementation of the application semantics, shared by the host application and
 * the plugin publication gate, so both interpret the rules identically. The link must already
 * be normalized by the application (scheme, host, percent-encoding, query order): this class
 * does not normalize anything itself.
 *
 * Precondition: `$rules` must have already passed {@see LinkRulesValidator::validate()} without
 * errors; an unbalanced expression (e.g. `x)|(?:.*`) escapes the `\A(?:…)\z` wrapper and
 * matches any link.
 */
final class LinkRulesApplier
{
    /**
     * Value of `pcre.backtrack_limit` while rules are being applied.
     */
    public const BACKTRACK_LIMIT = 10000;

    /**
     * @param array<string, string>                 $rules       expression => replacement, in order of application
     * @param (callable(string, string): void)|null $onRuleError called with the expression and the error text when a rule fails to run
     *
     * @return string|null the result of the first matching rule that yields an absolute https link, null if there is none
     */
    public function apply(array $rules, string $url, ?callable $onRuleError = null): ?string
    {
        $previousLimit = ini_set('pcre.backtrack_limit', (string) self::BACKTRACK_LIMIT);

        try {
            foreach ($rules as $expression => $replacement) {
                $result = $this->applyRule((string) $expression, $replacement, $url, $onRuleError);
                if ($result !== null) {
                    return $result;
                }
            }

            return null;
        } finally {
            if ($previousLimit !== false) {
                ini_set('pcre.backtrack_limit', $previousLimit);
            }
        }
    }

    private function applyRule(string $expression, string $replacement, string $url, ?callable $onRuleError): ?string
    {
        $pattern = "\x01\\A(?:".$expression.")\\z\x01u";

        $matched = @preg_match($pattern, $url);
        if ($matched === false) {
            if ($onRuleError !== null) {
                $onRuleError($expression, preg_last_error_msg());
            }

            return null;
        }
        if ($matched === 0) {
            return null;
        }

        $result = @preg_replace($pattern, $replacement, $url);
        if (!\is_string($result)) {
            if ($onRuleError !== null) {
                $onRuleError($expression, preg_last_error_msg());
            }

            return null;
        }

        $parts = parse_url($result);
        if ($parts === false || ($parts['scheme'] ?? null) !== 'https' || ($parts['host'] ?? '') === '') {
            return null;
        }

        return $result;
    }
}
