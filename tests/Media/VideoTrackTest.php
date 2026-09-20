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

use AnimeDb\PluginContracts\Media\VideoTrack;
use PHPUnit\Framework\TestCase;

class VideoTrackTest extends TestCase
{
    public function testPropertiesReturnConstructorValues(): void
    {
        $track = new VideoTrack(
            index: 0,
            codec: 'h264',
            profile: 'High',
            width: 1920,
            height: 1080,
            pixelFormat: 'yuv420p',
            frameRate: 23.976,
            bitRate: 4500000,
        );

        self::assertSame(0, $track->index);
        self::assertSame('h264', $track->codec);
        self::assertSame('High', $track->profile);
        self::assertSame(1920, $track->width);
        self::assertSame(1080, $track->height);
        self::assertSame('yuv420p', $track->pixelFormat);
        self::assertSame(23.976, $track->frameRate);
        self::assertSame(4500000, $track->bitRate);
    }

    public function testUnreportedFieldsAreNull(): void
    {
        $track = new VideoTrack(
            index: 0,
            codec: 'theora',
            profile: null,
            width: null,
            height: null,
            pixelFormat: null,
            frameRate: null,
            bitRate: null,
        );

        self::assertNull($track->profile);
        self::assertNull($track->width);
        self::assertNull($track->height);
        self::assertNull($track->pixelFormat);
        self::assertNull($track->frameRate);
        self::assertNull($track->bitRate);
    }
}
