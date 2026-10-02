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

namespace AnimeDb\PluginContracts\Download;

use AnimeDb\PluginContracts\Model\AnimeId;

/**
 * Thrown by {@see DownloadServiceInterface::enqueue()} when the given source is already
 * attached to a different anime: one torrent can only ever be linked to one catalog entry.
 *
 * The core raises this and also handles it — by offering the user a re-link dialog — so a
 * plugin only needs to recognize the class well enough to let it propagate unmodified. See
 * {@see \AnimeDb\PluginContracts\CandidateSearch\DownloadCandidateSearchInterface::runAction()}.
 */
final class DownloadAlreadyLinkedToAnotherAnimeException extends \RuntimeException
{
    /**
     * Canonical form: a lowercase 40-character hex BitTorrent v1 infohash, as normalized by
     * the core regardless of how the source was given (hex or base32 magnet, `.torrent` file).
     * A plugin comparing it with its own magnet must normalize the same way.
     */
    public readonly string $infoHash;
    public readonly AnimeId $occupyingAnimeId;

    public function __construct(string $infoHash, AnimeId $occupyingAnimeId)
    {
        parent::__construct(\sprintf('Torrent "%s" is already linked to anime #%d.', $infoHash, $occupyingAnimeId->value));

        $this->infoHash = $infoHash;
        $this->occupyingAnimeId = $occupyingAnimeId;
    }
}
