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

namespace AnimeDb\PluginContracts\Folder;

use AnimeDb\PluginContracts\Model\AnimeId;

/**
 * Core-provided enumeration of catalog records that have a folder of their own.
 *
 * Access: the service is per-plugin scoped and issued only to plugins of manifest type
 * `local`, by the same rule as {@see RecordFolderWriterInterface}.
 *
 * The index works with the database only and never touches the disk. It does not filter by
 * storage availability: a record whose storage is currently unreachable is returned too,
 * so that it does not silently drop out of a bulk run. Availability is checked per record
 * by {@see RecordFolderWriterInterface}, which reports it with
 * {@see \AnimeDb\PluginContracts\Media\StorageUnavailableException}. Records on a storage
 * whose type does not allow writing (CD/DVD, video) are not returned.
 */
interface RecordFolderIndexInterface
{
    /**
     * Records that have a storage set and a non-empty path in it, on a storage whose type
     * allows writing. Ordered by ascending id, strictly after `$after` (null means from the
     * start), at most `$limit` of them. The cursor is an id, not an offset, so removing a
     * record between two calls does not shift it.
     *
     * `$after` is the id of the last record of the previous page. `$limit` must be at
     * least 1, otherwise \InvalidArgumentException is thrown. The end of the walk is a
     * page with fewer than `$limit` records (including an empty list).
     *
     * @return list<AnimeId>
     *
     * @throws \InvalidArgumentException if `$limit` is less than 1
     */
    public function recordsWithFolder(?AnimeId $after, int $limit): array;
}
