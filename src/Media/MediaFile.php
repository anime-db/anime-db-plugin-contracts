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
 * Immutable handle to a single file inside a catalog record's storage
 * folder, produced by {@see MediaLibraryInterface::listFiles()} and
 * accepted by {@see MediaProbeInterface::probe()}/`probeAll()`.
 *
 * Carries no absolute filesystem path: the storage location is an
 * implementation detail of the host application, not something this
 * contract exposes. `listFiles()` only ever returns instances whose
 * `$relativePath` stays inside the record's storage folder, but this is a
 * plain, publicly constructible DTO — nothing in the type system stops a
 * caller from building one with an arbitrary `$relativePath` (e.g.
 * `'../../../etc/passwd'`) and passing it to `probe()`/`probeAll()`.
 *
 * An implementation of {@see MediaProbeInterface} does not try to validate
 * the path: `probe()` receives no record id, so it has nothing to
 * canonicalize the path against. Instead it relies on provenance — it
 * accepts only handles that it issued itself from `listFiles()`, in the
 * same process and within the same call chain. A handle it did not issue
 * is rejected with {@see MediaProbeFailedException} before any disk
 * access, so a crafted `$relativePath` never becomes a filesystem path.
 *
 * This is the contract's behaviour, not a defect of the host: a
 * `MediaFile` fabricated by a plugin, or rebuilt by a plugin from its own
 * cache or from a background task payload, is rejected even if its
 * `$relativePath` is correct. The widget and the background task run in
 * different processes, and handles issued in one are not valid in the
 * other; therefore a background task handler MUST call `listFiles()`
 * itself before `probe()`/`probeAll()` and use the handles it got back.
 *
 * `$relativePath` is the stable identifier for matching a file against
 * a plugin's own cached payload across calls, since the host does not
 * expose any other per-file id.
 *
 * `$sizeBytes` and `$modifiedAt` are required, not incidental metadata:
 * probing a file that is still being written can succeed and return
 * plausible-looking but incomplete data, without raising any exception.
 * This is the dominant failure mode for freshly downloaded files, and the
 * only way a caller can catch it is by comparing a previously cached
 * `$sizeBytes`/`$modifiedAt` against the current one before trusting a
 * cached probe result.
 */
final class MediaFile
{
    public function __construct(
        /**
         * File name for display.
         */
        public readonly string $name,
        /**
         * Position of the file inside the record's storage folder, stable
         * across calls and usable as a cache key.
         */
        public readonly string $relativePath,
        public readonly int $sizeBytes,
        public readonly \DateTimeImmutable $modifiedAt,
    ) {
    }
}
