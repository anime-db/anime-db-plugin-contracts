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

namespace AnimeDb\PluginContracts\Tests\Media;

use AnimeDb\PluginContracts\Media\AudioTrack;
use PHPUnit\Framework\TestCase;

class AudioTrackTest extends TestCase
{
    public function testPropertiesReturnConstructorValues(): void
    {
        $track = new AudioTrack(
            index: 1,
            codec: 'aac',
            channels: 2,
            channelLayout: 'stereo',
            sampleRate: 48000,
            bitRate: 192000,
            language: 'jpn',
            title: 'Original',
            isDefault: true,
        );

        self::assertSame(1, $track->index);
        self::assertSame('aac', $track->codec);
        self::assertSame(2, $track->channels);
        self::assertSame('stereo', $track->channelLayout);
        self::assertSame(48000, $track->sampleRate);
        self::assertSame(192000, $track->bitRate);
        self::assertSame('jpn', $track->language);
        self::assertSame('Original', $track->title);
        self::assertTrue($track->isDefault);
    }

    public function testUnreportedFieldsAreNull(): void
    {
        $track = new AudioTrack(
            index: 1,
            codec: 'wmav2',
            channels: null,
            channelLayout: null,
            sampleRate: null,
            bitRate: null,
            language: null,
            title: null,
            isDefault: false,
        );

        self::assertNull($track->channels);
        self::assertNull($track->channelLayout);
        self::assertNull($track->sampleRate);
        self::assertNull($track->bitRate);
        self::assertNull($track->language);
        self::assertNull($track->title);
        self::assertFalse($track->isDefault);
    }
}
