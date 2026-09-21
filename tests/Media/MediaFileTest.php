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

use AnimeDb\PluginContracts\Media\MediaFile;
use PHPUnit\Framework\TestCase;

class MediaFileTest extends TestCase
{
    public function testPropertiesReturnConstructorValues(): void
    {
        $modifiedAt = new \DateTimeImmutable('2026-08-11T12:00:00+00:00');

        $file = new MediaFile(
            name: 'Cowboy Bebop - 01.mkv',
            relativePath: 'Season 1/Cowboy Bebop - 01.mkv',
            sizeBytes: 734003200,
            modifiedAt: $modifiedAt,
        );

        self::assertSame('Cowboy Bebop - 01.mkv', $file->name);
        self::assertSame('Season 1/Cowboy Bebop - 01.mkv', $file->relativePath);
        self::assertSame(734003200, $file->sizeBytes);
        self::assertSame($modifiedAt, $file->modifiedAt);
    }
}
