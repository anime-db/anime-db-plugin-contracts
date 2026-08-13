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

namespace AnimeDb\PluginContracts\Sync;

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
        /**
         * Time of the last change of this list entry, as reported by the source.
         *
         * Record-level, not per-field: sources do not report a separate timestamp
         * for the moment the watch status or the episode count last changed, only
         * for the record as a whole. Nullable because a source/plugin that does not
         * expose any timestamp must not fabricate one (e.g. via `now()`) — that would
         * re-introduce a fabricated time as if it were authoritative. A `null` value
         * is compared by content instead and, in conflict arbitration, treated as the
         * oldest possible value.
         */
        public readonly ?\DateTimeImmutable $updatedAt = null,
        /**
         * Number of episodes watched.
         *
         * Forms one reconciliation unit together with {@see self::$status}: locally
         * the two are tightly coupled (the episode count derives the watch status
         * and vice versa), so they are never synced independently. Nullable because
         * movies and sources without episode-level granularity do not report it —
         * "does not report episodes" is not the same as "0 episodes watched".
         */
        public readonly ?int $watchedEpisodes = null,
    ) {
    }
}
