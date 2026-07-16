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

namespace AnimeDb\PluginContracts;

/**
 * Base interface for all plugins.
 *
 * Common ancestor for all specialized plugin interfaces (Filler, Widget,
 * Search, Sync). Also acts as a marker/tag interface for a plugin registry:
 * a way to list all installed plugins regardless of which other interfaces
 * they implement.
 */
interface PluginInterface
{
    /**
     * Resolve this plugin's external id from a list of catalog record URLs.
     *
     * The plugin receives all external source URLs already attached to a
     * catalog record, parses them against its own vendor URL pattern, and
     * returns the id it recognizes, or null if none of the URLs belong to it.
     *
     * @param string[] $urls list of external source URLs already attached to the catalog record
     *
     * @return string|null external id resolved by this plugin from the URLs, or null if none matched
     */
    public function resolveExternalId(array $urls): ?string;
}
