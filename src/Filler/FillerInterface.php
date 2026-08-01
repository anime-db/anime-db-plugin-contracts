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

namespace AnimeDb\PluginContracts\Filler;

use AnimeDb\PluginContracts\Search\SearchByPluginInterface;

/**
 * Contract for plugins that can fill in a catalog record's card with data
 * pulled from an external source.
 *
 * Extends SearchByPluginInterface: a plugin able to fill in a card is
 * assumed to be able to search for it too, so find() is reused as-is
 * instead of duplicating a separate heavy search method here.
 *
 * Filling in a single field is not a separate plugin method: the caller
 * fetches the full PluginAnimeData via findById()/find() and applies only
 * the field it needs. Filling in everything is the same findById() call
 * with all supported fields applied.
 */
interface FillerInterface extends SearchByPluginInterface
{
    /**
     * Fetch a fully filled-in data object for a known external id.
     *
     * The result is cacheable on the caller's side.
     */
    public function findById(string $externalId): ?PluginAnimeData;

    /**
     * List of PluginAnimeData fields this plugin is actually able to fill in.
     *
     * Used by the host UI to show per-field fill-in buttons only for fields
     * the plugin supports.
     *
     * @return string[]
     */
    public function getFillableFields(): array;
}
