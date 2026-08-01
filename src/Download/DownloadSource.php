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

namespace AnimeDb\PluginContracts\Download;

/**
 * Source handed to {@see DownloadServiceInterface::enqueue()} to start a download.
 *
 * Only buildable through its named constructors, each self-validating its input and
 * doubling as documentation of what a download task can actually be built from.
 * Extensible without touching existing plugins: a new source kind (e.g. a future
 * `::url()`) is a new named constructor and a new {@see DownloadSourceType} case.
 */
final class DownloadSource
{
    private function __construct(
        public readonly DownloadSourceType $type,
        public readonly string $value,
    ) {
    }

    /**
     * Build a source from a magnet URI.
     *
     * @throws \InvalidArgumentException if `$uri` is not a well-formed
     *                                   `magnet:?xt=urn:btih:...` URI
     */
    public static function magnet(string $uri): self
    {
        if (preg_match('/^magnet:\?xt=urn:btih:[a-zA-Z0-9]{32,40}(&.*)?$/', $uri) !== 1) {
            throw new \InvalidArgumentException(\sprintf('"%s" is not a valid "magnet:?xt=urn:btih:..." URI.', $uri));
        }

        return new self(DownloadSourceType::Magnet, $uri);
    }

    /**
     * Build a source from a path to a local `.torrent` file.
     *
     * @throws \InvalidArgumentException if `$path` does not end with a `.torrent` extension
     */
    public static function torrentFile(string $path): self
    {
        if ($path === '' || strtolower(substr($path, -8)) !== '.torrent') {
            throw new \InvalidArgumentException(\sprintf('"%s" is not a path to a ".torrent" file.', $path));
        }

        return new self(DownloadSourceType::TorrentFile, $path);
    }
}
