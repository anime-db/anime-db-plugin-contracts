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

namespace AnimeDb\PluginContracts\Widget;

/**
 * Pending-update marker for widget output.
 *
 * A widget whose `render()` has nothing to show yet (e.g. its background
 * work has not finished) prefixes the returned HTML with {@see self::MARKER}
 * so the host does not cache that one response. See the `render()` docblocks
 * of {@see EntryWidgetInterface} and {@see CatalogWidgetInterface} for the
 * exact semantics.
 */
final class WidgetPendingUpdate
{
    /**
     * Marker string, exactly 36 bytes, compared byte-for-byte as a prefix
     * of the returned HTML.
     */
    public const MARKER = '<!--animedb:widget-pending-update-->';

    private function __construct()
    {
    }

    /**
     * Return the HTML prefixed with {@see self::MARKER}.
     *
     * Rules out the one failure mode of the mechanism: stray whitespace, a
     * line break or a BOM before the marker, which makes the marker
     * unrecognised. Concatenating by hand is still allowed.
     */
    public static function mark(string $html): string
    {
        return self::MARKER.$html;
    }
}
