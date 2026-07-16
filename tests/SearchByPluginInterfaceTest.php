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

use AnimeDb\PluginContracts\SearchByPluginCandidate;
use AnimeDb\PluginContracts\SearchByPluginInterface;
use PHPUnit\Framework\TestCase;

class SearchByPluginInterfaceTest extends TestCase
{
    public function testOnHeartbeatIsCalledForEachPage(): void
    {
        $plugin = new class implements SearchByPluginInterface {
            public function resolveExternalId(array $urls): ?string
            {
                return null;
            }

            public function find(string $name, ?callable $onHeartbeat = null): array
            {
                $candidates = [];
                foreach (['1', '2'] as $page) {
                    if ($onHeartbeat !== null) {
                        $onHeartbeat();
                    }
                    $candidates[] = new SearchByPluginCandidate('my-plugin', $name, $page);
                }

                return $candidates;
            }
        };

        $calls = 0;
        $result = $plugin->find('Cowboy Bebop', function () use (&$calls): void {
            ++$calls;
        });

        self::assertSame(2, $calls);
        self::assertCount(2, $result);
    }

    public function testOnHeartbeatIsOptional(): void
    {
        $plugin = new class implements SearchByPluginInterface {
            public function resolveExternalId(array $urls): ?string
            {
                return null;
            }

            public function find(string $name, ?callable $onHeartbeat = null): array
            {
                if ($onHeartbeat !== null) {
                    $onHeartbeat();
                }

                return [new SearchByPluginCandidate('my-plugin', $name, '1')];
            }
        };

        $result = $plugin->find('Cowboy Bebop');

        self::assertCount(1, $result);
    }
}
