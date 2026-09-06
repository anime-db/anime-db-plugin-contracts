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

use Composer\Semver\Constraint\Constraint;
use Composer\Semver\VersionParser;

/**
 * Validates decoded `manifest.json` data and reports every problem found, instead of
 * failing on the first one — both the market registry's CI and the host application's
 * client installer need to show a plugin author the full list of what to fix, not just
 * the first offending field.
 *
 * Operates on the raw associative array produced by decoding `manifest.json`, not on a
 * {@see Manifest} DTO: a {@see Manifest} can only be built from data that already passed
 * this validator (invalid values, e.g. an unknown `type`, cannot be represented by the
 * DTO's typed properties in the first place).
 */
final class ManifestValidator
{
    /**
     * `features` keys that name an integration-plugin role rather than a widget. Reserved
     * and rejected for type "local": a plugin whose code never talks to an external source
     * cannot fulfil the filler/sync/search role a caller would infer from these keys being
     * present. Every other `features` key is read as a widget name, which is legal for
     * "local" too.
     *
     * @var list<string>
     */
    private const LOCAL_DISALLOWED_FEATURE_KEYS = ['filler', 'sync', 'search'];

    private VersionParser $versionParser;

    public function __construct()
    {
        $this->versionParser = new VersionParser();
    }

