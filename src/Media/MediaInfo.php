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
 * Technical characteristics of a single file, returned by
 * {@see MediaProbeInterface::probe()}/`probeAll()`.
 *
 * ## `null` means "not reported", not "unknown forever"
 *
 * Almost every field is nullable. `null` means only that the prober did not
 * report a value for this particular file (e.g. no overall bit rate for a
 * `matroska`/`mpegts` container, no profile for `theora`/`wmv2`, no channel
 * layout for `wmav2`) — it never means the value is zero, and it never
 * means the field is permanently unknowable for every file. A prober's raw
 * "no data" sentinels (`"0/0"`, `"N/A"`, `"unknown"`, `-99`, and similar)
 * must never reach this DTO as-is: normalizing them to `null` is the
 * responsibility of the core implementation that constructs `MediaInfo`,
 * not something this contract leaves to be reinterpreted by each caller.
 *
 * ## Four track lists, one invariant
 *
 * `$video`, `$audio`, `$subtitles` and `$otherTracks` partition every
 * stream the prober found: `$otherTracks` exists specifically so that
 * attachment streams (e.g. embedded fonts) and data streams are not
 * silently dropped by the three typed lists. The invariant this contract
 * requires of any implementation:
 *
 * `count($video) + count($audio) + count($subtitles) + count($otherTracks) === $trackCount`
 *
 * Silently losing a stream so that this sum falls short of `$trackCount`
 * is not acceptable — a user seeing "7 streams" against 5 actually listed
 * is exactly the failure this invariant rules out. The constructor
 * enforces it directly: constructing a `MediaInfo` where the four lists
 * do not add up to `$trackCount` throws `\InvalidArgumentException`.
 *
 * ## `$probeIdentity`
 *
 * The same value as a contemporaneous {@see MediaProbeInterface::probeIdentity()}
 * call, stamped onto every result so that a stored payload is
 * self-describing without a separate lookup. See
 * {@see MediaProbeInterface::probeIdentity()} for what this value is
 * required to mean.
 */
final class MediaInfo
{
    /**
     * @param VideoTrack[]    $video
     * @param AudioTrack[]    $audio
     * @param SubtitleTrack[] $subtitles
     * @param OtherTrack[]    $otherTracks
     */
    public function __construct(
        /**
         * Container format as reported by the prober, e.g. `'matroska,webm'`.
         */
        public readonly string $containerFormat,
        public readonly ?float $durationSeconds,
        public readonly int $sizeBytes,
        /**
         * Overall bit rate of the file in bits per second, if the container
         * reports one.
         */
        public readonly ?int $bitRate,
        /**
         * Total number of streams found in the file, across all four track
         * lists below. See the class docblock for the invariant this must
         * satisfy.
         */
        public readonly int $trackCount,
        public readonly array $video,
        public readonly array $audio,
        public readonly array $subtitles,
        public readonly array $otherTracks,
        public readonly string $probeIdentity,
    ) {
        $countedTracks = \count($video) + \count($audio) + \count($subtitles) + \count($otherTracks);
        if ($countedTracks !== $trackCount) {
            throw new \InvalidArgumentException(\sprintf('trackCount (%d) does not match the number of tracks across $video, $audio, $subtitles and $otherTracks (%d).', $trackCount, $countedTracks));
        }
    }
}
