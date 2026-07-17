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

/**
 * Parses a plugin's `manifest.json` into a strictly typed {@see Manifest} DTO.
 *
 * Callers are expected to run {@see ManifestValidator::validate()} on {@see self::decode()}'s
 * result first and only call {@see self::parse()} once it reports no errors — {@see parse()}
 * itself only guards against malformed JSON syntax (via {@see InvalidManifestJsonException}),
 * it does not re-validate manifest content and trusts the required fields to be present and
 * well-formed.
 */
final class ManifestParser
{
    /**
     * @throws InvalidManifestJsonException if `$json` is not syntactically valid JSON, or its
     *                                      top-level value is not a JSON object
     */
    public function parse(string $json): Manifest
    {
        return $this->buildManifest($this->decode($json));
    }

    /**
     * Decode raw `manifest.json` content into an associative array, without validating its
     * content. Exposed separately so {@see ManifestValidator} can be run on the same raw data
     * before {@see self::parse()} is trusted to build a DTO from it.
     *
     * @return array<string, mixed>
     *
     * @throws InvalidManifestJsonException if `$json` is not syntactically valid JSON, or its
     *                                      top-level value is not a JSON object
     */
    public function decode(string $json): array
    {
        try {
            $data = json_decode($json, true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new InvalidManifestJsonException('manifest.json is not valid JSON: '.$exception->getMessage(), previous: $exception);
        }

        if (!\is_array($data) || array_is_list($data)) {
            throw new InvalidManifestJsonException('manifest.json must contain a JSON object at the top level.');
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $data decoded manifest content, already validated by {@see ManifestValidator}
     */
    private function buildManifest(array $data): Manifest
    {
        $type = PluginType::from($data['type']);
        $require = $data['require'];

        return new Manifest(
            id: $data['id'],
            name: $data['name'],
            version: $data['version'],
            type: $type,
            require: new ManifestRequirements(
                core: $require['core'],
                php: $require['php'],
                pluginContracts: $require['plugin-contracts'] ?? null,
            ),
            description: $data['description'] ?? null,
            author: $data['author'] ?? null,
            features: PluginType::Integration === $type ? $data['features'] : null,
            locales: PluginType::Translation === $type ? $data['locales'] : null,
            updateUrl: $data['update_url'] ?? null,
        );
    }
}
