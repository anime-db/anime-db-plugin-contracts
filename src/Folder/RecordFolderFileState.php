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

/**
 * State of a file in the record folder relative to what the calling plugin
 * last wrote, returned by {@see RecordFolderWriterInterface::inspect()}.
 */
enum RecordFolderFileState: string
{
    /** The file does not exist. */
    case Absent = 'absent';
    /** The file exists and its sha256 equals the recorded one. */
    case Written = 'written';
    /** The file exists, but its sha256 differs from the recorded one or nothing is recorded. */
    case Modified = 'modified';
}
