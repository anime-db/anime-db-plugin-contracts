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

namespace AnimeDb\PluginContracts\CandidateSearch;

use AnimeDb\PluginContracts\Download\DownloadAlreadyLinkedToAnotherAnimeException;
use AnimeDb\PluginContracts\Download\DownloadServiceInterface;
use AnimeDb\PluginContracts\ExternalIdResolutionInterface;
use AnimeDb\PluginContracts\Model\AnimeId;
use AnimeDb\PluginContracts\Search\SearchByPluginCandidate;
use AnimeDb\PluginContracts\Search\SearchByPluginInterface;

/**
 * Interactive user search on a plugin's source; returned items carry plugin-defined actions.
 *
 * Distinct from {@see SearchByPluginInterface}: that one recognizes a title while
 * scanning thousands of local folders and returns a lightweight
 * {@see SearchByPluginCandidate}. This one is triggered by the user explicitly
 * searching a source and returns rich, user-facing items with actions attached
 * — an action may, for example, queue a download via {@see DownloadServiceInterface}.
 *
 * Source-specific implementations of this interface live in their own plugins;
 * this package only defines the interface and its DTOs.
 *
 * Not an {@see ExternalIdResolutionInterface}: search() takes a free-text query
 * rather than a list of urls, and a candidate's identity is carried by
 * {@see AnimeSearchResultItem::$externalId} instead.
 */
interface CandidateSearchInterface
{
    /**
     * Search the external source for the given free-text query.
     */
    public function search(string $query): AnimeSearchResult;

    /**
     * Run a user-picked action for a previously returned {@see AnimeSearchResultItem}.
     *
     * $meta is opaque to the core: it is the same value the plugin put into the
     * item's `meta` when it was returned from {@see self::search()}, round-tripped
     * through the client unmodified. The plugin alone knows how to interpret
     * $actionId together with $meta.
     *
     * $anime is the catalog record the action applies to, resolved by the
     * core from the calling context or, if none existed yet, created from the
     * item's {@see AnimeSearchResultItem::$externalId}.
     *
     * Exceptions thrown by core-provided services (e.g. {@see DownloadAlreadyLinkedToAnotherAnimeException}
     * from {@see DownloadServiceInterface::enqueue()}) must not be caught or wrapped here: the
     * core handles them itself.
     */
    public function runAction(string $actionId, string $meta, AnimeId $anime): void;
}
