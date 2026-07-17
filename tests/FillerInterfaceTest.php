<?php

declare(strict_types=1);

/**
 * AnimeDb package.
 *
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2026, Peter Gribanov
 * @license   https://gnu.org GPL-3.0-or-later
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
 * along with this program. If not, see <https://gnu.org>.
 */

namespace AnimeDb\PluginContracts\Tests;

use AnimeDb\PluginContracts\FillerInterface;
use AnimeDb\PluginContracts\PluginAnimeData;
use AnimeDb\PluginContracts\SearchByPluginCandidate;
use PHPUnit\Framework\TestCase;

class FillerInterfaceTest extends TestCase
{
    public function testFindByIdReturnsFilledData(): void
    {
        $plugin = new class implements FillerInterface {
            public function resolveExternalId(array $urls): ?string
            {
                return null;
            }

            public function find(string $name, ?callable $onHeartbeat = null): array
            {
                return [new SearchByPluginCandidate('my-plugin', $name, '1')];
            }

            public function findById(string $externalId): ?PluginAnimeData
            {
                return $externalId === '1' ? new PluginAnimeData('Cowboy Bebop') : null;
            }

            public function getFillableFields(): array
            {
                return ['title', 'genres'];
            }
        };

        $data = $plugin->findById('1');

        self::assertNotNull($data);
        self::assertSame('Cowboy Bebop', $data->title);
        self::assertNull($plugin->findById('unknown'));
        self::assertSame(['title', 'genres'], $plugin->getFillableFields());
    }
}
