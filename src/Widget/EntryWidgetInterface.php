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

namespace AnimeDb\PluginContracts\Widget;

use AnimeDb\PluginContracts\Catalog\AnimeView;
use AnimeDb\PluginContracts\Catalog\CatalogReaderInterface;
use AnimeDb\PluginContracts\ExternalIdResolutionInterface;
use AnimeDb\PluginContracts\Model\AnimeId;

/**
 * Widget rendered on a single catalog record's page.
 *
 * A plugin may declare several widgets, one class per widget, each toggled
 * independently in the host UI. The rendered output is a raw HTML string
 * rather than structured data: this keeps the contract simple and covers
 * widgets that are not a list of catalog records. Visual consistency for
 * the common "list of records" case is a host-side optional helper, not a
 * rigid schema in this contract.
 *
 * `render()` takes the record's {@see AnimeId}, not a pre-resolved external
 * id: many widgets don't need one at all (e.g. a download-status widget
 * reads its own slice by `AnimeId`). The record's state — including the
 * already-resolved {@see AnimeView::$externalId} — is read through
 * {@see CatalogReaderInterface} injected into the plugin, not passed to
 * `render()`. A widget that needs its own external id beyond what
 * `CatalogReaderInterface` exposes implements
 * {@see ExternalIdResolutionInterface} additionally and explicitly, as a
 * declared capability rather than a side effect of inheritance.
 */
interface EntryWidgetInterface
{
    /**
     * Widget metadata: code name and title/description translation keys.
     *
     * Static so the host can read {@see WidgetMetadata::$name} (for its
     * DI tag / URL / `features` key) while compiling the container, without
     * instantiating the widget class. Must return a literal value object
     * with no heavy logic or side effects: it runs at container build time
     * on plugin install/activate.
     */
    public static function metadata(): WidgetMetadata;

    /**
     * Render the widget for a single catalog record.
     *
     * Pending update: a widget that has nothing to show yet returns its HTML
     * prefixed with {@see WidgetPendingUpdate::MARKER} (or built with
     * {@see WidgetPendingUpdate::mark()}).
     *
     * - The marker must be the very first bytes of the returned string:
     *   nothing before it, no whitespace, line break or BOM. The prefix
     *   check is byte-for-byte.
     * - The host strips the marker before sanitizing; it never reaches the
     *   response body.
     * - A marked response is served without `max-age`; an unmarked response
     *   is cached as before.
     * - Dropping the cache does not mean the host re-requests the widget: it
     *   polls nothing itself. A marked response is simply not served stale
     *   from the browser cache on the next request of the slot (page reload
     *   or navigating back to the page). A widget that needs to refresh
     *   itself must arrange that on its own.
     * - The marker changes nothing except caching: status code, sanitizing
     *   and exception handling are the same.
     *
     * @return string rendered widget markup as a raw HTML string
     */
    public function render(AnimeId $anime): string;
}
