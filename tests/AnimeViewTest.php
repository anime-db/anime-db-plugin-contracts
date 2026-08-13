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

use AnimeDb\PluginContracts\Catalog\AnimeView;
use AnimeDb\PluginContracts\Model\AnimeType;
use AnimeDb\PluginContracts\Model\GenreCode;
use AnimeDb\PluginContracts\Model\ThemeCode;
use PHPUnit\Framework\TestCase;

class AnimeViewTest extends TestCase
{
    public function testPropertiesReturnConstructorValues(): void
    {
        $view = new AnimeView(
            title: 'Cowboy Bebop',
            alternativeNames: ['Kaubōi Bibappu'],
            type: AnimeType::Tv,
            genres: [GenreCode::Action, GenreCode::SciFi],
            themes: [ThemeCode::Space, ThemeCode::AdultCast],
            episodesCount: 26,
            sources: ['https://my-source.example/anime/1'],
            externalId: '1',
        );

        self::assertSame('Cowboy Bebop', $view->title);
        self::assertSame(['Kaubōi Bibappu'], $view->alternativeNames);
        self::assertSame(AnimeType::Tv, $view->type);
        self::assertSame([GenreCode::Action, GenreCode::SciFi], $view->genres);
        self::assertSame([ThemeCode::Space, ThemeCode::AdultCast], $view->themes);
        self::assertSame(26, $view->episodesCount);
        self::assertSame(['https://my-source.example/anime/1'], $view->sources);
        self::assertSame('1', $view->externalId);
    }

    public function testUnknownFieldsUseEmptyListsAndNulls(): void
    {
        $view = new AnimeView(
            title: 'Cowboy Bebop',
            alternativeNames: [],
            type: null,
            genres: [],
            themes: [],
            episodesCount: null,
            sources: [],
            externalId: null,
        );

        self::assertSame([], $view->alternativeNames);
        self::assertNull($view->type);
        self::assertSame([], $view->genres);
        self::assertSame([], $view->themes);
        self::assertNull($view->episodesCount);
        self::assertSame([], $view->sources);
        self::assertNull($view->externalId);
    }
}
