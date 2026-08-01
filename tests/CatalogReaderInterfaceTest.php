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

use AnimeDb\PluginContracts\AnimeId;
use AnimeDb\PluginContracts\AnimeView;
use AnimeDb\PluginContracts\CatalogReaderInterface;
use PHPUnit\Framework\TestCase;

class CatalogReaderInterfaceTest extends TestCase
{
    public function testReadReturnsViewForKnownRecord(): void
    {
        $view = new AnimeView(
            title: 'Cowboy Bebop',
            alternativeNames: [],
            type: null,
            genres: [],
            themes: [],
            episodesCount: null,
            sources: ['https://my-source.example/anime/1'],
            externalId: '1',
        );

        $catalog = new class($view) implements CatalogReaderInterface {
            public function __construct(
                private readonly AnimeView $view,
            ) {
            }

            public function read(AnimeId $anime): ?AnimeView
            {
                return $anime->value === 1 ? $this->view : null;
            }
        };

        self::assertSame($view, $catalog->read(new AnimeId(1)));
        self::assertNull($catalog->read(new AnimeId(2)));
    }
}
