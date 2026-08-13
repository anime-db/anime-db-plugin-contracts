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

use AnimeDb\PluginContracts\Search\SearchByPluginCandidate;
use PHPUnit\Framework\TestCase;

class SearchByPluginCandidateTest extends TestCase
{
    public function testGettersReturnConstructorValues(): void
    {
        $candidate = new SearchByPluginCandidate('my-plugin', 'Cowboy Bebop', '12345');

        self::assertSame('my-plugin', $candidate->getPluginId());
        self::assertSame('Cowboy Bebop', $candidate->getName());
        self::assertSame('12345', $candidate->getExternalId());
    }

    public function testExternalIdAcceptsNonNumericString(): void
    {
        $candidate = new SearchByPluginCandidate('my-plugin', 'Cowboy Bebop', 'cowboy-bebop');

        self::assertIsString($candidate->getExternalId());
        self::assertSame('cowboy-bebop', $candidate->getExternalId());
    }
}
