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
 * Capability of resolving this plugin's own external source id.
 *
 * Extended by every role interface that needs this capability
 * (SearchByPluginInterface, SyncInterface, EntryWidgetInterface,
 * CatalogWidgetInterface, and transitively FillerInterface).
 * Not a common ancestor of all plugins, and not implemented by
 * {@see DownloadCandidateSearchInterface}, whose search() takes a free-text
 * query rather than a list of urls and whose candidates carry their own
 * identity via {@see AnimeSearchResultItem::$externalId}. A plugin that
 * reacts to catalog events without talking to an external source (manifest
 * `type: local`) has no use for resolveExternalId() either. Listing
 * installed plugins is done from manifests, not from a marker interface.
 */
interface ExternalIdResolutionInterface
{
    /**
     * Resolve this plugin's external id from a list of catalog record URLs.
     *
     * The plugin receives all external source URLs already attached to a
     * catalog record, parses them against its own vendor URL pattern, and
     * returns the id it recognizes, or null if none of the URLs belong to it.
     *
     * @param string[] $urls list of external source URLs already attached to the catalog record
     *
     * @return string|null external id resolved by this plugin from the URLs, or null if none matched
     */
    public function resolveExternalId(array $urls): ?string;
}
