<?php

declare(strict_types=1);

/**
 * AnimeDb package.
 *
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2026, Peter Gribanov
 * @license   https://gnu.org GPL-3.0-or-later
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
 * along with this program. If not, see <https://gnu.org>.
 */

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
        if (null !== $type) {
            $errors = [...$errors, ...$this->validateFeaturesOrLocales($data, $type)];
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

        if (!\is_string($data[$field]) || '' === $data[$field]) {
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
        if ([] !== $errors) {
            return $errors;
        }

        if (1 !== preg_match('/^[a-z0-9]+(-[a-z0-9]+)+$/', $data['id'])) {
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
        if (!array_key_exists($field, $data) || null === $data[$field]) {
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
        if ([] !== $errors) {
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

        if (!\is_string($data['type']) || null === PluginType::tryFrom($data['type'])) {
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
        $errors = [];
        $unexpectedField = PluginType::Integration === $type ? 'locales' : 'features';

        if (array_key_exists($unexpectedField, $data)) {
            $errors[] = new ManifestValidationError(
                $unexpectedField,
                \sprintf('Field "%s" is not allowed for type "%s".', $unexpectedField, $type->value),
            );
        }

        if (PluginType::Integration === $type) {
            $errors = [...$errors, ...$this->validateFeatures($data)];
        } else {
            $errors = [...$errors, ...$this->validateLocales($data)];
        }

        return $errors;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return ManifestValidationError[]
     */
    private function validateFeatures(array $data): array
    {
        if (!array_key_exists('features', $data)) {
            return [new ManifestValidationError('features', 'Field "features" is required for type "integration".')];
        }

        $features = $data['features'];
        if (!\is_array($features) || array_is_list($features)) {
            return [new ManifestValidationError('features', 'Field "features" must be an object of boolean flags.')];
        }

        $errors = [];
        foreach ($features as $key => $value) {
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
    private function validateLocales(array $data): array
    {
        if (!array_key_exists('locales', $data)) {
            return [new ManifestValidationError('locales', 'Field "locales" is required for type "translation".')];
        }

        $locales = $data['locales'];
        if (!\is_array($locales) || !array_is_list($locales)) {
            return [new ManifestValidationError('locales', 'Field "locales" must be a list of strings.')];
        }

        $errors = [];
        foreach ($locales as $index => $locale) {
            if (!\is_string($locale) || '' === $locale) {
                $errors[] = new ManifestValidationError(\sprintf('locales.%d', $index), 'Locale code must be a non-empty string.');
            }
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

        if (!\is_string($require[$key]) || '' === $require[$key]) {
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
        if (!array_key_exists('plugin-contracts', $require) || null === $require['plugin-contracts']) {
            return [];
        }

        if (!\is_string($require['plugin-contracts']) || '' === $require['plugin-contracts']) {
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

        return $parsed instanceof Constraint && Constraint::STR_OP_GE === $parsed->getOperator();
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return ManifestValidationError[]
     */
    private function validateUpdateUrl(array $data): array
    {
        if (!array_key_exists('update_url', $data) || null === $data['update_url']) {
            return [];
        }

        if (!\is_string($data['update_url'])
            || false === filter_var($data['update_url'], \FILTER_VALIDATE_URL)
            || !\in_array(parse_url($data['update_url'], \PHP_URL_SCHEME), ['http', 'https'], true)
        ) {
            return [new ManifestValidationError('update_url', 'Field "update_url" must be a valid "http" or "https" URL.')];
        }

        return [];
    }
}
