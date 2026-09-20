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

namespace AnimeDb\PluginContracts\Media;

/**
 * Thrown by {@see MediaLibraryInterface::listFiles()} when a record's storage
 * path is known but currently unreachable (e.g. removable/network storage
 * that is temporarily offline).
 *
 * Distinct from an empty result: `listFiles()` returns an empty array when
 * a record has no storage folder at all or the folder is simply empty —
 * this exception is only for the case where the storage location is known
 * but cannot be read right now. A caller (e.g. a widget) is expected to
 * tell these apart, showing "files are unavailable here" rather than "no
 * files" when this is thrown.
 *
 * `listFiles()` does not attempt to repair the path when it is unreachable;
 * recovering a moved storage path remains the job of the background
 * storage scan.
 *
 * Deliberately has no base class in common with
 * {@see MediaProbeUnavailableException} or {@see MediaProbeFailedException}:
 * a caller is required to distinguish "storage is unreachable" from "no
 * prober is available" from "this one file could not be parsed", each of
 * which calls for a different response, not to catch all three the same
 * way.
 */
final class StorageUnavailableException extends \RuntimeException
{
}
