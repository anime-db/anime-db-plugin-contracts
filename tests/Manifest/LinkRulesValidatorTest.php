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

namespace AnimeDb\PluginContracts\Tests\Manifest;

use AnimeDb\PluginContracts\LinkRules\LinkRulesValidator as BaseLinkRulesValidator;
use AnimeDb\PluginContracts\Manifest\LinkRulesValidator;
use PHPUnit\Framework\TestCase;

/**
 * @group legacy
 */
final class LinkRulesValidatorTest extends TestCase
{
    public function testDeprecatedClassExtendsNewValidator(): void
    {
        self::assertInstanceOf(BaseLinkRulesValidator::class, new LinkRulesValidator());
    }

    public function testDeprecatedClassIsMarkedDeprecated(): void
    {
        $doc = (new \ReflectionClass(LinkRulesValidator::class))->getDocComment();

        self::assertIsString($doc);
        self::assertStringContainsString('@deprecated', $doc);
    }

    public function testDeprecatedClassStillValidates(): void
    {
        $validator = new LinkRulesValidator();

        self::assertSame([], $validator->validate([
            'version' => 1,
            'rules' => ['https://example\.com/(\d+)' => 'https://example.com/$1'],
        ]));
        self::assertNotSame([], $validator->validate(['version' => 2, 'rules' => []]));
    }
}
