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

use AnimeDb\PluginContracts\Model\AnimeId;

/**
 * Core-provided access to the list of files currently present in a catalog
 * record's storage folder, for plugins that need to see what is actually
 * on disk — e.g. a widget listing episode files, or a filler enriching a
 * card from downloaded content.
 *
 * A plugin obtains this service via constructor injection, type-hinting
 * this interface, the same way it obtains
 * {@see \AnimeDb\PluginContracts\Catalog\CatalogReaderInterface} or
 * {@see MediaProbeInterface}.
 *
 * A separate interface from {@see MediaProbeInterface} rather than a second
 * method on the same one: a plugin enriching a card from folder contents
 * needs the file list and has no use for a prober, and this package's own
 * convention is one interface per feature, not mixed feature logic.
 *
 * The set of rules for what counts as a media file (extensions, recursion
 * into subfolders, etc.) lives entirely in the host application's
 * implementation of this interface; this contract deliberately does not
 * describe those rules, only that they are applied consistently by a
 * single implementation instead of being reimplemented by every plugin.
 */
interface MediaLibraryInterface
{
    /**
     * List the files currently present in the given record's storage
     * folder, as they are on disk right now.
     *
     * Does not repair a stale or moved storage path: this walks from the
     * last known storage path and reports what it finds there. Recovering
     * a moved storage path remains the job of the background storage scan,
     * not of this call.
     *
     * Returns an empty array both when the record has no storage folder at
     * all and when the folder exists but is empty — those are the same
     * "nothing to list" outcome. This is distinct from "the storage
     * location is known but currently unreachable", which is reported as
     * {@see StorageUnavailableException} instead. A caller that wants to
     * tell "no files" apart from "files are unavailable right now" (e.g. a
     * widget deciding what message to show) must catch that exception, not
     * infer the distinction from an empty array.
     *
     * @return MediaFile[]
     *
     * @throws StorageUnavailableException if the record's storage path is known but
     *                                     currently unreachable
     */
    public function listFiles(AnimeId $anime): array;
}
