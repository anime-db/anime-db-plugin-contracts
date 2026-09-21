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

use AnimeDb\PluginContracts\Media\SubtitleTrack;
use PHPUnit\Framework\TestCase;

class SubtitleTrackTest extends TestCase
{
    public function testPropertiesReturnConstructorValues(): void
    {
        $track = new SubtitleTrack(
            index: 2,
            codec: 'ass',
            language: 'eng',
            title: 'English (Full)',
            isDefault: true,
            isForced: false,
        );

        self::assertSame(2, $track->index);
        self::assertSame('ass', $track->codec);
        self::assertSame('eng', $track->language);
        self::assertSame('English (Full)', $track->title);
        self::assertTrue($track->isDefault);
        self::assertFalse($track->isForced);
    }

    public function testUnreportedFieldsAreNull(): void
    {
        $track = new SubtitleTrack(
            index: 2,
            codec: 'subrip',
            language: null,
            title: null,
            isDefault: false,
            isForced: false,
        );

        self::assertNull($track->language);
        self::assertNull($track->title);
    }
}
