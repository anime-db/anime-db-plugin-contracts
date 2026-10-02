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

use AnimeDb\PluginContracts\Download\DownloadAlreadyLinkedToAnotherAnimeException;
use AnimeDb\PluginContracts\Model\AnimeId;
use PHPUnit\Framework\TestCase;

class DownloadAlreadyLinkedToAnotherAnimeExceptionTest extends TestCase
{
    public function testExposesInfoHashAndOccupyingAnimeIdAsPublicReadonlyProperties(): void
    {
        $occupyingAnimeId = new AnimeId(42);

        $exception = new DownloadAlreadyLinkedToAnotherAnimeException('c12fe1c06bba254a9dc9f519b335aa7c1367a88a', $occupyingAnimeId);

        self::assertSame('c12fe1c06bba254a9dc9f519b335aa7c1367a88a', $exception->infoHash);
        self::assertSame($occupyingAnimeId, $exception->occupyingAnimeId);
    }

    public function testFormatsMessageWithInfoHashAndOccupyingAnimeId(): void
    {
        $exception = new DownloadAlreadyLinkedToAnotherAnimeException('c12fe1c06bba254a9dc9f519b335aa7c1367a88a', new AnimeId(42));

        self::assertSame('Torrent "c12fe1c06bba254a9dc9f519b335aa7c1367a88a" is already linked to anime #42.', $exception->getMessage());
    }

    public function testIsARuntimeException(): void
    {
        $exception = new DownloadAlreadyLinkedToAnotherAnimeException('c12fe1c06bba254a9dc9f519b335aa7c1367a88a', new AnimeId(42));

        self::assertInstanceOf(\RuntimeException::class, $exception);
    }

    /**
     * @dataProvider provideInvalidInfoHashes
     */
    public function testRejectsInfoHashNotInCanonicalForm(string $infoHash): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new DownloadAlreadyLinkedToAnotherAnimeException($infoHash, new AnimeId(42));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideInvalidInfoHashes(): iterable
    {
        yield 'empty string' => [''];
        yield 'uppercase hex' => ['C12FE1C06BBA254A9DC9F519B335AA7C1367A88A'];
        yield 'base32' => ['YEX6DQDLXISUVHOJPGKVWYNQ7YNOVSMK'];
        yield 'sha256 hex (64 chars)' => ['c12fe1c06bba254a9dc9f519b335aa7c1367a88ac12fe1c06bba254a9dc9f519'];
    }
}
