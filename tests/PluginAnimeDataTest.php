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

use AnimeDb\PluginContracts\PluginAnimeData;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class PluginAnimeDataTest extends TestCase
{
    public function testOnlyTitleIsRequired(): void
    {
        $data = new PluginAnimeData('Cowboy Bebop');

        self::assertSame('Cowboy Bebop', $data->getTitle());
        self::assertNull($data->getAlternativeNames());
        self::assertNull($data->getDescriptions());
        self::assertNull($data->getGenres());
        self::assertNull($data->getThemes());
        self::assertNull($data->getDemographic());
        self::assertNull($data->getStudios());
        self::assertNull($data->getType());
        self::assertNull($data->getDatePremiere());
        self::assertNull($data->getDateEnd());
        self::assertNull($data->getDurationMinutes());
        self::assertNull($data->getEpisodesCount());
        self::assertNull($data->getCountries());
        self::assertNull($data->getCover());
        self::assertNull($data->getImages());
    }

    public function testGettersReturnConstructorValues(): void
    {
        $datePremiere = new DateTimeImmutable('1998-04-03');
        $dateEnd = new DateTimeImmutable('1999-04-24');

        $data = new PluginAnimeData(
            title: 'Cowboy Bebop',
            alternativeNames: ['Kaubōi Bibappu'],
            descriptions: ['A bounty hunting crew chases criminals across space.'],
            genres: ['action', 'sci-fi'],
            themes: ['space', 'adult-cast'],
            demographic: 'seinen',
            studios: ['Sunrise'],
            type: 'tv',
            datePremiere: $datePremiere,
            dateEnd: $dateEnd,
            durationMinutes: 24,
            episodesCount: 26,
            countries: ['JP'],
            cover: 'https://example.com/cover.jpg',
            images: ['https://example.com/1.jpg', 'https://example.com/2.jpg'],
        );

        self::assertSame('Cowboy Bebop', $data->getTitle());
        self::assertSame(['Kaubōi Bibappu'], $data->getAlternativeNames());
        self::assertSame(['A bounty hunting crew chases criminals across space.'], $data->getDescriptions());
        self::assertSame(['action', 'sci-fi'], $data->getGenres());
        self::assertSame(['space', 'adult-cast'], $data->getThemes());
        self::assertSame('seinen', $data->getDemographic());
        self::assertSame(['Sunrise'], $data->getStudios());
        self::assertSame('tv', $data->getType());
        self::assertSame($datePremiere, $data->getDatePremiere());
        self::assertSame($dateEnd, $data->getDateEnd());
        self::assertSame(24, $data->getDurationMinutes());
        self::assertSame(26, $data->getEpisodesCount());
        self::assertSame(['JP'], $data->getCountries());
        self::assertSame('https://example.com/cover.jpg', $data->getCover());
        self::assertSame(['https://example.com/1.jpg', 'https://example.com/2.jpg'], $data->getImages());
    }
}
