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

use AnimeDb\PluginContracts\Settings\SettingsPageInterface;
use PHPUnit\Framework\TestCase;

class SettingsPageInterfaceTest extends TestCase
{
    public function testRenderReturnsPageMarkup(): void
    {
        $page = new class implements SettingsPageInterface {
            public function render(): string
            {
                return '<form hx-post="/plugins/my-source/settings"></form>';
            }
        };

        self::assertSame('<form hx-post="/plugins/my-source/settings"></form>', $page->render());
    }
}
