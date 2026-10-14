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
 * Validates decoded `link-rules.json` data (format version 1) and reports every problem
 * found, not just the first one — the plugin author needs the full list of what to fix.
 *
 * Operates on the raw associative array produced by decoding the file, without access to the
 * file system. One validator for every consumer: the plugin publication gate and the host
 * application (on plugin install and when loading the rule set), so the file is interpreted
 * identically on both sides.
 */
final class LinkRulesValidator
{
    public const SUPPORTED_VERSION = 1;

    public const MAX_RULES = 500;

    public const MAX_PATTERN_LENGTH = 256;

    private const SUBSTITUTION = '/\{(path|query|host)\.([^{}]*)\}/';

    /**
     * @param array<string, mixed> $data decoded `link-rules.json` content
     *
     * @return ManifestValidationError[]
     */
    public function validate(array $data): array
    {
        $errors = [];

        if (($data['version'] ?? null) !== self::SUPPORTED_VERSION) {
            $errors[] = new ManifestValidationError('version', \sprintf('Field "version" must be the integer %d.', self::SUPPORTED_VERSION));
        }

        if (!\is_array($data['rules'] ?? null) || !array_is_list($data['rules'])) {
            $errors[] = new ManifestValidationError('rules', 'Field "rules" must be a list of rules.');

            return $errors;
        }

        $rules = $data['rules'];
        if (\count($rules) > self::MAX_RULES) {
            $errors[] = new ManifestValidationError('rules', \sprintf('Field "rules" must contain at most %d rules, %d given.', self::MAX_RULES, \count($rules)));
        }

        foreach ($rules as $index => $rule) {
            $errors = [...$errors, ...$this->validateRule($rule, \sprintf('rules.%d', $index))];
        }

        return $errors;
    }

    /**
     * @return ManifestValidationError[]
     */
    private function validateRule(mixed $rule, string $prefix): array
    {
        if (!\is_array($rule) || ($rule !== [] && array_is_list($rule))) {
            return [new ManifestValidationError($prefix, 'Rule must be an object.')];
        }

        $errors = [];

        // Group info: host => named groups, path => group count, query => param => group count.
        $hostNames = null;
        $pathCount = null;
        $queryCounts = [];

        $hostGroups = $this->validatePattern($rule, 'host', $prefix, $errors);
        if ($hostGroups !== null) {
            $hostNames = array_values(array_filter(array_keys($hostGroups), '\is_string'));
        }

        $pathGroups = $this->validatePattern($rule, 'path', $prefix, $errors);
        if ($pathGroups !== null) {
            $pathCount = \count(array_filter(array_keys($pathGroups), '\is_int')) - 1;
        }

        $queryValid = true;
        if (array_key_exists('query', $rule)) {
            $query = $rule['query'];
            if (!\is_array($query) || ($query !== [] && array_is_list($query))) {
                $errors[] = new ManifestValidationError($prefix.'.query', 'Field "query" must be an object of parameter name to regular expression.');
                $queryValid = false;
            } else {
                foreach ($query as $param => $_) {
                    $groups = $this->validatePattern($query, (string) $param, $prefix.'.query', $errors);
                    if ($groups === null) {
                        $queryValid = false;

                        continue;
                    }
                    $queryCounts[(string) $param] = \count(array_filter(array_keys($groups), '\is_int')) - 1;
                }
            }
        }

        $errors = [...$errors, ...$this->validateCanonical($rule, $prefix, $hostNames, $pathCount, $queryCounts, $queryValid, $hostGroups !== null ? (string) $rule['host'] : null)];

        return $errors;
    }

    /**
     * Checks that `$container[$key]` is a string within the length limit that compiles when
     * wrapped in `\A(?:…)\z`, and returns the groups of its match (name and number keys) or
     * null when it is invalid.
     *
     * @param array<array-key, mixed>   $container
     * @param ManifestValidationError[] $errors
     *
     * @return array<array-key, mixed>|null
     */
    private function validatePattern(array $container, string $key, string $prefix, array &$errors): ?array
    {
        $field = $prefix.'.'.$key;
        if (!\is_string($container[$key] ?? null) || $container[$key] === '') {
            $errors[] = new ManifestValidationError($field, \sprintf('Field "%s" must be a non-empty regular expression string.', $field));

            return null;
        }

        $pattern = $container[$key];
        $length = preg_match_all('/./su', $pattern);
        $length = $length === false ? \strlen($pattern) : $length;
        if ($length > self::MAX_PATTERN_LENGTH) {
            $errors[] = new ManifestValidationError($field, \sprintf('Regular expression in "%s" is longer than %d characters.', $field, self::MAX_PATTERN_LENGTH));

            return null;
        }

        // Alternation with an empty branch always matches, which exposes every group of the pattern.
        $matches = [];
        $result = @preg_match("\x01(?:".$pattern.")|\x01u", '', $matches, \PREG_UNMATCHED_AS_NULL);
        if ($result !== 1) {
            $errors[] = new ManifestValidationError($field, \sprintf('Regular expression in "%s" does not compile.', $field));

            return null;
        }

        return $matches;
    }

