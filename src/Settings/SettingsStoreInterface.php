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

namespace AnimeDb\PluginContracts\Settings;

/**
 * Core-provided read/write access to a plugin's own settings — configuration
 * values as well as tokens/secrets a plugin's OAuth flow has obtained.
 *
 * A plugin obtains this service via constructor injection, type-hinting this
 * interface, the same way it obtains {@see \AnimeDb\PluginContracts\PluginData\PluginDataStoreInterface}.
 * The instance handed to a plugin is scoped to that plugin's own id — no
 * plugin id appears in the method signatures because the instance already
 * knows it, and a plugin cannot reach another plugin's settings through this
 * interface. This scoping is isolation from key collisions between plugins,
 * not a security boundary: a plugin is trusted code once installed, running
 * arbitrary PHP in the host process by design.
 *
 * `write()` expresses intent — "these are all my settings now" — not a
 * lifecycle step. There is deliberately no `flush()` here: how and when the
 * write actually lands is an implementation concern of the host application,
 * not something the contract dictates.
 */
interface SettingsStoreInterface
{
    /**
     * Read this plugin's stored settings.
     *
     * Returns an empty array if nothing has been stored yet.
     *
     * @return array<string, mixed>
     */
    public function read(): array;

    /**
     * Replace this plugin's stored settings with the given data and persist
     * it.
     *
     * An override, not a merge: keys previously stored that are absent from
     * $settings are removed. This is how a plugin revokes an OAuth token or
     * clears a field — by writing settings without that key.
     *
     * @param array<string, mixed> $settings
     */
    public function write(array $settings): void;
}
