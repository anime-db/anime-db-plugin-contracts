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

namespace AnimeDb\PluginContracts\PluginData;

use AnimeDb\PluginContracts\Model\AnimeId;
use AnimeDb\PluginContracts\Sync\SyncItem;

/**
 * Core-provided read/write access to a plugin's own payload for a given anime,
 * for plugin-initiated writes that the filler flow does not cover (e.g.
 * enriching a card from downloaded files, or a sync plugin persisting
 * bookkeeping data outside of {@see SyncItem}).
 *
 * A plugin obtains this service via constructor injection, type-hinting this
 * interface, the same way it obtains a PSR-18 HTTP client. The instance
 * handed to a plugin is scoped to that plugin's own id — no plugin id
 * appears in the method signatures because the instance already knows it,
 * and a plugin cannot reach another plugin's slice through this interface.
 *
 * `write()` expresses intent — "persist my data" — not a lifecycle step.
 * There is deliberately no `flush()` here: how and when the write actually
 * lands (write-through, batched, immediate) is an implementation concern of
 * the host application, not something the contract dictates.
 */
interface PluginDataStoreInterface
{
    /**
     * Read this plugin's previously stored payload for the given anime.
     *
     * Returns an empty array if nothing has been stored yet.
     *
     * @return array<string, mixed>
     */
    public function read(AnimeId $anime): array;

    /**
     * Replace this plugin's stored payload for the given anime with the
     * given data and persist it.
     *
     * An override, not a merge: keys already stored from a previous write()
     * that are absent from $data are removed.
     *
     * @param array<string, mixed> $data
     */
    public function write(AnimeId $anime, array $data): void;
}
