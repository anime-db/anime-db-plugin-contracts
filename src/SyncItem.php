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
 * A single user list entry synced between the host application and an external source.
 *
 * Plain DTO used by {@see SyncInterface}, not a host application entity. Minimal field
 * set for a pilot integration; expect it to grow once the first real sync plugin
 * (e.g. Shikimori) is implemented.
 */
final class SyncItem
{
    public function __construct(
        /**
         * External id of the title on the source, as recognized by the plugin.
         */
        public readonly string $externalId,
        /**
         * Watch status of the title, normalized by the plugin from the source's own vocabulary.
         */
        public readonly SyncStatus $status,
        /**
         * Title of the anime, as known to the source.
         */
        public readonly string $title,
    ) {
    }
}
