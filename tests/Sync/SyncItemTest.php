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

namespace AnimeDb\PluginContracts\Tests\Sync;

use AnimeDb\PluginContracts\Sync\SyncItem;
use AnimeDb\PluginContracts\Sync\SyncStatus;
use PHPUnit\Framework\TestCase;

class SyncItemTest extends TestCase
{
    public function testUpdatedAtAndWatchedEpisodesDefaultToNull(): void
    {
        $item = new SyncItem(
            externalId: '1',
            status: SyncStatus::Watching,
            title: 'Cowboy Bebop',
        );

        self::assertNull($item->updatedAt);
        self::assertNull($item->watchedEpisodes);
    }

    public function testPropertiesReturnConstructorValues(): void
    {
        $updatedAt = new \DateTimeImmutable('2026-08-11T12:00:00+00:00');

        $item = new SyncItem(
            externalId: '1',
            status: SyncStatus::Watching,
            title: 'Cowboy Bebop',
            updatedAt: $updatedAt,
            watchedEpisodes: 12,
        );

        self::assertSame('1', $item->externalId);
        self::assertSame(SyncStatus::Watching, $item->status);
        self::assertSame('Cowboy Bebop', $item->title);
        self::assertSame($updatedAt, $item->updatedAt);
        self::assertSame(12, $item->watchedEpisodes);
    }
}
