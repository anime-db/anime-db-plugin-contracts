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
 * A single video stream inside {@see MediaInfo::$video}.
 *
 * Every field but `$index` and `$codec` is nullable: a real-world prober
 * does not always have a value to report (e.g. no profile for `theora` or
 * `wmv2`, no bit rate for a stream inside a `matroska`/`mpegts` container
 * that only carries an overall file bit rate). `null` means "the prober did
 * not report this", never "zero" or "permanently unknown" — see
 * {@see MediaInfo} for the full explanation of this convention.
 */
final class VideoTrack
{
    public function __construct(
        /**
         * Position of this stream among all streams in the file, as
         * reported by the prober.
         */
        public readonly int $index,
        public readonly string $codec,
        public readonly ?string $profile,
        public readonly ?int $width,
        public readonly ?int $height,
        public readonly ?string $pixelFormat,
        public readonly ?float $frameRate,
        public readonly ?int $bitRate,
    ) {
    }
}
