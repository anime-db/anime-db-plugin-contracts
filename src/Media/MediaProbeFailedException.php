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
 * Thrown by {@see MediaProbeInterface::probe()} when a prober is available
 * but this specific file could not be parsed — a timeout, a corrupt or
 * zero-byte file, or a file that is still being written and only partially
 * readable.
 *
 * From {@see MediaProbeInterface::probeAll()}, this is only thrown when
 * none of the given files could be parsed; a partial failure among several
 * files does not throw this — the failed file's
 * {@see MediaFile::$relativePath} is simply absent as a key in the returned
 * result. A single damaged file in a folder must not destroy the data
 * probed for every other file in the same call.
 *
 * Deliberately distinct from {@see MediaProbeUnavailableException}: this
 * one means the capability exists but this file defeated it, that one means
 * the capability itself is absent. Deliberately has no base class in common
 * with either that exception or {@see StorageUnavailableException} — see
 * {@see StorageUnavailableException} for why.
 */
final class MediaProbeFailedException extends \RuntimeException
{
}
