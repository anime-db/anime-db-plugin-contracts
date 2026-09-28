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

namespace AnimeDb\PluginContracts\Tests\Widget;

use AnimeDb\PluginContracts\Widget\WidgetListItem;
use PHPUnit\Framework\TestCase;

class WidgetListItemTest extends TestCase
{
    public function testConstructWithAllFields(): void
    {
        $item = new WidgetListItem(
            thumbnail: 'app-media://thumbnails/1.jpg',
            title: 'Title',
            subtitle: 'Subtitle',
            url: '/anime/1',
        );

        self::assertSame('app-media://thumbnails/1.jpg', $item->thumbnail);
        self::assertSame('Title', $item->title);
        self::assertSame('Subtitle', $item->subtitle);
        self::assertSame('/anime/1', $item->url);
    }

    public function testConstructWithOptionalFieldsNull(): void
    {
        $item = new WidgetListItem(
            thumbnail: null,
            title: 'Title',
            subtitle: null,
            url: '/anime/1',
        );

        self::assertNull($item->thumbnail);
        self::assertSame('Title', $item->title);
        self::assertNull($item->subtitle);
        self::assertSame('/anime/1', $item->url);
    }
}
