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

namespace AnimeDb\PluginContracts\Manifest;

/**
 * Validates parsed `link-rules.yaml` data (format version 1) and reports every problem
 * found, not just the first one — the plugin author needs the full list of what to fix.
 *
 * Operates on the already parsed array `['version' => 1, 'rules' => [expression => replacement]]`:
 * the contract does not depend on YAML, parsing is up to the consumer. One validator for
 * every consumer: the plugin publication gate and the host application (on plugin install
 * and when loading the rule set), so the file is interpreted identically on both sides.
 */
final class LinkRulesValidator
{
    public const SUPPORTED_VERSION = 1;

    public const MAX_PATTERN_LENGTH = 256;

    private const SUBSTITUTION = '/\$(?:(\d+)|\{(\d+)\})/';

    /**
     * @param array<array-key, mixed> $data parsed `link-rules.yaml` content
     *
     * @return ManifestValidationError[]
     */
    public function validate(array $data): array
    {
        $errors = [];

        if (($data['version'] ?? null) !== self::SUPPORTED_VERSION) {
            $errors[] = new ManifestValidationError('version', \sprintf('Field "version" must be the integer %d.', self::SUPPORTED_VERSION));
        }

        if (!\is_array($data['rules'] ?? null)) {
            $errors[] = new ManifestValidationError('rules', 'Field "rules" must be a map of regular expression to replacement.');

            return $errors;
        }

        foreach ($data['rules'] as $expression => $replacement) {
            if (!\is_string($expression)) {
                $errors[] = new ManifestValidationError('rules', \sprintf('Rule key "%s" must be a string regular expression (quote numeric keys).', $expression));

                continue;
            }

            $field = 'rules.'.$expression;
            $groups = $this->validateExpression($expression, $field, $errors);
            if (!\is_string($replacement)) {
                $errors[] = new ManifestValidationError($field, 'Replacement must be a string.');

                continue;
            }
            $errors = [...$errors, ...$this->validateReplacement($replacement, $field, $groups)];
        }

        return $errors;
    }

    /**
     * Returns the number of capturing groups, or null when the expression is invalid.
     *
     * @param ManifestValidationError[] $errors
     */
    private function validateExpression(string $expression, string $field, array &$errors): ?int
    {
        if (preg_match('/[\x00-\x1f\x7f]/', $expression) === 1) {
            $errors[] = new ManifestValidationError($field, 'Regular expression must not contain control characters.');

            return null;
        }

        $length = preg_match_all('/./su', $expression);
        if ($length === false) {
            $errors[] = new ManifestValidationError($field, 'Regular expression is not valid UTF-8.');

            return null;
        }
        if ($length > self::MAX_PATTERN_LENGTH) {
            $errors[] = new ManifestValidationError($field, \sprintf('Regular expression is longer than %d characters.', self::MAX_PATTERN_LENGTH));

            return null;
        }

        // Alone first: an unbalanced expression such as `x)|(?:.*` compiles inside the wrapper
        // but escapes `\A(?:…)\z` and would match every link.
        $matches = [];
        // The empty alternative always matches, which exposes every group of the expression.
        if (@preg_match("\x01(?:".$expression.")|\x01u", '', $matches, \PREG_UNMATCHED_AS_NULL) !== 1
            || @preg_match("\x01".$expression."\x01u", '') === false
            || @preg_match("\x01\\A(?:".$expression.")\\z\x01u", '') === false
        ) {
            $errors[] = new ManifestValidationError($field, 'Regular expression does not compile on its own and wrapped in \A(?:…)\z with the "u" flag.');

            return null;
        }

        return \count(array_filter(array_keys($matches), '\is_int')) - 1;
    }

    /**
     * @return ManifestValidationError[]
     */
    private function validateReplacement(string $replacement, string $field, ?int $groups): array
    {
        $errors = [];

        if (!str_starts_with($replacement, 'https://')) {
            $errors[] = new ManifestValidationError($field, 'Replacement must start with "https://".');
        }

        if (preg_match('/[\s\\\\\x00-\x1f\x7f]/', $replacement) === 1) {
            $errors[] = new ManifestValidationError($field, 'Replacement must not contain whitespace, backslashes or control characters.');
        }

        $matches = [];
        preg_match_all(self::SUBSTITUTION, $replacement, $matches, \PREG_SET_ORDER);
        if ($matches === []) {
            $errors[] = new ManifestValidationError($field, 'Replacement must contain at least one "$N" or "${N}" substitution; without it every matching link would be merged into one.');
        }

        foreach ($matches as $match) {
            $number = (int) ($match[2] ?? $match[1] ?? '');
            if ($number < 1 || ($groups !== null && $number > $groups)) {
                $errors[] = new ManifestValidationError($field, \sprintf('Substitution "%s" refers to a group the expression does not have; only groups 1…N are allowed.', $match[0]));
            }
        }

        return $errors;
    }
}
