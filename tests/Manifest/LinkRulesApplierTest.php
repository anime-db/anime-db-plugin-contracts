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

use AnimeDb\PluginContracts\Manifest\LinkRulesApplier;
use PHPUnit\Framework\TestCase;

class LinkRulesApplierTest extends TestCase
{
    private const WIKI = 'https://([a-z]+)\.wikipedia\.org/wiki/(\w+)';

    public function testFirstMatchingRuleWins(): void
    {
        $rules = [
            self::WIKI => 'https://$1.wiki.example/$2',
            'https://en\.wikipedia\.org/wiki/(\w+)' => 'https://other.example/$1',
        ];

        self::assertSame('https://en.wiki.example/Naruto', (new LinkRulesApplier())->apply($rules, 'https://en.wikipedia.org/wiki/Naruto'));
    }

    public function testSubstringDoesNotMatch(): void
    {
        $rules = [self::WIKI => 'https://$1.wiki.example/$2'];
        $applier = new LinkRulesApplier();

        self::assertNull($applier->apply($rules, 'https://en.wikipedia.org/wiki/Naruto?x=1'));
        self::assertNull($applier->apply($rules, 'prefix https://en.wikipedia.org/wiki/Naruto'));
    }

    public function testBracedSubstitution(): void
    {
        $rules = ['https://a\.example/(\d+)' => 'https://b.example/${1}0'];

        self::assertSame('https://b.example/50', (new LinkRulesApplier())->apply($rules, 'https://a.example/5'));
    }

    public function testInvalidResultFallsThroughToNextRule(): void
    {
        $rules = [
            'https://a\.example/(\d+)' => 'http://b.example/$1',
            'https://a\.example/(\d+)|x' => 'https:///$1',
            'https://a\.example/(\d)' => 'https://c.example/$1',
        ];
        $applier = new LinkRulesApplier();

        self::assertSame('https://c.example/7', $applier->apply($rules, 'https://a.example/7'));
        self::assertNull($applier->apply(['https://a\.example/(\d+)' => 'http://b.example/$1'], 'https://a.example/7'));
        self::assertNull($applier->apply(['https://a\.example/(\d+)' => 'https:///$1'], 'https://a.example/7'));
    }

    public function testNoMatchReturnsNull(): void
    {
        self::assertNull((new LinkRulesApplier())->apply([self::WIKI => 'https://x.example/$2'], 'https://other.example/'));
        self::assertNull((new LinkRulesApplier())->apply([], 'https://other.example/'));
    }

    public function testCatastrophicExpressionIsSkipped(): void
    {
        $url = 'https://'.str_repeat('a', 40).'.example/';
        $errors = [];

        $result = (new LinkRulesApplier())->apply([
            'https://(?:a+)+(?:b|\.example/x)' => 'https://bad.example/$0',
            'https?://(a+)\.example/' => 'https://good.example/$1',
        ], $url, static function (string $expression, string $message) use (&$errors): void {
            $errors[$expression] = $message;
        });

        self::assertSame('https://good.example/'.str_repeat('a', 40), $result);
        self::assertSame(['https://(?:a+)+(?:b|\.example/x)'], array_keys($errors));
        self::assertNotSame('', $errors['https://(?:a+)+(?:b|\.example/x)']);
    }

    public function testInvalidExpressionIsSkippedWithoutCallback(): void
    {
        $rules = ['(' => 'https://x.example/$1', 'https://a\.example/(\d+)' => 'https://b.example/$1'];

        self::assertSame('https://b.example/1', (new LinkRulesApplier())->apply($rules, 'https://a.example/1'));
    }

    public function testBacktrackLimitIsRestored(): void
    {
        $before = ini_get('pcre.backtrack_limit');
        $applier = new LinkRulesApplier();

        $applier->apply([self::WIKI => 'https://x.example/$2'], 'https://en.wikipedia.org/wiki/Naruto');
        self::assertSame($before, ini_get('pcre.backtrack_limit'));

        $applier->apply(['https://(?:a+)+(?:b|\.example/x)' => 'https://x.example/$0'], 'https://'.str_repeat('a', 40).'.example/');
        self::assertSame($before, ini_get('pcre.backtrack_limit'));

        $applier->apply(['(' => 'https://x.example/'], 'https://a.example/');
        self::assertSame($before, ini_get('pcre.backtrack_limit'));

        try {
            $applier->apply(['https://a\.example/' => 'https://b.example/'], 'https://a.example/', static function (): void {
                throw new \RuntimeException('boom');
            });
        } catch (\RuntimeException) {
        }
        self::assertSame($before, ini_get('pcre.backtrack_limit'));
    }

    public function testBacktrackLimitIsSetDuringCall(): void
    {
        $seen = null;
        (new LinkRulesApplier())->apply(['(' => 'https://x.example/'], 'https://a.example/', static function () use (&$seen): void {
            $seen = ini_get('pcre.backtrack_limit');
        });

        self::assertSame((string) LinkRulesApplier::BACKTRACK_LIMIT, $seen);
    }
}
