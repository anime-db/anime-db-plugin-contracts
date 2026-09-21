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

namespace AnimeDb\PluginContracts\Catalog;

/**
 * Why {@see AnimeFilesChangedEvent} was dispatched. Four distinct ways of arriving at
 * the same fact — the set or location of an entry's files on disk changed — for which
 * a subscriber does the same thing regardless of which one applies; a new reason is
 * added as a new case here, not as a new event a plugin would have to subscribe to
 * separately.
 */
enum FilesChangeReason: string
{
    case Created = 'created';
    case FilesAdded = 'files_added';
    case PathChanged = 'path_changed';
    case DownloadFinished = 'download_finished';
}
