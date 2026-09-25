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
 * Core-provided access to the technical characteristics of files listed by
 * {@see MediaLibraryInterface::listFiles()}.
 *
 * A plugin obtains this service via constructor injection, type-hinting
 * this interface, the same way it obtains {@see MediaLibraryInterface}.
 * A well-behaved plugin only ever probes a {@see MediaFile} produced by
 * `listFiles()`, but `MediaFile` is a plain, publicly constructible DTO —
 * this contract does not by itself stop a caller from fabricating one
 * with an arbitrary path and passing it here. An implementation accepts
 * only handles it issued itself; see the {@see MediaFile} docblock for
 * the provenance rule and what it means for background task handlers.
 *
 * There is deliberately no `isAvailable()`/`capabilities()` pair: a plugin
 * cannot reach a prober any other way (executing external processes itself
 * is forbidden), so the only path to a prober is this service, and the
 * only failure mode worth having is an exception — a separate availability
 * check would only add a check-then-use race without removing the need for
 * that exception.
 */
interface MediaProbeInterface
{
    /**
     * Probe a single file's technical characteristics.
     *
     * @throws MediaProbeUnavailableException if no prober is available at all — disabled
     *                                        in the host application's settings, or not installed
     * @throws MediaProbeFailedException      if this specific file could not be parsed — a
     *                                        timeout, a corrupt or zero-byte file, or a file that is
     *                                        still being written and only partially readable — or
     *                                        if the handle was not issued by this implementation
     *                                        (see {@see MediaFile}), checked before any disk access
     */
    public function probe(MediaFile $file): MediaInfo;

    /**
     * Probe several files in one call.
     *
     * Exists so an implementation has room to batch or hold a worker pool
     * instead of being forced to handle N independent, uncoordinated calls
     * when {@see probe()} is called once per file: probing a folder of 26
     * files can be seconds in a single call, minutes across 200 separate
     * ones.
     *
     * All files of one call MUST belong to the same catalog record: the
     * result is keyed by {@see MediaFile::$relativePath}, and files of two
     * records with equal relative paths would collapse into one key. The
     * implementation knows the record of every issued handle and enforces
     * this: a call mixing records is rejected as a whole with
     * {@see MediaProbeFailedException}, before any disk access.
     *
     * @param MediaFile[] $files
     *
     * @return array<string, MediaInfo> results keyed by the matching input file's
     *                                  {@see MediaFile::$relativePath} — a file that could
     *                                  not be parsed is simply absent from the result rather
     *                                  than represented as an error entry, so the caller
     *                                  tells success from failure by checking whether a
     *                                  given file's key is present, not by comparing lengths
     *
     * @throws MediaProbeUnavailableException if no prober is available at all
     * @throws MediaProbeFailedException      only if none of the given files could be
     *                                        parsed; if at least one succeeded, this returns
     *                                        whatever did succeed instead of throwing — a single
     *                                        damaged file must not destroy the data probed for
     *                                        every other file in the same call; also thrown, for
     *                                        the whole call and with no partial result, if any
     *                                        handle was not issued by this implementation (see
     *                                        {@see MediaFile}) or the handles belong to
     *                                        different records — checked before any disk access
     */
    public function probeAll(array $files): array;

    /**
     * A cheap readout of the prober's current identity.
     *
     * A plugin compares this value against the one stored in its own cached
     * payload (see {@see MediaInfo::$probeIdentity}) to decide whether that
     * cache needs to be refreshed, without having to probe a file just to
     * find out. "Cheap" means the call neither runs the prober nor reads
     * the probed files — if finding out the current identity required
     * probing, the plugin would already have fresh data and the cache check
     * would be pointless. It does not mean the disk is never touched: an
     * implementation may read the prober file itself (e.g. to notice the
     * user replaced it). Such a read may be memoized only keyed by the
     * prober file's (path, mtime, size) and recomputed when any of them
     * changes, so a long-lived process (a background worker) still notices
     * a replaced prober.
     *
     * What exactly goes into the string is an implementation decision, but
     * this contract requires one property of it: the value must change if
     * and only if the prober's observable output would change. Too coarse a
     * value (e.g. the upstream prober's version string) fails to invalidate
     * a cache when the set of supported codecs changes without a version
     * bump; too fine a value (e.g. the modification time or size of the
     * prober file alone) invalidates the entire cache on an application
     * reinstall even though the prober's output has not changed. A hash of
     * the prober file's content is closer to the right granularity: it is
     * stable across reinstalls and restoring a backup on another machine,
     * though it is still only an approximation (it changes on a rebuild
     * that does not alter the output and does not see codecs or libraries
     * living outside that file).
     */
    public function probeIdentity(): string;
}
