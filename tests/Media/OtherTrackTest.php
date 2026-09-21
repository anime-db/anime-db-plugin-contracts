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

use AnimeDb\PluginContracts\Media\OtherTrack;
use PHPUnit\Framework\TestCase;

class OtherTrackTest extends TestCase
{
    public function testPropertiesReturnConstructorValues(): void
    {
        $track = new OtherTrack(
            index: 3,
            type: 'attachment',
            codec: 'ttf',
        );

        self::assertSame(3, $track->index);
        self::assertSame('attachment', $track->type);
        self::assertSame('ttf', $track->codec);
    }

    public function testCodecIsNullWhenNotReported(): void
    {
        $track = new OtherTrack(
            index: 4,
            type: 'data',
            codec: null,
        );

        self::assertNull($track->codec);
    }
}
