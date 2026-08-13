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

use AnimeDb\PluginContracts\Filler\PluginAnimeData;
use AnimeDb\PluginContracts\Model\AnimeType;
use AnimeDb\PluginContracts\Model\Demographic;
use AnimeDb\PluginContracts\Model\GenreCode;
use AnimeDb\PluginContracts\Model\ThemeCode;
use PHPUnit\Framework\TestCase;

class PluginAnimeDataTest extends TestCase
{
    public function testOnlyTitleIsRequired(): void
    {
        $data = new PluginAnimeData('Cowboy Bebop');

        self::assertSame('Cowboy Bebop', $data->title);
        self::assertNull($data->alternativeNames);
        self::assertNull($data->descriptions);
        self::assertNull($data->genres);
        self::assertNull($data->themes);
        self::assertNull($data->demographic);
        self::assertNull($data->studios);
        self::assertNull($data->type);
        self::assertNull($data->datePremiere);
        self::assertNull($data->dateEnd);
        self::assertNull($data->durationMinutes);
        self::assertNull($data->episodesCount);
        self::assertNull($data->countries);
        self::assertNull($data->cover);
        self::assertNull($data->images);
    }

    public function testPropertiesReturnConstructorValues(): void
    {
        $datePremiere = new \DateTimeImmutable('1998-04-03');
        $dateEnd = new \DateTimeImmutable('1999-04-24');

        $data = new PluginAnimeData(
            title: 'Cowboy Bebop',
            alternativeNames: ['Kaubōi Bibappu'],
            descriptions: ['en' => 'A bounty hunting crew chases criminals across space.'],
            genres: [GenreCode::Action, GenreCode::SciFi],
            themes: [ThemeCode::Space, ThemeCode::AdultCast],
            demographic: Demographic::Seinen,
            studios: ['Sunrise'],
            type: AnimeType::Tv,
            datePremiere: $datePremiere,
            dateEnd: $dateEnd,
            durationMinutes: 24,
            episodesCount: 26,
            countries: ['JP'],
            cover: 'https://example.com/cover.jpg',
            images: ['https://example.com/1.jpg', 'https://example.com/2.jpg'],
        );

        self::assertSame('Cowboy Bebop', $data->title);
        self::assertSame(['Kaubōi Bibappu'], $data->alternativeNames);
        self::assertSame(['en' => 'A bounty hunting crew chases criminals across space.'], $data->descriptions);
        self::assertSame([GenreCode::Action, GenreCode::SciFi], $data->genres);
        self::assertSame([ThemeCode::Space, ThemeCode::AdultCast], $data->themes);
        self::assertSame(Demographic::Seinen, $data->demographic);
        self::assertSame(['Sunrise'], $data->studios);
        self::assertSame(AnimeType::Tv, $data->type);
        self::assertSame($datePremiere, $data->datePremiere);
        self::assertSame($dateEnd, $data->dateEnd);
        self::assertSame(24, $data->durationMinutes);
        self::assertSame(26, $data->episodesCount);
        self::assertSame(['JP'], $data->countries);
        self::assertSame('https://example.com/cover.jpg', $data->cover);
        self::assertSame(['https://example.com/1.jpg', 'https://example.com/2.jpg'], $data->images);
    }
}
