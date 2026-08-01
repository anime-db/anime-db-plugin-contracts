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

use AnimeDb\PluginContracts\Download\DownloadSource;
use AnimeDb\PluginContracts\Download\DownloadSourceType;
use PHPUnit\Framework\TestCase;

class DownloadSourceTest extends TestCase
{
    public function testMagnetAcceptsAWellFormedUri(): void
    {
        $source = DownloadSource::magnet('magnet:?xt=urn:btih:c12fe1c06bba254a9dc9f519b335aa7c1367a88a&dn=example');

        self::assertSame(DownloadSourceType::Magnet, $source->type);
        self::assertSame('magnet:?xt=urn:btih:c12fe1c06bba254a9dc9f519b335aa7c1367a88a&dn=example', $source->value);
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function invalidMagnetUris(): iterable
    {
        yield 'missing scheme' => ['xt=urn:btih:c12fe1c06bba254a9dc9f519b335aa7c1367a88a'];
        yield 'wrong urn namespace' => ['magnet:?xt=urn:sha1:c12fe1c06bba254a9dc9f519b335aa7c1367a88a'];
        yield 'hash too short' => ['magnet:?xt=urn:btih:c12fe1c'];
        yield 'not a magnet uri at all' => ['https://example.com/file.torrent'];
    }

    /**
     * @dataProvider invalidMagnetUris
     */
    public function testMagnetRejectsMalformedUri(string $uri): void
    {
        $this->expectException(\InvalidArgumentException::class);

        DownloadSource::magnet($uri);
    }

    public function testTorrentFileAcceptsAPathEndingWithTorrentExtension(): void
    {
        $source = DownloadSource::torrentFile('/tmp/downloads/cowboy-bebop.torrent');

        self::assertSame(DownloadSourceType::TorrentFile, $source->type);
        self::assertSame('/tmp/downloads/cowboy-bebop.torrent', $source->value);
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function invalidTorrentFilePaths(): iterable
    {
        yield 'empty string' => [''];
        yield 'wrong extension' => ['/tmp/downloads/cowboy-bebop.zip'];
        yield 'no extension' => ['/tmp/downloads/cowboy-bebop'];
    }

    /**
     * @dataProvider invalidTorrentFilePaths
     */
    public function testTorrentFileRejectsPathWithoutTorrentExtension(string $path): void
    {
        $this->expectException(\InvalidArgumentException::class);

        DownloadSource::torrentFile($path);
    }
}
