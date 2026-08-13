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
 * Core-provided read-only access to a plugin's own manifest identity: its
 * own id, name and version.
 *
 * A plugin obtains this service via constructor injection, type-hinting this
 * interface, the same way it obtains {@see \AnimeDb\PluginContracts\Settings\SettingsStoreInterface}
 * and {@see \AnimeDb\PluginContracts\PluginData\PluginDataStoreInterface}.
 * The instance handed to a plugin is scoped to that plugin's own id — no
 * plugin id appears in the method signatures because the instance already
 * knows it.
 *
 * This is deliberately narrower than {@see Manifest} itself: it exposes only
 * the identity a plugin has a real use for at runtime (for example, building
 * a `User-Agent` string from its own id/version), not `require`/`features`/
 * `locales`/`updateUrl` — fields a plugin already declared itself and has no
 * reason to read back, and which are host/registry concerns. It is also
 * unrelated to {@see ManifestParser}, which parses arbitrary/foreign
 * manifests (used by the plugin registry's CI and by the host's installer) —
 * this interface is a plugin's read-only view of its own manifest.
 */
interface OwnManifestInterface
{
    /**
     * This plugin's own id, as declared in its manifest.
     */
    public function id(): string;

    /**
     * This plugin's own name, as declared in its manifest.
     */
    public function name(): string;

    /**
     * This plugin's own version, as declared in its manifest.
     */
    public function version(): string;
}
