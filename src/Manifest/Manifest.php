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
 * Strictly typed DTO for a plugin's `manifest.json`, the file every plugin ZIP carries
 * at its archive root.
 *
 * Built by {@see ManifestParser} from already-validated data (see {@see ManifestValidator}) —
 * consumers should never work with the raw decoded JSON array directly. `features` is only
 * meaningful for {@see PluginType::Integration}, `locales` only for {@see PluginType::Translation}.
 * {@see PluginType::Local} declares neither.
 *
 * Also implements {@see OwnManifestInterface} — the narrow read-only view of
 * this data that the host injects into the plugin itself.
 */
final class Manifest implements OwnManifestInterface
{
    /**
     * @param array<string, bool>|null $features flat set of feature flags, only for {@see PluginType::Integration}
     * @param string[]|null            $locales  list of provided locale codes, only for {@see PluginType::Translation}
     */
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $version,
        public readonly PluginType $type,
        public readonly ManifestRequirements $require,
        public readonly ?string $description = null,
        public readonly ?string $author = null,
        public readonly ?array $features = null,
        public readonly ?array $locales = null,
        public readonly ?string $updateUrl = null,
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function version(): string
    {
        return $this->version;
    }
}
