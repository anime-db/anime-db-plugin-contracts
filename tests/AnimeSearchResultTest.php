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

use AnimeDb\PluginContracts\CandidateSearch\AnimeSearchResult;
use AnimeDb\PluginContracts\CandidateSearch\AnimeSearchResultAction;
use AnimeDb\PluginContracts\CandidateSearch\AnimeSearchResultItem;
use PHPUnit\Framework\TestCase;

class AnimeSearchResultTest extends TestCase
{
    public function testItemCarriesFieldsActionsAndOpaqueMeta(): void
    {
        $action = new AnimeSearchResultAction('download', 'Download');
        $item = new AnimeSearchResultItem(
            title: 'Cowboy Bebop',
            externalId: 'external-42',
            image: 'base64-cover-data',
            fields: ['seeders' => '42', 'size' => '4.1 GB'],
            actions: [$action],
            meta: 'opaque-plugin-payload',
        );

        self::assertSame('Cowboy Bebop', $item->title);
        self::assertSame('external-42', $item->externalId);
        self::assertSame('base64-cover-data', $item->image);
        self::assertSame(['seeders' => '42', 'size' => '4.1 GB'], $item->fields);
        self::assertSame([$action], $item->actions);
        self::assertSame('opaque-plugin-payload', $item->meta);
        self::assertSame('download', $item->actions[0]->id);
        self::assertSame('Download', $item->actions[0]->label);
    }

    public function testResultCarriesItemsList(): void
    {
        $item = new AnimeSearchResultItem('Cowboy Bebop', 'external-42', null, [], [], 'meta');

        $result = new AnimeSearchResult([$item]);

        self::assertSame([$item], $result->items);
    }
}