    /**
     * @param array<array-key, mixed> $rule
     * @param list<string>|null       $hostNames   named groups of `host`, null when `host` is invalid
     * @param array<string, int>      $queryCounts group count per query parameter
     *
     * @return ManifestValidationError[]
     */
    private function validateCanonical(array $rule, string $prefix, ?array $hostNames, ?int $pathCount, array $queryCounts, bool $queryValid, ?string $hostPattern): array
    {
        $field = $prefix.'.canonical';
        $canonical = $rule['canonical'] ?? null;
        if (!\is_string($canonical) || $canonical === '') {
            return [new ManifestValidationError($field, 'Field "canonical" must be a non-empty string.')];
        }

        if (!str_starts_with($canonical, 'https://')) {
            return [new ManifestValidationError($field, 'Field "canonical" must be an absolute URL starting with "https://".')];
        }

        $errors = [];
        $rest = substr($canonical, \strlen('https://'));
        $authority = $rest;
        if (preg_match('~[/?#]~', $rest, $match, \PREG_OFFSET_CAPTURE) === 1) {
            $authority = substr($rest, 0, $match[0][1]);
        }

        $authoritySubstitutions = [];
        preg_match_all(self::SUBSTITUTION, $authority, $authoritySubstitutions, \PREG_SET_ORDER);
        $literalAuthority = preg_replace(self::SUBSTITUTION, '', $authority) ?? $authority;
        if ($authority === '' || preg_match('/[^a-z0-9.-]/', $literalAuthority) === 1) {
            $errors[] = new ManifestValidationError($field, 'Host of "canonical" must be a non-empty lowercase host name (a-z, 0-9, "." and "-"; punycode for non-ASCII) without user info, port or malformed substitutions.');
        }

        if (preg_match('/[\\\\\s\x00-\x1f\x7f]/', $canonical) === 1) {
            $errors[] = new ManifestValidationError($field, 'Field "canonical" must not contain backslashes, whitespace or control characters.');
        }

        $hasPathOrQuery = false;
        $matches = [];
        preg_match_all(self::SUBSTITUTION, $canonical, $matches, \PREG_SET_ORDER);
        foreach ($matches as [$token, $space, $name]) {
            if ($space === 'host') {
                if ($hostNames !== null && !\in_array($name, $hostNames, true)) {
                    $errors[] = new ManifestValidationError($field, \sprintf('Substitution "%s" refers to a named group that "host" does not have.', $token));
                }

                continue;
            }

            if (str_contains($authority, $token)) {
                $errors[] = new ManifestValidationError($field, \sprintf('Substitution "%s" is not allowed in the host of "canonical": only "{host.<name>}" is.', $token));

                continue;
            }

            $hasPathOrQuery = true;
            if ($space === 'path') {
                if (preg_match('/^[1-9]\d*\z/', $name) !== 1 || ($pathCount !== null && (int) $name > $pathCount)) {
                    $errors[] = new ManifestValidationError($field, \sprintf('Substitution "%s" refers to a group that "path" does not have.', $token));
                }

                continue;
            }

            if (preg_match('/^(.+)\.([1-9]\d*)\z/', $name, $parts) !== 1) {
                $errors[] = new ManifestValidationError($field, \sprintf('Substitution "%s" must look like "{query.<parameter>.<group number>}".', $token));
            } elseif ($queryValid && (!isset($queryCounts[$parts[1]]) || (int) $parts[2] > $queryCounts[$parts[1]])) {
                $errors[] = new ManifestValidationError($field, \sprintf('Substitution "%s" refers to a query parameter or group that "query" does not have.', $token));
            }
        }

        $stripped = preg_replace(self::SUBSTITUTION, '', $canonical) ?? $canonical;
        if (preg_match('/[{}]/', $stripped) === 1) {
            $errors[] = new ManifestValidationError($field, 'Field "canonical" has a malformed substitution; allowed are "{path.N}", "{query.<parameter>.N}" and "{host.<name>}".');
        }

        if (!$hasPathOrQuery) {
            $errors[] = new ManifestValidationError($field, 'Field "canonical" must contain at least one "{path.N}" or "{query.<parameter>.N}" substitution; substitutions from "host" alone would merge every link of the site into one.');
        }

        // A host without substitutions is a literal: it has to be matched by the rule's own "host".
        if ($authoritySubstitutions === [] && $errors === [] && $hostPattern !== null) {
            if (@preg_match("\x01\\A(?:".$hostPattern.")\\z\x01u", $authority) !== 1) {
                $errors[] = new ManifestValidationError($field, \sprintf('Host "%s" of "canonical" is not matched by "host" of the same rule.', $authority));
            }
        }

        return $errors;
    }
}
