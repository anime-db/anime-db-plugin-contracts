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

namespace AnimeDb\PluginContracts;

/**
 * Interactive user search for downloadable candidates on an external source.
 *
 * Distinct from {@see SearchByPluginInterface}: that one recognizes a title while
 * scanning thousands of local folders and returns a lightweight
 * {@see SearchByPluginCandidate}. This one is triggered by the user explicitly
 * searching a source and returns rich, user-facing items with actions attached
 * (typically including "download", implemented by the plugin via
 * {@see DownloadServiceInterface}).
 *
 * Source-specific implementations of this interface live in their own plugins;
 * this package only defines the interface and its DTOs.
 */
interface DownloadCandidateSearchInterface extends IntegrationPluginInterface
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
     */
    public function runAction(string $actionId, string $meta): void;
}
