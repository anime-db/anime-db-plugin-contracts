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

use AnimeDb\PluginContracts\Manifest\ManifestValidationError;

/**
 * Checks parsed `link-rules.examples.yaml` (format version 1) against a set of link rules and
 * reports every problem found, not just the first one.
 *
 * Every example is run through {@see LinkRulesApplier}, so the check uses the very same
 * semantics as the host application. Besides matching the expected results, every rule must be
 * the winning rule for at least one example. A rule that fails to run (PCRE error, backtrack limit)
 * is reported with the failure text instead of being skipped silently.
 *
 * Precondition: `$rules` must have already passed {@see LinkRulesValidator::validate()} without
 * errors; an unbalanced expression can escape the `\A(?:…)\z` wrapper and match any link.
 */
final class LinkRulesExamplesChecker
{
    public const SUPPORTED_VERSION = 1;

    /**
     * @param array<string, string>   $rules    expression => replacement, in order of application
     * @param array<array-key, mixed> $examples parsed `link-rules.examples.yaml` content
     *
     * @return ManifestValidationError[]
     */
    public function check(array $rules, array $examples): array
    {
        $errors = [];

        if (($examples['version'] ?? null) !== self::SUPPORTED_VERSION) {
            $errors[] = new ManifestValidationError('version', \sprintf('Field "version" must be the integer %d.', self::SUPPORTED_VERSION));
        }

        if (!\is_array($examples['examples'] ?? null)) {
            $errors[] = new ManifestValidationError('examples', 'Field "examples" must be a map of link to expected result.');

            return $errors;
        }

        $applier = new LinkRulesApplier();
        $covered = [];
        $ruleErrors = [];

        foreach ($examples['examples'] as $url => $expected) {
            if (!\is_string($url) || !str_starts_with($url, 'https://')) {
                $errors[] = new ManifestValidationError('examples', \sprintf('Example key "%s" must be a string starting with "https://".', $url));

                continue;
            }

            $field = 'examples.'.$url;
            if ($expected !== null && !\is_string($expected)) {
                $errors[] = new ManifestValidationError($field, 'Expected result must be a string or null.');

                continue;
            }

            $actual = null;
            foreach ($rules as $expression => $replacement) {
                $actual = $applier->apply([$expression => $replacement], $url, static function (string $failed, string $message) use (&$ruleErrors, $url): void {
                    $ruleErrors[$failed] ??= \sprintf('rule failed on example "%s": %s', $url, $message);
                });
                if ($actual !== null) {
                    $covered[$expression] = true;

                    break;
                }
            }

            if ($actual !== $expected) {
                $errors[] = new ManifestValidationError($field, \sprintf('expected %s, got %s', $this->quote($expected), $this->quote($actual)));
            }
        }

        foreach ($ruleErrors as $expression => $message) {
            $errors[] = new ManifestValidationError('rules.'.$expression, $message);
        }

        foreach ($rules as $expression => $replacement) {
            if (!isset($covered[$expression]) && !isset($ruleErrors[$expression])) {
                $errors[] = new ManifestValidationError('rules.'.$expression, 'no example matches this rule');
            }
        }

        return $errors;
    }

    private function quote(?string $value): string
    {
        return $value === null ? 'null' : '"'.$value.'"';
    }
}
