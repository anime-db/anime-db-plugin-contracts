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
 * A single audio stream inside {@see MediaInfo::$audio}.
 *
 * `$language`, `$title` and `$isDefault` are not incidental — without them
 * a "which tracks does this file have" widget has nothing to show the
 * user beyond a bare count. `$language` is copied from the file as-is,
 * with no normalization to a canonical code (e.g. ISO 639) — that mapping
 * is left to whoever renders the value.
 *
 * The remaining fields are nullable: real files omit an overall bit rate
 * for some containers, and codecs such as `wmav2` do not report a channel
 * layout. `null` means "the prober did not report this" — see
 * {@see MediaInfo} for the full explanation of this convention.
 */
final class AudioTrack
{
    public function __construct(
        /**
         * Position of this stream among all streams in the file, as
         * reported by the prober.
         */
        public readonly int $index,
        public readonly string $codec,
        public readonly ?int $channels,
        public readonly ?string $channelLayout,
        public readonly ?int $sampleRate,
        public readonly ?int $bitRate,
        /**
         * Language as recorded in the file, with no normalization applied.
         */
        public readonly ?string $language,
        public readonly ?string $title,
        public readonly bool $isDefault,
    ) {
    }
}
