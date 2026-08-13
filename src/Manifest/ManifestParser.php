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
 * Parses a plugin's `manifest.json` into a strictly typed {@see Manifest} DTO.
 *
 * {@see self::parse()} decodes and validates the content itself, so it is safe to call
 * directly on untrusted input. {@see self::decode()} and {@see ManifestValidator::validate()}
 * remain public separately for callers (e.g. the client installer's UI) that need the full
 * structured error list up front, before deciding whether to call {@see self::parse()} at all.
 */
final class ManifestParser
{
    private ManifestValidator $validator;

    public function __construct()
    {
        $this->validator = new ManifestValidator();
    }

    /**
     * @throws InvalidManifestJsonException if `$json` is not syntactically valid JSON, or its
     *                                      top-level value is not a JSON object
     * @throws InvalidManifestException     if `$json` is syntactically valid JSON but its content
     *                                      fails {@see ManifestValidator} checks
     */
    public function parse(string $json): Manifest
    {
        $data = $this->decode($json);

        $errors = $this->validator->validate($data);
        if ($errors !== []) {
            throw new InvalidManifestException($errors);
        }

        return $this->buildManifest($data);
    }

    /**
     * Decode raw `manifest.json` content into an associative array, without validating its
     * content. Exposed separately for callers that need the raw array ahead of, or instead
     * of, calling {@see self::parse()} (e.g. to run {@see ManifestValidator} and show the
     * full error list before committing to building a {@see Manifest} DTO).
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

        if (!\is_array($data) || ($data !== [] && array_is_list($data))) {
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
            features: $type === PluginType::Integration ? $data['features'] : null,
            locales: $type === PluginType::Translation ? $data['locales'] : null,
            updateUrl: $data['update_url'] ?? null,
        );
    }
}
