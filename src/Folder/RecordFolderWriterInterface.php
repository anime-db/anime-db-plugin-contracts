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

use AnimeDb\PluginContracts\Media\StorageUnavailableException;
use AnimeDb\PluginContracts\Model\AnimeId;

/**
 * Core-provided writing of files into a catalog record's own folder.
 *
 * The plugin passes a bare file name and bytes, never a path: the host finds
 * the record folder itself. No absolute path crosses this interface in either
 * direction. The plugin does not need to check paths, permissions or foreign
 * files — the host does it and guarantees the following.
 *
 * Access: the service is per-plugin scoped and issued only to plugins of
 * manifest type `local`. A plugin of any type other than `local` that requests it is not loaded.
 *
 * Host checks (stricter than reading via {@see \AnimeDb\PluginContracts\Media\MediaLibraryInterface}):
 *  - the storage must be writable (not an external read-only or video storage);
 *  - the storage marker must be strictly equal to the storage id, a missing
 *    marker is not forgiven;
 *  - the path after `realpath` must lie inside the storage root.
 *
 * File name rules for `$fileName` in write()/inspect():
 *  - no path separators and no `..`;
 *  - matches `[a-z0-9][a-z0-9._-]*`, no trailing dot;
 *  - Windows reserved names `con`, `prn`, `aux`, `nul`, `com1`-`com9`,
 *    `lpt1`-`lpt9` are rejected with any extension;
 *  - the extension is one of `html`, `jpg`, `jpeg`, `png`, `webp`;
 *  - a file is at most 5 MB.
 * Rules for `$baseName` in writeCover(): matches `[a-z0-9][a-z0-9_-]*`
 * (no dot, no extension — the host appends it), and the same Windows reserved
 * names are rejected. `writeCover($id, 'cover.jpg')` is therefore invalid.
 * A name that breaks a rule is rejected with {@see \InvalidArgumentException}.
 *
 * Atomicity: the host writes to a temporary file and renames it in the same folder.
 *
 * Foreign files are never overwritten. The host keeps a record
 * `(plugin, anime, filename) → sha256` of the content it wrote:
 *  - the file is absent → written;
 *  - the file exists and its sha256 equals the recorded one, or the sha256 of
 *    the new bytes (adoption) → written;
 *  - otherwise → {@see ForeignFileException}, the file is untouched;
 *  - if the sha256 of the file on disk equals the sha256 of the new bytes, the host does not
 *    touch the file and only updates its own record;
 *  - with `$overwrite = true` (an explicit user decision) the file is overwritten.
 * The record is removed when the plugin or the catalog record is removed;
 * files on disk stay.
 *
 * Storage errors, for every method: storage unreachable →
 * {@see StorageUnavailableException}; any refusal to write to the location
 * (read-only, marker missing or mismatched, path outside the root) →
 * {@see StorageNotWritableException}; the record has no folder of its own (its
 * path is a single file) → {@see RecordHasNoFolderException}.
 */
interface RecordFolderWriterInterface
{
    /**
     * Write `$contents` as `$fileName` into the record folder.
     *
     * @throws ForeignFileException        a different file with that name exists and `$overwrite` is false
     * @throws RecordHasNoFolderException
     * @throws StorageNotWritableException
     * @throws StorageUnavailableException
     * @throws \InvalidArgumentException   the name breaks the rules or the contents exceed 5 MB
     */
    public function write(AnimeId $anime, string $fileName, string $contents, bool $overwrite = false): void;

    /**
     * Copy the record's current cover into the record folder as
     * `<baseName>.<ext>` and return the resulting file name. `<ext>` is chosen
     * by the host from `jpg`, `png`, `webp` by the actual image format (JPEG is
     * always `jpg`, never `jpeg`); the host does not convert. Returns null and
     * writes nothing if the record has no cover, the cover is in another format,
     * or it is larger than 5 MB. The path of the image is not disclosed. The
     * same foreign-file rule as in write() applies.
     *
     * @throws ForeignFileException        a different file with that name exists and `$overwrite` is false
     * @throws RecordHasNoFolderException
     * @throws StorageNotWritableException
     * @throws StorageUnavailableException
     * @throws \InvalidArgumentException   `$baseName` breaks the rules
     */
    public function writeCover(AnimeId $anime, string $baseName, bool $overwrite = false): ?string;

    /**
     * State of `$fileName` in the record folder, without writing anything.
     * Folder problems are reported with the same exceptions as writing
     * (except {@see ForeignFileException}).
     *
     * @throws RecordHasNoFolderException
     * @throws StorageNotWritableException
     * @throws StorageUnavailableException
     * @throws \InvalidArgumentException   the name breaks the rules
     */
    public function inspect(AnimeId $anime, string $fileName): RecordFolderFileState;
}
