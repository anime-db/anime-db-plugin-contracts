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
 * A stream inside {@see MediaInfo::$otherTracks} that is neither video,
 * audio, nor a subtitle stream — an attachment (e.g. an embedded font) or a
 * data stream, kept as a distinct, explicit fourth list rather than being
 * silently dropped.
 *
 * Without this list, {@see MediaInfo::$trackCount} would not equal the sum
 * of the other three lists' lengths whenever a file carries such a stream,
 * and a user would see e.g. "7 streams" against 5 actually shown ones. See
 * {@see MediaInfo} for the full invariant.
 */
final class OtherTrack
{
    public function __construct(
        /**
         * Position of this stream among all streams in the file, as
         * reported by the prober.
         */
        public readonly int $index,
        /**
         * Stream type as reported by the prober, e.g. `'attachment'` or
         * `'data'`. Not a closed dictionary: this list exists precisely to
         * avoid losing whatever the prober reports here.
         */
        public readonly string $type,
        public readonly ?string $codec,
    ) {
    }
}
