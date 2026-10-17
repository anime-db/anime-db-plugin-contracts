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

use AnimeDb\PluginContracts\Catalog\AnimeField;
use PHPUnit\Framework\TestCase;

class AnimeFieldTest extends TestCase
{
    public function testHasExactlyTheDocumentedCases(): void
    {
        $expected = [
            'Title' => 'title',
            'AlternativeNames' => 'alternative_names',
            'Type' => 'type',
            'Demographic' => 'demographic',
            'Genres' => 'genres',
            'Themes' => 'themes',
            'EpisodesCount' => 'episodes_count',
            'Sources' => 'sources',
            'Description' => 'description',
            'DatePremiere' => 'date_premiere',
            'DateEnd' => 'date_end',
            'DurationMinutes' => 'duration_minutes',
            'Studios' => 'studios',
            'Countries' => 'countries',
            'Cover' => 'cover',
        ];

        $actual = [];
        foreach (AnimeField::cases() as $case) {
            $actual[$case->name] = $case->value;
        }

        self::assertSame($expected, $actual);
    }
}
