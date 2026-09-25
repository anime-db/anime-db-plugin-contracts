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
 * Thrown by {@see MediaProbeInterface::probe()}/`probeAll()` when a
 * {@see MediaFile} was not issued by {@see MediaLibraryInterface::listFiles()}
 * of this implementation in the current process (fabricated, rebuilt from a
 * cache or a background task payload, or issued in another process), or when
 * one `probeAll()` call mixes handles of different records.
 *
 * A programmer error, not a runtime condition: the plugin must fix its code
 * (call `listFiles()` first and use the returned handles) rather than retry
 * or mark the file as corrupt. Deliberately extends \LogicException, so it
 * shares no base class with {@see MediaProbeFailedException}.
 */
final class ForeignMediaFileException extends \LogicException
{
}
