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

namespace AnimeDb\PluginContracts\Tests;

use AnimeDb\PluginContracts\Background\BackgroundTask;
use AnimeDb\PluginContracts\Model\AnimeId;
use PHPUnit\Framework\TestCase;

class BackgroundTaskTest extends TestCase
{
    public function testNameIsTheOnlyRequiredArgument(): void
    {
        $task = new BackgroundTask('fill-card');

        self::assertSame('fill-card', $task->name);
        self::assertNull($task->anime);
        self::assertSame([], $task->payload);
    }

    public function testCarriesAnimeIdAndPayload(): void
    {
        $anime = new AnimeId(42);

        $task = new BackgroundTask(
            name: 'fill-card',
            anime: $anime,
            payload: ['episode' => 12, 'path' => '/downloads/12.mkv'],
        );

        self::assertSame('fill-card', $task->name);
        self::assertSame($anime, $task->anime);
        self::assertSame(['episode' => 12, 'path' => '/downloads/12.mkv'], $task->payload);
    }
}
