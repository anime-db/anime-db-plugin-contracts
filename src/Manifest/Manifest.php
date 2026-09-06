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
 * Strictly typed DTO for a plugin's `manifest.json`, the file every plugin ZIP carries
 * at its archive root.
 *
 * Built by {@see ManifestParser} from already-validated data (see {@see ManifestValidator}) —
 * consumers should never work with the raw decoded JSON array directly. `features` is only
 * meaningful for {@see PluginType::Integration}. `locales` — the languages of the translation
 * catalogs the plugin ships — is required for {@see PluginType::Translation} and optional for
 * {@see PluginType::Integration} and {@see PluginType::Local}. `ui` — see {@see PluginUi} — is
 * not allowed for {@see PluginType::Translation}.
 *
 * Also implements {@see OwnManifestInterface} — the narrow read-only view of
 * this data that the host injects into the plugin itself.
 */
final class Manifest implements OwnManifestInterface
{
    /**
     * `name`/`description` are self-sufficient literal display strings in the plugin's own
     * default language — NOT translation keys. The manifest is a standalone descriptor consumed
     * catalog-free: {@see ManifestParser}/{@see ManifestValidator}, the market registry build and
     * the pre-activation install UI all read it with no translation catalog or locale loaded, so a
     * key here would surface verbatim in the registry and UI. Localizable strings belong to the UI
     * class instead, resolved by the host in the plugin's `translations/` catalog (settings labels,
     * widget {@see \AnimeDb\PluginContracts\Widget\WidgetMetadata} `titleKey`/`descriptionKey`).
     *
     * @param array<string, bool>|null $features flat set of feature flags, only for {@see PluginType::Integration}
     * @param string[]|null            $locales  languages of the translation catalogs the plugin ships;
     *                                           required for {@see PluginType::Translation}, optional otherwise
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
        public readonly ?PluginUi $ui = null,
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