    /**
     * @param array<string, mixed> $data decoded `manifest.json` content
     *
     * @return ManifestValidationError[]
     */
    public function validate(array $data): array
    {
        $errors = [];

        $errors = [...$errors, ...$this->validateId($data)];
        $errors = [...$errors, ...$this->validateRequiredString($data, 'name')];
        $errors = [...$errors, ...$this->validateVersion($data)];
        $errors = [...$errors, ...$this->validateTypeField($data)];

        $type = \is_string($data['type'] ?? null) ? PluginType::tryFrom($data['type']) : null;
        if ($type !== null) {
            $errors = [...$errors, ...$this->validateFeaturesOrLocales($data, $type)];
            $errors = [...$errors, ...$this->validateUi($data, $type)];
        }

        $errors = [...$errors, ...$this->validateRequire($data)];
        $errors = [...$errors, ...$this->validateOptionalString($data, 'description')];
        $errors = [...$errors, ...$this->validateOptionalString($data, 'author')];
        $errors = [...$errors, ...$this->validateUpdateUrl($data)];

        return $errors;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return ManifestValidationError[]
     */
    private function validateRequiredString(array $data, string $field): array
    {
        if (!array_key_exists($field, $data)) {
            return [new ManifestValidationError($field, \sprintf('Field "%s" is required.', $field))];
        }

        if (!\is_string($data[$field]) || $data[$field] === '') {
            return [new ManifestValidationError($field, \sprintf('Field "%s" must be a non-empty string.', $field))];
        }

        return [];
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return ManifestValidationError[]
     */
    private function validateId(array $data): array
    {
        $errors = $this->validateRequiredString($data, 'id');
        if ($errors !== []) {
            return $errors;
        }

        if (preg_match('/^[a-z0-9]+(-[a-z0-9]+)+$/', $data['id']) !== 1) {
            return [new ManifestValidationError(
                'id',
                \sprintf('"%s" is not a valid id. It must be a "vendor-name" slug: lowercase letters, digits and hyphen-separated segments (e.g. "vendor-name").', $data['id']),
            )];
        }

        return [];
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return ManifestValidationError[]
     */
    private function validateOptionalString(array $data, string $field): array
    {
        if (!array_key_exists($field, $data) || $data[$field] === null) {
            return [];
        }

        if (!\is_string($data[$field])) {
            return [new ManifestValidationError($field, \sprintf('Field "%s" must be a string.', $field))];
        }

        return [];
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return ManifestValidationError[]
     */
    private function validateVersion(array $data): array
    {
        $errors = $this->validateRequiredString($data, 'version');
        if ($errors !== []) {
            return $errors;
        }

        try {
            $this->versionParser->normalize($data['version']);
        } catch (\UnexpectedValueException) {
            return [new ManifestValidationError('version', \sprintf('"%s" is not a valid version.', $data['version']))];
        }

        return [];
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return ManifestValidationError[]
     */
    private function validateTypeField(array $data): array
    {
        if (!array_key_exists('type', $data)) {
            return [new ManifestValidationError('type', 'Field "type" is required.')];
        }

        if (!\is_string($data['type']) || PluginType::tryFrom($data['type']) === null) {
            $allowed = implode('", "', array_map(static fn (PluginType $case) => $case->value, PluginType::cases()));

            return [new ManifestValidationError('type', \sprintf('Field "type" must be one of "%s".', $allowed))];
        }

        return [];
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return ManifestValidationError[]
     */
    private function validateFeaturesOrLocales(array $data, PluginType $type): array
    {
        $errors = match ($type) {
            PluginType::Integration => $this->validateFeatures($data, $type, required: true),
            PluginType::Translation => array_key_exists('features', $data)
                ? [new ManifestValidationError('features', \sprintf('Field "features" is not allowed for type "%s".', $type->value))]
                : [],
            PluginType::Local => $this->validateFeatures($data, $type, required: false, disallowedKeys: self::LOCAL_DISALLOWED_FEATURE_KEYS),
        };

        $errors = [...$errors, ...$this->validateLocales($data, required: $type === PluginType::Translation)];

        return $errors;
    }

    /**
     * @param array<string, mixed> $data
     * @param list<string>         $disallowedKeys keys that must not appear in `features`, e.g. integration-only
     *                                             roles for type "local"
     *
     * @return ManifestValidationError[]
     */
    private function validateFeatures(array $data, PluginType $type, bool $required, array $disallowedKeys = []): array
    {
        if (!array_key_exists('features', $data)) {
            if ($required) {
                return [new ManifestValidationError('features', \sprintf('Field "features" is required for type "%s".', $type->value))];
            }

            return [];
        }

        $features = $data['features'];
        if (!\is_array($features) || array_is_list($features)) {
            return [new ManifestValidationError('features', 'Field "features" must be an object of boolean flags.')];
        }

        $errors = [];
        foreach ($features as $key => $value) {
            if (\in_array($key, $disallowedKeys, true)) {
                $errors[] = new ManifestValidationError(
                    \sprintf('features.%s', $key),
                    \sprintf('"%s" is an integration-plugin role and is not allowed for type "%s". Only widget names are allowed here.', $key, $type->value),
                );

                continue;
            }

            if (!\is_bool($value)) {
                $errors[] = new ManifestValidationError(\sprintf('features.%s', $key), 'Feature flag must be a boolean.');
            }
        }

        return $errors;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return ManifestValidationError[]
     */
    private function validateLocales(array $data, bool $required): array
    {
        if (!array_key_exists('locales', $data)) {
            if ($required) {
                return [new ManifestValidationError('locales', 'Field "locales" is required for type "translation".')];
            }

            return [];
        }

        $locales = $data['locales'];
        if (!\is_array($locales) || !array_is_list($locales)) {
            return [new ManifestValidationError('locales', 'Field "locales" must be a list of strings.')];
        }

        $errors = [];
        foreach ($locales as $index => $locale) {
            if (!\is_string($locale) || $locale === '') {
                $errors[] = new ManifestValidationError(\sprintf('locales.%d', $index), 'Locale code must be a non-empty string.');

                continue;
            }

            if (preg_match('/^[a-z]{2,3}$/', $locale) !== 1) {
                $errors[] = new ManifestValidationError(
                    \sprintf('locales.%d', $index),
                    \sprintf('"%s" is not a valid locale code. It must be a bare language subtag: two or three lowercase letters, without region or script (e.g. "en"). Three letters are only for languages without an ISO 639-1 two-letter code; use the two-letter code when one exists.', $locale),
                );
            }
        }

        return $errors;
    }

    /**
     * `ui` declares files the host itself inserts into the page shell when rendering this
     * plugin's UI — not allowed for {@see PluginType::Translation}, which ships no code and
     * renders no UI of its own.
     *
     * @param array<string, mixed> $data
     *
     * @return ManifestValidationError[]
     */
    private function validateUi(array $data, PluginType $type): array
    {
        if (!array_key_exists('ui', $data)) {
            return [];
        }

        if ($type === PluginType::Translation) {
            return [new ManifestValidationError('ui', \sprintf('Field "ui" is not allowed for type "%s".', $type->value))];
        }

        $ui = $data['ui'];
        if (!\is_array($ui) || ($ui !== [] && array_is_list($ui))) {
            return [new ManifestValidationError('ui', 'Field "ui" must be an object with "css" and/or "js" keys.')];
        }

        $unknownKeys = array_diff(array_keys($ui), ['css', 'js']);
        if ($unknownKeys !== []) {
            return [new ManifestValidationError(
                'ui',
                \sprintf('Field "ui" has unknown key(s): "%s". Only "css" and "js" are allowed.', implode('", "', array_map('strval', $unknownKeys))),
            )];
        }

        $cssErrors = $this->validateUiFileList($ui, 'css');
        $jsErrors = $this->validateUiFileList($ui, 'js');
        $errors = [...$cssErrors, ...$jsErrors];

        if ($cssErrors === [] && $jsErrors === []) {
            $css = \is_array($ui['css'] ?? null) ? $ui['css'] : [];
            $js = \is_array($ui['js'] ?? null) ? $ui['js'] : [];
            if ($css === [] && $js === []) {
                $errors[] = new ManifestValidationError('ui', 'Field "ui" must declare at least one file in "css" or "js".');
            }
        }

        return $errors;
    }

    /**
     * @param array<string, mixed> $ui
     *
     * @return ManifestValidationError[]
     */
    private function validateUiFileList(array $ui, string $key): array
    {
        if (!array_key_exists($key, $ui)) {
            return [];
        }

        $list = $ui[$key];
        if (!\is_array($list) || !array_is_list($list)) {
            return [new ManifestValidationError(\sprintf('ui.%s', $key), \sprintf('Field "ui.%s" must be a list of strings.', $key))];
        }

        $field = \sprintf('ui.%s', $key);
        $extension = '.'.$key;
        $errors = [];
        $seen = [];
        foreach ($list as $path) {
            if (!\is_string($path) || $path === '' || str_contains($path, "\0")) {
                $errors[] = new ManifestValidationError($field, \sprintf('Entries of "%s" must be non-empty strings without a NUL byte.', $field));

                continue;
            }

            if (!str_starts_with($path, 'assets/')) {
                $errors[] = new ManifestValidationError($field, \sprintf('"%s" must be a path under "assets/".', $path));

                continue;
            }

            $segments = explode('/', $path);
            if (str_starts_with($path, '/') || str_contains($path, '\\') || str_contains($path, ':')
                || \in_array('..', $segments, true) || \in_array('.', $segments, true) || \in_array('', $segments, true)
            ) {
                $errors[] = new ManifestValidationError($field, \sprintf('"%s" must be a relative path without "..", ".", empty segments, "\\" or ":".', $path));

                continue;
            }

            if (!str_ends_with($path, $extension)) {
                $errors[] = new ManifestValidationError($field, \sprintf('"%s" must end with "%s".', $path, $extension));

                continue;
            }

            if (\in_array($path, $seen, true)) {
                $errors[] = new ManifestValidationError($field, \sprintf('"%s" is listed more than once in "%s".', $path, $field));

                continue;
            }

            $seen[] = $path;
        }

        return $errors;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return ManifestValidationError[]
     */
    private function validateRequire(array $data): array
    {
        if (!array_key_exists('require', $data)) {
            return [new ManifestValidationError('require', 'Field "require" is required.')];
        }

        $require = $data['require'];
        if (!\is_array($require) || array_is_list($require)) {
            return [new ManifestValidationError('require', 'Field "require" must be an object.')];
        }

        $errors = [];
        $errors = [...$errors, ...$this->validateLowerBoundConstraint($require, 'require.core', 'core')];
        $errors = [...$errors, ...$this->validateLowerBoundConstraint($require, 'require.php', 'php')];
        $errors = [...$errors, ...$this->validatePluginContractsConstraint($require)];

        return $errors;
    }

    /**
     * @param array<string, mixed> $require
     *
     * @return ManifestValidationError[]
     */
    private function validateLowerBoundConstraint(array $require, string $dotPath, string $key): array
    {
        if (!array_key_exists($key, $require)) {
            return [new ManifestValidationError($dotPath, \sprintf('Field "%s" is required.', $dotPath))];
        }

        if (!\is_string($require[$key]) || $require[$key] === '') {
            return [new ManifestValidationError($dotPath, \sprintf('Field "%s" must be a non-empty string.', $dotPath))];
        }

        if (!$this->isLowerBoundOnlyConstraint($require[$key])) {
            return [new ManifestValidationError(
                $dotPath,
                \sprintf('Field "%s" must be a single ">=" lower-bound constraint (e.g. ">=2.0.0"), without an upper bound.', $dotPath),
            )];
        }

        return [];
    }

    /**
     * @param array<string, mixed> $require
     *
     * @return ManifestValidationError[]
     */
    private function validatePluginContractsConstraint(array $require): array
    {
        if (!array_key_exists('plugin-contracts', $require) || $require['plugin-contracts'] === null) {
            return [];
        }

        if (!\is_string($require['plugin-contracts']) || $require['plugin-contracts'] === '') {
            return [new ManifestValidationError('require.plugin-contracts', 'Field "require.plugin-contracts" must be a non-empty string.')];
        }

        try {
            $this->versionParser->parseConstraints($require['plugin-contracts']);
        } catch (\UnexpectedValueException) {
            return [new ManifestValidationError(
                'require.plugin-contracts',
                \sprintf('"%s" is not a valid version constraint.', $require['plugin-contracts']),
            )];
        }

        return [];
    }

    private function isLowerBoundOnlyConstraint(string $constraint): bool
    {
        try {
            $parsed = $this->versionParser->parseConstraints($constraint);
        } catch (\UnexpectedValueException) {
            return false;
        }

        return $parsed instanceof Constraint && $parsed->getOperator() === Constraint::STR_OP_GE;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return ManifestValidationError[]
     */
    private function validateUpdateUrl(array $data): array
    {
        if (!array_key_exists('update_url', $data) || $data['update_url'] === null) {
            return [];
        }

        if (!\is_string($data['update_url'])
            || filter_var($data['update_url'], \FILTER_VALIDATE_URL) === false
            || !\in_array(parse_url($data['update_url'], \PHP_URL_SCHEME), ['http', 'https'], true)
        ) {
            return [new ManifestValidationError('update_url', 'Field "update_url" must be a valid "http" or "https" URL.')];
        }

        return [];
    }
}
